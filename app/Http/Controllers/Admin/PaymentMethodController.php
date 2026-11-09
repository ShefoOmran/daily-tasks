<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use App\TransactionType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaymentMethodController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', Rule::in([
                TransactionType::Deposit->value,
                TransactionType::Withdrawal->value,
            ])],
            'details' => ['required', 'string', 'max:1000'],
        ]);

        PaymentMethod::query()->create([
            'name' => $data['name'],
            'type' => $data['type'],
            'details' => $data['details'],
            'is_active' => true,
        ]);

        return redirect()->route('balance')->with('success', 'تمت إضافة طريقة الدفع.');
    }
}
