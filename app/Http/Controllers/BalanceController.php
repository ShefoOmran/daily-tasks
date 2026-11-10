<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\PaymentMethod;
use App\TransactionType;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BalanceController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        if ($user->is_admin) {
            return Inertia::render('Balance/Index', [
                'is_admin' => true,
                'balance' => null,
                'methods' => $this->paymentMethods(),
                'agents' => Agent::query()
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->get(['id', 'name', 'phone', 'note']),
                'transactions' => [],
            ]);
        }

        return Inertia::render('Balance/Index', [
            'is_admin' => false,
            'balance' => number_format((float) $user->balance, 2, '.', ''),
            'methods' => $this->paymentMethods(),
            'agents' => Agent::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'phone', 'note']),
            'transactions' => $user->balanceTransactions()
                ->with('paymentMethod')
                ->latest()
                ->limit(15)
                ->get()
                ->map(fn ($transaction): array => [
                    'id' => $transaction->id,
                    'label' => match ($transaction->type) {
                        TransactionType::Deposit => 'إيداع',
                        TransactionType::Withdrawal => 'سحب',
                        TransactionType::Subscription => 'اشتراك',
                    },
                    'amount' => number_format((float) $transaction->amount, 2, '.', ''),
                    'method' => $transaction->paymentMethod?->name,
                    'note' => $transaction->note,
                    'created_at' => $transaction->created_at->timezone(config('app.timezone'))->format('Y-m-d H:i'),
                ]),
        ]);
    }

    private function paymentMethods(): array
    {
        return PaymentMethod::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (PaymentMethod $method): array => [
                'id' => $method->id,
                'name' => $method->name,
                'type' => $method->type->value,
                'details' => $method->details,
            ])
            ->all();
    }
}
