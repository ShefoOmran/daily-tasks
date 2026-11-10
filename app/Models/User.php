<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

#[Fillable(['name', 'email', 'password', 'balance', 'is_admin', 'subscription_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected static function booted(): void
    {
        static::saving(function (User $user): void {
            if ($user->is_admin) {
                $user->balance = 0;
                $user->subscription_id = null;
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'balance' => 'decimal:2',
            'is_admin' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Subscription, $this>
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * @return HasMany<TaskCompletion, $this>
     */
    public function taskCompletions(): HasMany
    {
        return $this->hasMany(TaskCompletion::class);
    }

    /**
     * @return HasMany<BalanceTransaction, $this>
     */
    public function balanceTransactions(): HasMany
    {
        return $this->hasMany(BalanceTransaction::class);
    }

    public function plan(): Subscription
    {
        return $this->subscription ?? Subscription::freePlan();
    }

    public function tasksCompletedToday(): int
    {
        return $this->taskCompletions()
            ->whereDate('completed_at', today())
            ->count();
    }

    public function availableTaskCount(): int
    {
        return max(0, $this->plan()->daily_task_limit - $this->tasksCompletedToday());
    }

    public function nextTaskAvailableAt(): ?Carbon
    {
        $latest = $this->taskCompletions()->whereNotNull('completed_at')->latest('completed_at')->first();

        if ($latest === null) {
            return null;
        }

        $availableAt = $latest->completed_at->copy()->addMinutes($this->plan()->duration_minutes);

        return $availableAt->isFuture() ? $availableAt : null;
    }

    public function visitTask(Task $task): TaskCompletion
    {
        return DB::transaction(function () use ($task): TaskCompletion {
            $user = static::query()->whereKey($this->id)->lockForUpdate()->firstOrFail();

            if ($user->is_admin) {
                throw ValidationException::withMessages([
                    'task' => 'حساب المدير مخصص للإدارة ولا يمكنه تنفيذ المهام.',
                ]);
            }

            $plan = $user->plan();
            $completedToday = $user->taskCompletions()->whereDate('completed_at', today())->count();

            if ($completedToday >= $plan->daily_task_limit) {
                throw ValidationException::withMessages([
                    'task' => 'انتهى عدد المهام المتاحة لليوم.',
                ]);
            }

            $nextTaskAt = $user->nextTaskAvailableAt();

            if ($nextTaskAt?->isFuture()) {
                throw ValidationException::withMessages([
                    'task' => 'انتظر حتى ينتهي الفاصل المحدد في اشتراكك.',
                ]);
            }

            $activeTask = Task::query()->whereKey($task->getKey())->where('is_active', true)->first();

            if ($activeTask === null || blank($activeTask->external_url)) {
                throw ValidationException::withMessages([
                    'task' => 'هذه المهمة غير متاحة حالياً أو لم يتم ضبط رابطها.',
                ]);
            }

            $completion = $user->taskCompletions()->create([
                'task_id' => $activeTask->id,
                'reward_amount' => $activeTask->reward_amount,
                'started_at' => now(),
                'task_duration_minutes' => $activeTask->duration_minutes,
                'completed_at' => now(),
                'status' => 'completed',
            ]);
            $user->increment('balance', $activeTask->reward_amount);

            return $completion;
        });
    }
}
