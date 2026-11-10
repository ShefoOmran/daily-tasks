<?php

use App\Http\Controllers\Admin\AgentController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\PaymentMethodController;
use App\Http\Controllers\Admin\SubscriptionController as AdminSubscriptionController;
use App\Http\Controllers\Admin\TaskActivityController;
use App\Http\Controllers\Admin\TaskController as AdminTaskController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\BalanceController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepositController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\TaskCompletionController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\WithdrawalController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/tasks/{task}/visit', TaskCompletionController::class)->middleware('member')->name('tasks.visit');
    Route::get('/tasks/{task}', [TaskController::class, 'show'])->middleware('member')->name('tasks.show');
    Route::get('/balance', [BalanceController::class, 'index'])->name('balance');
    Route::post('/deposits', DepositController::class)->middleware('member')->name('deposits.store');
    Route::post('/withdrawals', WithdrawalController::class)->middleware('member')->name('withdrawals.store');
    Route::get('/subscriptions', [SubscriptionController::class, 'index'])->name('subscriptions');
    Route::post('/subscriptions', [SubscriptionController::class, 'store'])->middleware('member');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::middleware('admin')->group(function () {
        Route::get('/admin', AdminDashboardController::class)->name('admin.dashboard');
        Route::get('/admin/tasks', [AdminTaskController::class, 'index'])->name('admin.tasks.index');
        Route::post('/admin/tasks', [AdminTaskController::class, 'store'])->name('admin.tasks.store');
        Route::put('/admin/tasks/{task}', [AdminTaskController::class, 'update'])->name('admin.tasks.update');
        Route::delete('/admin/tasks/{task}', [AdminTaskController::class, 'destroy'])->name('admin.tasks.destroy');
        Route::get('/admin/task-activity', [TaskActivityController::class, 'index'])->name('admin.task-activity.index');
        Route::get('/admin/users', [AdminUserController::class, 'index'])->name('admin.users.index');
        Route::put('/admin/users/{user}/subscription', [AdminUserController::class, 'updateSubscription'])->name('admin.users.subscription.update');
        Route::post('/admin/subscriptions', AdminSubscriptionController::class)->name('admin.subscriptions.store');
        Route::put('/admin/subscriptions/{subscription}', [AdminSubscriptionController::class, 'update'])->name('admin.subscriptions.update');
        Route::delete('/admin/subscriptions/{subscription}', [AdminSubscriptionController::class, 'destroy'])->name('admin.subscriptions.destroy');
        Route::post('/admin/payment-methods', PaymentMethodController::class)->name('admin.payment-methods.store');
        Route::post('/admin/agents', AgentController::class)->name('admin.agents.store');
    });
});
