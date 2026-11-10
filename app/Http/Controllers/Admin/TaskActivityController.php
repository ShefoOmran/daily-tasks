<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TaskCompletion;
use Illuminate\Database\Eloquent\Builder;
use Inertia\Inertia;
use Inertia\Response;

class TaskActivityController extends Controller
{
    public function index(): Response
    {
        $activity = TaskCompletion::query()
            ->with(['task', 'user'])
            ->whereHas('user', function (Builder $query): void {
                $query->where('is_admin', false);
            })
            ->latest('started_at')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (TaskCompletion $completion): array => [
                'id' => $completion->id,
                'task_title' => $completion->task?->title ?? 'مهمة محذوفة',
                'member_name' => $completion->user->name,
                'member_email' => $completion->user->email,
                'reward_amount' => number_format((float) $completion->reward_amount, 2, '.', ''),
                'status_label' => in_array($completion->status, ['completed', 'approved'], true)
                    ? 'تم التنفيذ'
                    : ($completion->status === 'submitted' ? 'قيد المراجعة' : 'غير مكتملة'),
                'visited_at' => $completion->started_at?->format('Y-m-d H:i'),
                'completed_at' => $completion->completed_at?->format('Y-m-d H:i'),
            ]);

        return Inertia::render('Admin/TaskActivity/Index', ['activity' => $activity]);
    }
}
