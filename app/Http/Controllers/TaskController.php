<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TaskController extends Controller
{
    public function show(Request $request, Task $task): Response
    {
        abort_unless($task->is_active && filled($task->external_url), 404);

        $user = $request->user();
        $plan = $user->plan();

        return Inertia::render('Tasks/Show', [
            'task' => [
                'id' => $task->id,
                'title' => $task->title,
                'description' => $task->description,
                'duration_minutes' => $task->duration_minutes,
                'reward_amount' => number_format((float) $task->reward_amount, 2, '.', ''),
                'external_url' => $task->external_url,
            ],
            'plan' => [
                'name' => $plan->name,
                'interval_minutes' => $plan->duration_minutes,
            ],
            'available_tasks' => $user->availableTaskCount(),
            'next_task_at' => $user->nextTaskAvailableAt()?->toIso8601String(),
        ]);
    }
}
