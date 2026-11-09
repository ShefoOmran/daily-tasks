<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import AppLayout from '../Layouts/AppLayout.vue';

const props = defineProps({
    summary: {
        type: Object,
        required: true,
    },
});

const now = ref(Date.now());
let timer;

onMounted(() => {
    timer = setInterval(() => {
        now.value = Date.now();
    }, 1000);
});

onUnmounted(() => clearInterval(timer));

const remaining = computed(() => {
    if (! props.summary.next_task_at) {
        return null;
    }

    const diff = new Date(props.summary.next_task_at).getTime() - now.value;

    if (diff <= 0) {
        return null;
    }

    const total = Math.floor(diff / 1000);
    const minutes = String(Math.floor(total / 60)).padStart(2, '0');
    const seconds = String(total % 60).padStart(2, '0');

    return `${minutes}:${seconds}`;
});

const form = useForm({});

function completeTask() {
    form.post('/tasks');
}
</script>

<template>
    <AppLayout>
        <Head title="الرئيسية" />
        <div>
            <p class="text-sm text-stone-500">الخطة الحالية: {{ summary.plan_name }}</p>
            <h1 class="mt-1 text-3xl font-semibold">مهام اليوم</h1>

            <section class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <article class="rounded-2xl border border-stone-200 bg-white p-5">
                    <p class="text-sm text-stone-500">المهام المتاحة</p>
                    <p class="mt-2 text-3xl font-semibold text-teal-800">{{ summary.available_tasks }}</p>
                    <p class="mt-1 text-xs text-stone-500">من {{ summary.daily_task_limit }} يومياً</p>
                </article>
                <article class="rounded-2xl border border-stone-200 bg-white p-5">
                    <p class="text-sm text-stone-500">الرصيد</p>
                    <p class="mt-2 text-3xl font-semibold">{{ summary.balance }}</p>
                </article>
                <article class="rounded-2xl border border-stone-200 bg-white p-5">
                    <p class="text-sm text-stone-500">ربح كل مهمة</p>
                    <p class="mt-2 text-3xl font-semibold">{{ summary.reward_amount }}</p>
                </article>
                <article class="rounded-2xl border border-stone-200 bg-white p-5">
                    <p class="text-sm text-stone-500">وقت المهمة</p>
                    <p class="mt-2 text-3xl font-semibold">{{ summary.duration_minutes }}</p>
                    <p class="mt-1 text-xs text-stone-500">دقيقة بين كل مهمة</p>
                </article>
            </section>

            <section class="mt-6 rounded-2xl border border-stone-200 bg-white p-6">
                <h2 class="text-lg font-semibold">تنفيذ مهمة</h2>
                <p v-if="remaining" class="mt-2 text-sm text-stone-600">المهمة التالية بعد {{ remaining }}</p>
                <p v-else-if="summary.available_tasks === 0" class="mt-2 text-sm text-stone-600">أنهيت مهام اليوم. تعود المهام غداً.</p>
                <p v-else class="mt-2 text-sm text-stone-600">يمكنك إنجاز مهمة الآن وإضافة ربحها إلى الرصيد.</p>
                <p v-if="form.errors.task" class="mt-3 text-sm text-red-700">{{ form.errors.task }}</p>
                <button
                    type="button"
                    class="mt-4 rounded-lg bg-teal-800 px-4 py-2.5 font-medium text-white disabled:opacity-50"
                    :disabled="form.processing || remaining !== null || summary.available_tasks === 0"
                    @click="completeTask"
                >
                    إنجاز المهمة
                </button>
            </section>
        </div>
    </AppLayout>
</template>
