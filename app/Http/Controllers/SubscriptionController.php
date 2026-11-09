<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Models\User;
use App\TransactionType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $freePlan = Subscription::freePlan();

        return Inertia::render('Subscriptions/Index', [
            'balance' => number_format((float) $user->balance, 2, '.', ''),
            'current_subscription_id' => $user->subscription_id ?? $freePlan->id,
            'subscriptions' => Subscription::query()
                ->orderBy('price')
                ->orderBy('id')
                ->get()
                ->map(fn (Subscription $subscription): array => [
                    'id' => $subscription->id,
                    'name' => $subscription->name,
                    'price' => number_format((float) $subscription->price, 2, '.', ''),
                    'daily_task_limit' => $subscription->daily_task_limit,
                    'reward_amount' => number_format((float) $subscription->reward_amount, 2, '.', ''),
                    'duration_minutes' => $subscription->duration_minutes,
                    'is_free' => $subscription->is_free,
                ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'subscription_id' => ['required', 'integer', 'exists:subscriptions,id'],
        ]);

        DB::transaction(function () use ($request, $data): void {
            $user = User::query()->whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            $subscription = Subscription::query()->findOrFail($data['subscription_id']);
            $currentId = $user->subscription_id ?? Subscription::freePlan()->id;

            if ($currentId === $subscription->id) {
                throw ValidationException::withMessages([
                    'subscription_id' => 'أنت مشترك في هذه الخطة بالفعل.',
                ]);
            }

            if ($this->cents($user->balance) < $this->cents($subscription->price)) {
                throw ValidationException::withMessages([
                    'subscription_id' => 'الرصيد لا يكفي لهذا الاشتراك.',
                ]);
            }

            if ($this->cents($subscription->price) > 0) {
                $user->decrement('balance', $subscription->price);
                $user->balanceTransactions()->create([
                    'type' => TransactionType::Subscription,
                    'amount' => $subscription->price,
                    'note' => $subscription->name,
                ]);
            }

            $user->update(['subscription_id' => $subscription->id]);
        });

        return redirect()->route('subscriptions')->with('success', 'تم تفعيل الاشتراك.');
    }

    private function cents(mixed $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }
}
