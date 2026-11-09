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
        $latest = $this->taskCompletions()->latest('completed_at')->first();

        if ($latest === null) {
            return null;
        }

        $availableAt = $latest->completed_at->copy()->addMinutes($this->plan()->duration_minutes);

        return $availableAt->isFuture() ? $availableAt : null;
    }

    public function completeAvailableTask(): TaskCompletion
    {
        return DB::transaction(function (): TaskCompletion {
            $user = static::query()->whereKey($this->id)->lockForUpdate()->firstOrFail();
            $plan = $user->plan();
            $completedToday = $user->taskCompletions()->whereDate('completed_at', today())->count();

            if ($completedToday >= $plan->daily_task_limit) {
                throw ValidationException::withMessages([
                    'task' => 'انتهى عدد المهام المتاحة لليوم.',
                ]);
            }

            $latest = $user->taskCompletions()->latest('completed_at')->first();

            if ($latest !== null && $latest->completed_at->copy()->addMinutes($plan->duration_minutes)->isFuture()) {
                throw ValidationException::withMessages([
                    'task' => 'انتظر حتى ينتهي وقت المهمة الحالية.',
                ]);
            }

            $completion = $user->taskCompletions()->create([
                'reward_amount' => $plan->reward_amount,
                'completed_at' => now(),
            ]);

            $user->increment('balance', $plan->reward_amount);

            return $completion;
        });
    }
}
