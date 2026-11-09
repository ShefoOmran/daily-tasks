<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AgentController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:30'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        Agent::query()->create([
            'name' => $data['name'],
            'phone' => $data['phone'],
            'note' => $data['note'] ?? null,
            'is_active' => true,
        ]);

        return redirect()->route('balance')->with('success', 'تمت إضافة الوكيل.');
    }
}
