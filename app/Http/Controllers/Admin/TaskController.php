<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class TaskController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Tasks/Index', [
            'tasks' => Task::query()->orderBy('id')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Task::query()->create($this->validatedTask($request));

        return redirect()->route('admin.tasks.index')->with('success', 'تمت إضافة المهمة.');
    }

    public function update(Request $request, Task $task): RedirectResponse
    {
        $task->update($this->validatedTask($request));

        return redirect()->route('admin.tasks.index')->with('success', 'تم تحديث المهمة.');
    }

    public function destroy(Task $task): RedirectResponse
    {
        if ($task->completions()->exists()) {
            throw ValidationException::withMessages([
                'task' => 'لا يمكن حذف مهمة لها سجل تنفيذ. أوقف إتاحتها بدلاً من ذلك.',
            ]);
        }

        $task->delete();

        return redirect()->route('admin.tasks.index')->with('success', 'تم حذف المهمة.');
    }

    /**
     * @return array{title: string, description: ?string, duration_minutes: int, reward_amount: string|int|float, is_active: bool}
     */
    private function validatedTask(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'reward_amount' => ['required', 'numeric', 'min:0.01', 'max:10000'],
            'external_url' => ['required', 'string', 'max:2048', 'url:http,https'],
            'is_active' => ['required', 'boolean'],
        ]);

        return [
            ...$data,
            'description' => $data['description'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ];
    }
}
