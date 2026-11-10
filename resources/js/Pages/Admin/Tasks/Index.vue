<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    tasks: { type: Array, required: true },
});

const form = useForm({ title: '', description: '', duration_minutes: 1, reward_amount: '', external_url: '', is_active: true });
const deleteForm = useForm({});
const editingTaskId = ref(null);
const deleteTargetId = ref(null);

function saveTask() {
    const options = {
        onSuccess: () => {
            form.reset();
            editingTaskId.value = null;
        },
    };

    if (editingTaskId.value !== null) {
        form.put(`/admin/tasks/${editingTaskId.value}`, options);
        return;
    }

    form.post('/admin/tasks', options);
}

function editTask(task) {
    editingTaskId.value = task.id;
    form.title = task.title;
    form.description = task.description || '';
    form.duration_minutes = task.duration_minutes;
    form.reward_amount = task.reward_amount;
    form.external_url = task.external_url;
    form.is_active = task.is_active;
    form.clearErrors();
}

function cancelEditing() {
    form.reset();
    form.clearErrors();
    editingTaskId.value = null;
}

function deleteTask(task) {
    if (! window.confirm(`هل تريد حذف المهمة «${task.title}»؟`)) {
        return;
    }

    deleteTargetId.value = task.id;
    deleteForm.delete(`/admin/tasks/${task.id}`, {
        onSuccess: () => {
            deleteTargetId.value = null;
        },
    });
}
</script>

<template>
    <AppLayout>
        <Head title="إدارة المهام" />
        <div dir="rtl">
            <h1 class="text-3xl font-semibold">إدارة المهام</h1>

            <form class="mt-6 border-b border-stone-200 pb-6" @submit.prevent="saveTask">
                <h2 class="text-lg font-semibold">{{ editingTaskId ? 'تعديل المهمة' : 'إضافة مهمة' }}</h2>
                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    <label class="text-sm">
                        عنوان المهمة
                        <input v-model="form.title" required maxlength="150" class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2" />
                    </label>
                    <label class="text-sm">
                        مدة التنفيذ (دقيقة)
                        <input v-model="form.duration_minutes" required type="number" min="1" max="1440" class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2" />
                    </label>
                    <label class="text-sm">
                        مكافأة المهمة
                        <input v-model="form.reward_amount" required type="number" min="0.01" max="10000" step="0.01" class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2" />
                    </label>
                    <label class="text-sm sm:col-span-2">
                        رابط تنفيذ المهمة
                        <input v-model="form.external_url" required type="url" maxlength="2048" placeholder="https://example.com/task" class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2" />
                    </label>
                    <label class="text-sm sm:col-span-2">
                        وصف المهمة
                        <textarea v-model="form.description" rows="3" maxlength="2000" class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2" />
                    </label>
                    <label class="flex items-center gap-2 text-sm">
                        <input v-model="form.is_active" type="checkbox" />
                        متاحة للمستخدمين
                    </label>
                </div>
                <p v-for="(error, field) in form.errors" :key="field" class="mt-2 text-sm text-red-700">{{ error }}</p>
                <div class="mt-4 flex gap-2">
                    <button type="submit" class="rounded-lg bg-teal-800 px-4 py-2 text-white disabled:opacity-50" :disabled="form.processing">
                        {{ editingTaskId ? 'حفظ التعديلات' : 'إضافة المهمة' }}
                    </button>
                    <button v-if="editingTaskId" type="button" class="rounded-lg border border-stone-300 px-4 py-2" @click="cancelEditing">
                        إلغاء
                    </button>
                </div>
            </form>

            <section class="mt-6">
                <p v-if="tasks.length === 0" class="py-6 text-sm text-stone-500">لم تتم إضافة مهام بعد.</p>
                <div v-else class="divide-y divide-stone-200">
                    <article v-for="task in tasks" :key="task.id" class="flex flex-wrap items-start justify-between gap-4 py-5">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="font-semibold">{{ task.title }}</h2>
                                <span class="text-xs" :class="task.is_active ? 'text-teal-800' : 'text-stone-500'">
                                    {{ task.is_active ? 'متاحة' : 'متوقفة' }}
                                </span>
                            </div>
                            <p v-if="task.description" class="mt-1 whitespace-pre-line text-sm text-stone-600">{{ task.description }}</p>
                            <p class="mt-2 text-xs text-stone-500">مدة التنفيذ: {{ task.duration_minutes }} دقيقة</p>
                            <p class="mt-1 text-xs text-stone-500">المكافأة: {{ task.reward_amount }}</p>
                            <a :href="task.external_url" target="_blank" rel="noopener noreferrer" class="mt-1 block break-all text-xs text-teal-800">{{ task.external_url }}</a>
                        </div>
                        <div class="flex gap-2">
                            <button type="button" class="rounded-lg border border-stone-300 px-3 py-2 text-sm" @click="editTask(task)">تعديل</button>
                            <button type="button" class="rounded-lg border border-red-200 px-3 py-2 text-sm text-red-700 disabled:opacity-50" :disabled="deleteForm.processing" @click="deleteTask(task)">حذف</button>
                        </div>
                        <p v-if="deleteTargetId === task.id && deleteForm.errors.task" class="w-full text-sm text-red-700">
                            {{ deleteForm.errors.task }}
                        </p>
                    </article>
                </div>
            </section>
        </div>
    </AppLayout>
</template>