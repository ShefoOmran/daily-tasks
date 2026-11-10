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
        $data = $this->validatedPlan($request);

        $isFree = $request->boolean('is_free');

        if ($isFree && Subscription::query()->where('is_free', true)->exists()) {
            throw ValidationException::withMessages([
                'is_free' => 'توجد خطة مجانية بالفعل.',
            ]);
        }

        Subscription::query()->create([...$data, 'is_free' => $isFree]);

        return redirect()->route('subscriptions')->with('success', 'تمت إضافة الاشتراك.');
    }

    public function update(Request $request, Subscription $subscription): RedirectResponse
    {
        $data = $this->validatedPlan($request);
        $isFree = $request->boolean('is_free');

        $otherFreePlanExists = Subscription::query()
            ->where('is_free', true)
            ->whereKeyNot($subscription->id)
            ->exists();

        if ($isFree && $otherFreePlanExists) {
            throw ValidationException::withMessages([
                'is_free' => 'توجد خطة مجانية بالفعل.',
            ]);
        }

        if (! $isFree && $subscription->is_free && ! $otherFreePlanExists) {
            throw ValidationException::withMessages([
                'is_free' => 'لا يمكن إلغاء مجانية الخطة الوحيدة.',
            ]);
        }

        $subscription->update([...$data, 'is_free' => $isFree]);

        return redirect()->route('subscriptions')->with('success', 'تم تحديث الاشتراك.');
    }

    public function destroy(Subscription $subscription): RedirectResponse
    {
        if ($subscription->is_free) {
            throw ValidationException::withMessages([
                'subscription' => 'لا يمكن حذف الخطة المجانية.',
            ]);
        }

        if ($subscription->users()->exists()) {
            throw ValidationException::withMessages([
                'subscription' => 'لا يمكن حذف خطة عليها مستخدمون مشتركين.',
            ]);
        }

        $subscription->delete();

        return redirect()->route('subscriptions')->with('success', 'تم حذف الاشتراك.');
    }

    /**
     * @return array{name: string, price: numeric-string, daily_task_limit: int, reward_amount: numeric-string, duration_minutes: int}
     */
    private function validatedPlan(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'price' => ['required', 'numeric', 'min:0', 'max:100000'],
            'daily_task_limit' => ['required', 'integer', 'min:1', 'max:500'],
            'reward_amount' => ['required', 'numeric', 'min:0.01', 'max:10000'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
        ]);
    }
}
