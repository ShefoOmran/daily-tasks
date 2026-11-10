<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'task_id',
    'reward_amount',
    'started_at',
    'task_duration_minutes',
    'completed_at',
    'status',
    'submission_text',
    'submission_file_path',
    'submitted_at',
    'reviewed_at',
    'review_note',
])]
class TaskCompletion extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'reward_amount' => 'decimal:2',
            'started_at' => 'datetime',
            'task_duration_minutes' => 'integer',
            'completed_at' => 'datetime',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Task, $this>
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}
