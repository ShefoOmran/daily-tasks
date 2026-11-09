<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SubscriptionController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'price' => ['required', 'numeric', 'min:0', 'max:100000'],
            'daily_task_limit' => ['required', 'integer', 'min:1', 'max:500'],
            'reward_amount' => ['required', 'numeric', 'min:0.01', 'max:10000'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
        ]);

        $isFree = $request->boolean('is_free');

        if ($isFree && Subscription::query()->where('is_free', true)->exists()) {
            throw ValidationException::withMessages([
                'is_free' => 'توجد خطة مجانية بالفعل.',
            ]);
        }

        Subscription::query()->create([
            'name' => $data['name'],
            'price' => $data['price'],
            'daily_task_limit' => $data['daily_task_limit'],
            'reward_amount' => $data['reward_amount'],
            'duration_minutes' => $data['duration_minutes'],
            'is_free' => $isFree,
        ]);

        return redirect()->route('subscriptions')->with('success', 'تمت إضافة الاشتراك.');
    }
}
