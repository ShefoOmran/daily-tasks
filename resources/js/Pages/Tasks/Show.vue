<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import AppLayout from '../../Layouts/AppLayout.vue';

const props = defineProps({
    task: { type: Object, required: true },
    plan: { type: Object, required: true },
    available_tasks: { type: Number, required: true },
    next_task_at: { type: String, default: null },
});

const now = ref(Date.now());
const visitForm = useForm({});
let timer;

onMounted(() => {
    timer = setInterval(() => {
        now.value = Date.now();
    }, 1000);
});

onUnmounted(() => clearInterval(timer));

const remaining = computed(() => {
    if (!props.next_task_at) {
        return null;
    }

    const difference = new Date(props.next_task_at).getTime() - now.value;

    if (difference <= 0) {
        return null;
    }

    const total = Math.floor(difference / 1000);
    const minutes = String(Math.floor(total / 60)).padStart(2, '0');
    const seconds = String(total % 60).padStart(2, '0');

    return `${minutes}:${seconds}`;
});

function visitTask() {
    visitForm.post(`/tasks/${props.task.id}/visit`);
}
</script>

<template>
    <AppLayout>
        <Head :title="task.title" />
        <div dir="rtl" class="max-w-3xl">
            <Link href="/dashboard" class="text-sm text-teal-800">العودة إلى المهام</Link>
            <header class="mt-5 border-b border-stone-200 pb-5">
                <p class="text-sm text-stone-500">تفاصيل المهمة</p>
                <h1 class="mt-1 text-3xl font-semibold">{{ task.title }}</h1>
                <p v-if="task.description" class="mt-3 whitespace-pre-line leading-7 text-stone-700">{{ task.description }}</p>
            </header>

            <section class="mt-5 grid gap-4 sm:grid-cols-3">
                <article class="border-b border-stone-200 p-4">
                    <p class="text-sm text-stone-500">مدة التنفيذ</p>
                    <p class="mt-2 font-semibold">{{ task.duration_minutes }} دقيقة</p>
                </article>
                <article class="border-b border-stone-200 p-4">
                    <p class="text-sm text-stone-500">مكافأة تنفيذ المهمة</p>
                    <p class="mt-2 font-semibold">{{ task.reward_amount }}</p>
                    <p class="mt-1 text-xs text-stone-500">تضاف عند الانتقال للرابط</p>
                </article>
                <article class="border-b border-stone-200 p-4">
                    <p class="text-sm text-stone-500">الفاصل حسب الاشتراك</p>
                    <p class="mt-2 font-semibold">{{ plan.interval_minutes }} دقيقة</p>
                </article>
            </section>

            <section class="mt-7 space-y-3">
                <p class="text-sm text-stone-600">عند تنفيذ المهمة، اضغط الزر لتسجيل المكافأة والانتقال إلى الرابط الخارجي.</p>
                <p class="break-all text-xs text-stone-500">رابط التنفيذ: {{ task.external_url }}</p>
                <p v-if="remaining" class="text-sm text-stone-600">المهمة التالية متاحة بعد {{ remaining }}</p>
                <p v-else-if="available_tasks === 0" class="text-sm text-stone-600">وصلت إلى حد المهام اليومي.</p>
                <p v-if="visitForm.errors.task" class="text-sm text-red-700">{{ visitForm.errors.task }}</p>
                <button
                    type="button"
                    class="rounded-lg bg-teal-800 px-4 py-2.5 font-medium text-white disabled:opacity-50"
                    :disabled="visitForm.processing || remaining !== null || available_tasks === 0"
                    @click="visitTask"
                >
                    تنفيذ المهمة والانتقال إلى الرابط
                </button>
            </section>
        </div>
    </AppLayout>
</template>