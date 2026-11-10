<?php

namespace Tests\Feature;

use App\Models\PaymentMethod;
use App\Models\Subscription;
use App\Models\Task;
use App\Models\User;
use App\TransactionType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DailyTasksTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_sent_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_member_sees_the_free_daily_task_count(): void
    {
        $plan = $this->freePlan();
        $user = User::factory()->create(['subscription_id' => $plan->id]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('summary.available_tasks', 5)
                ->where('summary.balance', '0.00')
                ->where('summary.duration_minutes', 30));
    }

    public function test_visiting_task_records_completion_awards_reward_and_redirects(): void
    {
        $plan = $this->freePlan();
        $user = User::factory()->create(['subscription_id' => $plan->id, 'balance' => 0]);
        $task = Task::factory()->create([
            'duration_minutes' => 1,
            'reward_amount' => 0.50,
            'external_url' => 'https://example.test/execute',
        ]);

        $this->actingAs($user)
            ->post("/tasks/{$task->id}/visit")
            ->assertRedirect('https://example.test/execute');

        $this->assertSame('0.50', $user->fresh()->balance);
        $this->assertDatabaseHas('task_completions', [
            'user_id' => $user->id,
            'task_id' => $task->id,
            'reward_amount' => 0.50,
            'status' => 'completed',
        ]);
    }

    public function test_member_can_view_task_details_and_reward(): void
    {
        $this->freePlan();
        $user = User::factory()->create();
        $task = Task::factory()->create([
            'duration_minutes' => 3,
            'reward_amount' => 2.40,
        ]);

        $this->actingAs($user)
            ->get("/tasks/{$task->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Tasks/Show')
                ->where('task.duration_minutes', 3)
                ->where('task.reward_amount', '2.40')
                ->where('task.external_url', $task->external_url)
                ->where('plan.interval_minutes', 30));
    }

    public function test_member_cannot_receive_another_task_reward_before_subscription_interval(): void
    {
        $this->freePlan();
        $user = User::factory()->create();
        $task = Task::factory()->create(['reward_amount' => 1.75]);
        $this->actingAs($user)->post("/tasks/{$task->id}/visit");

        $this->post("/tasks/{$task->id}/visit")
            ->assertSessionHasErrors('task');

        $this->assertSame('1.75', $user->fresh()->balance);
        $this->assertSame(1, $user->taskCompletions()->count());
    }

    public function test_task_without_external_url_cannot_award_a_reward(): void
    {
        $this->freePlan();
        $user = User::factory()->create();
        $task = Task::factory()->create(['external_url' => null]);

        $this->actingAs($user)
            ->post("/tasks/{$task->id}/visit")
            ->assertSessionHasErrors('task');

        $this->assertSame('0.00', $user->fresh()->balance);
        $this->assertSame(0, $user->taskCompletions()->count());
    }

    public function test_admin_can_monitor_task_activity_without_approval_actions(): void
    {
        $plan = $this->freePlan();
        $user = User::factory()->create(['subscription_id' => $plan->id]);
        $task = Task::factory()->create();
        $this->actingAs($user)->post("/tasks/{$task->id}/visit");
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->get('/admin/task-activity')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/TaskActivity/Index')
                ->where('activity.total', 1)
                ->where('activity.data.0.member_name', $user->name));

        $this->post("/admin/submissions/{$user->taskCompletions()->firstOrFail()->id}/approve")->assertNotFound();
    }

    public function test_admin_is_redirected_to_admin_dashboard_and_cannot_use_member_features(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'balance' => 75]);
        $task = Task::factory()->create();

        $this->actingAs($admin)
            ->get('/dashboard')
            ->assertRedirect('/admin');

        $this->assertSame('0.00', $admin->fresh()->balance);
        $this->assertNull($admin->fresh()->subscription_id);

        $this->post("/tasks/{$task->id}/visit")->assertForbidden();
        $this->post('/deposits', [])->assertForbidden();
        $this->post('/withdrawals', [])->assertForbidden();
        $this->post('/subscriptions', [])->assertForbidden();

        $this->get('/balance')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Balance/Index')
                ->where('is_admin', true)
                ->where('balance', null));
    }

    public function test_admin_can_manage_tasks_while_members_cannot(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $member = User::factory()->create(['is_admin' => false]);
        $taskData = [
            'title' => 'مراجعة المحتوى',
            'description' => 'اقرأ التعليمات وأكملها.',
            'duration_minutes' => 8,
            'reward_amount' => 1.25,
            'external_url' => 'https://example.test/task',
            'is_active' => true,
        ];

        $this->actingAs($admin)
            ->post('/admin/tasks', $taskData)
            ->assertRedirect('/admin/tasks');

        $task = Task::query()->firstOrFail();

        $this->actingAs($member)
            ->put("/admin/tasks/{$task->id}", $taskData)
            ->assertForbidden();

        $this->actingAs($admin)
            ->put("/admin/tasks/{$task->id}", [...$taskData, 'duration_minutes' => 12])
            ->assertRedirect('/admin/tasks');

        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'duration_minutes' => 12]);

        $this->actingAs($admin)
            ->get('/admin/tasks')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Admin/Tasks/Index')->has('tasks', 1));

        $this->delete("/admin/tasks/{$task->id}")->assertRedirect('/admin/tasks');
        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    public function test_deposit_and_withdrawal_update_the_balance(): void
    {
        $this->freePlan();
        $user = User::factory()->create(['balance' => 10]);
        $deposit = PaymentMethod::query()->create([
            'name' => 'تحويل',
            'type' => TransactionType::Deposit,
            'details' => 'تفاصيل الإيداع',
            'is_active' => true,
        ]);
        $withdrawal = PaymentMethod::query()->create([
            'name' => 'محفظة',
            'type' => TransactionType::Withdrawal,
            'details' => 'تفاصيل السحب',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->post('/deposits', ['amount' => 5, 'payment_method_id' => $deposit->id])
            ->assertRedirect('/balance');

        $this->actingAs($user)
            ->post('/withdrawals', ['amount' => 4, 'payment_method_id' => $withdrawal->id])
            ->assertRedirect('/balance');

        $this->assertSame('11.00', $user->fresh()->balance);
    }

    public function test_withdrawal_above_the_balance_is_rejected(): void
    {
        $this->freePlan();
        $user = User::factory()->create(['balance' => 2]);
        $withdrawal = PaymentMethod::query()->create([
            'name' => 'محفظة',
            'type' => TransactionType::Withdrawal,
            'details' => 'تفاصيل السحب',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->from('/balance')
            ->post('/withdrawals', ['amount' => 5, 'payment_method_id' => $withdrawal->id])
            ->assertSessionHasErrors('amount');

        $this->assertSame('2.00', $user->fresh()->balance);
    }

    public function test_member_can_buy_a_subscription_from_the_balance(): void
    {
        $free = $this->freePlan();
        $paid = Subscription::query()->create([
            'name' => 'الأساسية',
            'price' => 10,
            'daily_task_limit' => 15,
            'reward_amount' => 1,
            'duration_minutes' => 20,
            'is_free' => false,
        ]);
        $user = User::factory()->create([
            'subscription_id' => $free->id,
            'balance' => 20,
        ]);

        $this->actingAs($user)
            ->post('/subscriptions', ['subscription_id' => $paid->id])
            ->assertRedirect('/subscriptions');

        $user->refresh();
        $this->assertSame($paid->id, $user->subscription_id);
        $this->assertSame('10.00', $user->balance);
    }

    public function test_admin_can_update_a_subscription(): void
    {
        $this->freePlan();
        $plan = Subscription::query()->create([
            'name' => 'الأساسية',
            'price' => 10,
            'daily_task_limit' => 15,
            'reward_amount' => 1,
            'duration_minutes' => 20,
            'is_free' => false,
        ]);
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->put("/admin/subscriptions/{$plan->id}", [
                'name' => 'المتقدمة',
                'price' => 25,
                'daily_task_limit' => 30,
                'reward_amount' => 2,
                'duration_minutes' => 10,
                'is_free' => false,
            ])
            ->assertRedirect('/subscriptions');

        $this->assertDatabaseHas('subscriptions', [
            'id' => $plan->id,
            'name' => 'المتقدمة',
            'price' => 25,
        ]);
    }

    public function test_admin_can_delete_an_unused_paid_subscription(): void
    {
        $this->freePlan();
        $plan = Subscription::query()->create([
            'name' => 'الأساسية',
            'price' => 10,
            'daily_task_limit' => 15,
            'reward_amount' => 1,
            'duration_minutes' => 20,
            'is_free' => false,
        ]);
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->delete("/admin/subscriptions/{$plan->id}")
            ->assertRedirect('/subscriptions');

        $this->assertDatabaseMissing('subscriptions', ['id' => $plan->id]);
    }

    public function test_admin_cannot_delete_free_or_assigned_subscriptions(): void
    {
        $free = $this->freePlan();
        $assigned = Subscription::query()->create([
            'name' => 'الأساسية',
            'price' => 10,
            'daily_task_limit' => 15,
            'reward_amount' => 1,
            'duration_minutes' => 20,
            'is_free' => false,
        ]);
        $member = User::factory()->create(['subscription_id' => $assigned->id]);
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->from('/subscriptions')
            ->delete("/admin/subscriptions/{$free->id}")
            ->assertSessionHasErrors('subscription');

        $this->from('/subscriptions')
            ->delete("/admin/subscriptions/{$assigned->id}")
            ->assertSessionHasErrors('subscription');

        $this->assertDatabaseHas('subscriptions', ['id' => $free->id]);
        $this->assertDatabaseHas('subscriptions', ['id' => $assigned->id]);
        $this->assertSame($assigned->id, $member->fresh()->subscription_id);
    }

    public function test_member_cannot_update_or_delete_a_subscription(): void
    {
        $this->freePlan();
        $plan = Subscription::query()->create([
            'name' => 'الأساسية',
            'price' => 10,
            'daily_task_limit' => 15,
            'reward_amount' => 1,
            'duration_minutes' => 20,
            'is_free' => false,
        ]);
        $member = User::factory()->create(['is_admin' => false]);

        $this->actingAs($member)
            ->put("/admin/subscriptions/{$plan->id}", [])
            ->assertForbidden();
        $this->delete("/admin/subscriptions/{$plan->id}")->assertForbidden();
    }

    public function test_admin_can_change_a_members_subscription(): void
    {
        $free = $this->freePlan();
        $paid = Subscription::query()->create([
            'name' => 'الأساسية',
            'price' => 10,
            'daily_task_limit' => 15,
            'reward_amount' => 1,
            'duration_minutes' => 20,
            'is_free' => false,
        ]);
        $admin = User::factory()->create(['is_admin' => true]);
        $member = User::factory()->create(['subscription_id' => $free->id]);

        $this->actingAs($admin)
            ->get('/admin/users')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Users/Index')
                ->where('users.total', 1)
                ->where('users.data.0.subscription_id', $free->id));

        $this->actingAs($admin)
            ->put("/admin/users/{$member->id}/subscription", ['subscription_id' => $paid->id])
            ->assertRedirect('/admin/users');

        $this->assertSame($paid->id, $member->fresh()->subscription_id);
        $this->assertSame('0.00', $admin->fresh()->balance);
    }

    public function test_only_an_admin_can_add_a_payment_method(): void
    {
        $this->freePlan();
        $member = User::factory()->create(['is_admin' => false]);

        $this->actingAs($member)
            ->post('/admin/payment-methods', [
                'name' => 'بطاقة',
                'type' => 'deposit',
                'details' => 'تفاصيل',
            ])
            ->assertForbidden();

        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->post('/admin/payment-methods', [
                'name' => 'بطاقة',
                'type' => 'deposit',
                'details' => 'تفاصيل البطاقة',
            ])
            ->assertRedirect('/balance');

        $this->assertDatabaseHas('payment_methods', ['name' => 'بطاقة']);
    }

    public function test_admin_dashboard_aggregates_live_member_data(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'balance' => 900]);
        $activeMember = User::factory()->create(['balance' => 125.50]);
        $inactiveMember = User::factory()->create(['balance' => 7.25]);

        $activeMember->taskCompletions()->createMany([
            ['reward_amount' => 0.50, 'completed_at' => now()->startOfDay()->addHour()],
            ['reward_amount' => 0.50, 'completed_at' => now()->startOfDay()->addHours(2)],
        ]);
        $inactiveMember->taskCompletions()->create([
            'reward_amount' => 0.50,
            'completed_at' => now()->subDays(31),
        ]);

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard')
                ->where('metrics.total_users', 2)
                ->where('metrics.active_users', 1)
                ->where('metrics.completed_tasks_today', 2)
                ->where('metrics.total_balance', '132.75'));
    }

    public function test_member_cannot_view_admin_dashboard(): void
    {
        $member = User::factory()->create(['is_admin' => false]);

        $this->actingAs($member)->get('/admin')->assertForbidden();
    }

    private function freePlan(): Subscription
    {
        return Subscription::query()->create([
            'name' => 'المجانية',
            'price' => 0,
            'daily_task_limit' => 5,
            'reward_amount' => 0.50,
            'duration_minutes' => 30,
            'is_free' => true,
        ]);
    }
}
