<?php

namespace App\Http\Controllers;

use App\Models\PaymentMethod;
use App\Models\User;
use App\TransactionType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WithdrawalController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1', 'max:100000'],
            'payment_method_id' => ['required', 'integer'],
        ]);

        $method = PaymentMethod::query()
            ->whereKey($data['payment_method_id'])
            ->where('type', TransactionType::Withdrawal)
            ->where('is_active', true)
            ->first();

        if ($method === null) {
            throw ValidationException::withMessages([
                'payment_method_id' => 'اختر طريقة سحب متاحة.',
            ]);
        }

        DB::transaction(function () use ($request, $data, $method): void {
            $user = User::query()->whereKey($request->user()->id)->lockForUpdate()->firstOrFail();

            if ($this->cents($user->balance) < $this->cents($data['amount'])) {
                throw ValidationException::withMessages([
                    'amount' => 'الرصيد غير كافٍ للسحب.',
                ]);
            }

            $user->decrement('balance', $data['amount']);
            $user->balanceTransactions()->create([
                'type' => TransactionType::Withdrawal,
                'amount' => $data['amount'],
                'payment_method_id' => $method->id,
            ]);
        });

        return redirect()->route('balance')->with('success', 'تم تسجيل السحب وخصم المبلغ من الرصيد.');
    }

    private function cents(mixed $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }
}
