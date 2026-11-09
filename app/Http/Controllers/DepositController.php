<?php

namespace App\Http\Controllers;

use App\Models\PaymentMethod;
use App\Models\User;
use App\TransactionType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DepositController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1', 'max:100000'],
            'payment_method_id' => ['required', 'integer'],
        ]);

        $method = $this->activeMethod((int) $data['payment_method_id'], TransactionType::Deposit);

        DB::transaction(function () use ($request, $data, $method): void {
            $user = User::query()->whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            $user->increment('balance', $data['amount']);
            $user->balanceTransactions()->create([
                'type' => TransactionType::Deposit,
                'amount' => $data['amount'],
                'payment_method_id' => $method->id,
            ]);
        });

        return redirect()->route('balance')->with('success', 'تم تسجيل الإيداع وإضافة المبلغ إلى الرصيد.');
    }

    private function activeMethod(int $id, TransactionType $type): PaymentMethod
    {
        $method = PaymentMethod::query()
            ->whereKey($id)
            ->where('type', $type)
            ->where('is_active', true)
            ->first();

        if ($method === null) {
            throw ValidationException::withMessages([
                'payment_method_id' => 'اختر طريقة متاحة.',
            ]);
        }

        return $method;
    }
}
