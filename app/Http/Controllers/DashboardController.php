<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if ($user->is_admin) {
            return redirect()->route('admin.dashboard');
        }

        $user->load('subscription');
        $plan = $user->plan();

        return Inertia::render('Dashboard', [
            'summary' => [
                'available_tasks' => $user->availableTaskCount(),
                'daily_task_limit' => $plan->daily_task_limit,
                'balance' => number_format((float) $user->balance, 2, '.', ''),
                'duration_minutes' => $plan->duration_minutes,
                'next_task_at' => $user->nextTaskAvailableAt()?->toIso8601String(),
                'plan_name' => $plan->name,
            ],
            'tasks' => Task::query()
                ->where('is_active', true)
                ->whereNotNull('external_url')
                ->where('external_url', '!=', '')
                ->orderBy('id')
                ->get(['id', 'title', 'description', 'duration_minutes', 'reward_amount']),
        ]);
    }
}
