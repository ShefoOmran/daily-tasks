<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim()->value();
        $freePlan = Subscription::freePlan();

        $users = User::query()
            ->where('is_admin', false)
            ->with('subscription')
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'subscription_id' => $user->subscription_id ?? $freePlan->id,
                'subscription_name' => $user->subscription?->name ?? $freePlan->name,
            ]);

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'subscriptions' => Subscription::query()->orderBy('price')->get(['id', 'name', 'price']),
            'filters' => ['search' => $search],
        ]);
    }

    public function updateSubscription(Request $request, User $user): RedirectResponse
    {
        if ($user->is_admin) {
            throw new NotFoundHttpException;
        }

        $data = $request->validate([
            'subscription_id' => ['required', 'integer', 'exists:subscriptions,id'],
        ]);

        $user->update(['subscription_id' => $data['subscription_id']]);

        return redirect()->route('admin.users.index')->with('success', 'تم تحديث اشتراك المستخدم.');
    }
}
