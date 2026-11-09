<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TaskCompletion;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $members = User::query()->where('is_admin', false);

        return Inertia::render('Admin/Dashboard', [
            'admin_name' => $request->user()->name,
            'metrics' => [
                'total_users' => (clone $members)->count(),
                'active_users' => (clone $members)
                    ->whereHas('taskCompletions', function (Builder $query): void {
                        $query->where('completed_at', '>=', now()->subDays(30));
                    })
                    ->count(),
                'completed_tasks_today' => TaskCompletion::query()
                    ->whereDate('completed_at', today())
                    ->whereHas('user', function (Builder $query): void {
                        $query->where('is_admin', false);
                    })
                    ->count(),
                'total_balance' => number_format((float) (clone $members)->sum('balance'), 2, '.', ''),
            ],
        ]);
    }
}
