<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $user->load('subscription');
        $plan = $user->plan();

        return Inertia::render('Dashboard', [
            'summary' => [
                'available_tasks' => $user->availableTaskCount(),
                'daily_task_limit' => $plan->daily_task_limit,
                'balance' => number_format((float) $user->balance, 2, '.', ''),
                'reward_amount' => number_format((float) $plan->reward_amount, 2, '.', ''),
                'duration_minutes' => $plan->duration_minutes,
                'next_task_at' => $user->nextTaskAvailableAt()?->toIso8601String(),
                'plan_name' => $plan->name,
            ],
        ]);
    }
}
