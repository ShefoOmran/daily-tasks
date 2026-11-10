<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class TaskCompletionController extends Controller
{
    public function __invoke(Request $request, Task $task): Response
    {
        $request->user()->visitTask($task);

        return Inertia::location($task->external_url);
    }
}
