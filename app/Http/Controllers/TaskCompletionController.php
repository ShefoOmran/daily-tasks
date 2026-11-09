<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TaskCompletionController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $request->user()->completeAvailableTask();

        return redirect()->route('dashboard')->with('success', 'تم إنجاز المهمة وإضافة الربح إلى الرصيد.');
    }
}
