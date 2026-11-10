<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import AppLayout from '../Layouts/AppLayout.vue';

const props = defineProps({
    summary: {
        type: Object,
        required: true,
    },
    tasks: { type: Array, required: true },
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
    const target = props.summary.next_task_at;

    if (! target) {
        return null;
    }

    const diff = new Date(target).getTime() - now.value;

    if (diff <= 0) {
        return null;
    }

    const total = Math.floor(diff / 1000);
    const minutes = String(Math.floor(total / 60)).padStart(2, '0');
    const seconds = String(total % 60).padStart(2, '0');

    return `${minutes}:${seconds}`;
});

</script>

<template>
    <AppLayout>
        <Head title="الرئيسية" />
        <div>
            <p class="text-sm text-stone-500">الخطة الحالية: {{ summary.plan_name }}</p>
            <h1 class="mt-1 text-3xl font-semibold">مهام اليوم</h1>

            <section class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
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
                    <p class="text-sm text-stone-500">الفاصل بين المهام</p>
                    <p class="mt-2 text-3xl font-semibold">{{ summary.duration_minutes }}</p>
                    <p class="mt-1 text-xs text-stone-500">دقيقة حسب اشتراكك</p>
                </article>
            </section>

            <section class="mt-6 rounded-2xl border border-stone-200 bg-white p-6">
                <h2 class="text-lg font-semibold">المهام المتاحة</h2>
                <p v-if="summary.available_tasks === 0" class="mt-2 text-sm text-stone-600">أنهيت مهام اليوم. تعود المهام غداً.</p>
                <p v-else-if="remaining" class="mt-2 text-sm text-stone-600">المهمة التالية متاحة بعد {{ remaining }}</p>
                <p v-else-if="tasks.length === 0" class="mt-2 text-sm text-stone-600">لا توجد مهام متاحة حالياً.</p>
                <p v-else class="mt-2 text-sm text-stone-600">اختر مهمة لعرض التفاصيل ثم الانتقال إلى رابط تنفيذها.</p>
                <div v-if="tasks.length > 0" class="mt-4 grid gap-3 sm:grid-cols-2">
                    <article v-for="task in tasks" :key="task.id" class="border border-stone-200 p-4">
                        <h3 class="font-medium">{{ task.title }}</h3>
                        <p v-if="task.description" class="mt-1 text-sm text-stone-600">{{ task.description }}</p>
                        <p class="mt-2 text-xs text-stone-500">مدة التنفيذ: {{ task.duration_minutes }} دقيقة</p>
                        <p class="mt-1 text-xs text-stone-500">المكافأة: {{ task.reward_amount }}</p>
                        <Link :href="`/tasks/${task.id}`" class="mt-3 inline-flex rounded-lg bg-teal-800 px-4 py-2 text-sm text-white">
                            تفاصيل المهمة
                        </Link>
                    </article>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
