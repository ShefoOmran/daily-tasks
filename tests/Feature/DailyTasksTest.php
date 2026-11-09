<?php

namespace Tests\Feature;

use App\Models\PaymentMethod;
use App\Models\Subscription;
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
                ->where('summary.reward_amount', '0.50')
                ->where('summary.duration_minutes', 30));
    }

    public function test_completing_a_task_adds_the_reward_to_the_balance(): void
    {
        $plan = $this->freePlan();
        $user = User::factory()->create(['subscription_id' => $plan->id, 'balance' => 0]);

        $this->actingAs($user)
            ->post('/tasks')
            ->assertRedirect('/dashboard');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'balance' => 0.50,
        ]);
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
