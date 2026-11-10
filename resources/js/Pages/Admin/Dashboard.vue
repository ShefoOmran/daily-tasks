<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';

defineProps({
    admin_name: { type: String, required: true },
    metrics: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <AppLayout>
        <Head title="لوحة التحكم" />
        <div dir="rtl">
            <header>
                <p class="text-sm font-medium text-teal-800">إدارة المنصة</p>
                <h1 class="mt-1 text-3xl font-semibold text-stone-900">لوحة التحكم</h1>
                <p class="mt-2 text-stone-600">مرحبًا، {{ admin_name }}. نظرة عامة على إحصائيات المنصة.</p>
            </header>

            <section class="mt-7 grid gap-4 sm:grid-cols-2" aria-label="إحصائيات المنصة">
                <article class="border-b border-stone-200 bg-white p-5">
                    <p class="text-sm text-stone-500">إجمالي المستخدمين</p>
                    <p class="mt-3 text-3xl font-semibold tabular-nums text-stone-900">{{ metrics.total_users }}</p>
                </article>
                <article class="border-b border-stone-200 bg-white p-5">
                    <p class="text-sm text-stone-500">مستخدمون نشطون خلال آخر 30 يومًا</p>
                    <p class="mt-3 text-3xl font-semibold tabular-nums text-stone-900">{{ metrics.active_users }}</p>
                </article>
                <article class="border-b border-stone-200 bg-white p-5">
                    <p class="text-sm text-stone-500">مهام مكتملة اليوم</p>
                    <p class="mt-3 text-3xl font-semibold tabular-nums text-stone-900">{{ metrics.completed_tasks_today }}</p>
                </article>
                <article class="border-b border-stone-200 bg-white p-5">
                    <p class="text-sm text-stone-500">إجمالي أرصدة المستخدمين</p>
                    <p class="mt-3 text-3xl font-semibold tabular-nums text-stone-900">{{ metrics.total_balance }}</p>
                </article>
            </section>

            <section class="mt-9">
                <h2 class="text-lg font-semibold text-stone-900">إجراءات الإدارة</h2>
                <div class="mt-3 divide-y divide-stone-200 border-y border-stone-200">
                    <Link href="/admin/users" class="flex items-center justify-between gap-4 py-4 hover:text-teal-800">
                        <span>
                            <span class="block font-medium">إدارة المستخدمين</span>
                            <span class="mt-1 block text-sm text-stone-500">عرض الأعضاء وتغيير اشتراكاتهم</span>
                        </span>
                        <span aria-hidden="true" class="text-xl text-stone-400">‹</span>
                    </Link>
                    <Link href="/admin/tasks" class="flex items-center justify-between gap-4 py-4 hover:text-teal-800">
                        <span>
                            <span class="block font-medium">إدارة المهام</span>
                            <span class="mt-1 block text-sm text-stone-500">إضافة المهام وتعديلها وتحديد وقت تنفيذها</span>
                        </span>
                        <span aria-hidden="true" class="text-xl text-stone-400">‹</span>
                    </Link>
                    <Link href="/admin/task-activity" class="flex items-center justify-between gap-4 py-4 hover:text-teal-800">
                        <span>
                            <span class="block font-medium">متابعة تنفيذ المهام</span>
                            <span class="mt-1 block text-sm text-stone-500">عرض سجل انتقال المستخدمين وروابط المهام</span>
                        </span>
                        <span aria-hidden="true" class="text-xl text-stone-400">‹</span>
                    </Link>
                    <Link href="/subscriptions" class="flex items-center justify-between gap-4 py-4 hover:text-teal-800">
                        <span>
                            <span class="block font-medium">إدارة الاشتراكات</span>
                            <span class="mt-1 block text-sm text-stone-500">عرض الخطط وإضافة خطة جديدة</span>
                        </span>
                        <span aria-hidden="true" class="text-xl text-stone-400">‹</span>
                    </Link>
                    <Link href="/balance" class="flex items-center justify-between gap-4 py-4 hover:text-teal-800">
                        <span>
                            <span class="block font-medium">إعدادات الدفع والوكلاء</span>
                            <span class="mt-1 block text-sm text-stone-500">إضافة طرق دفع ووكلاء جدد</span>
                        </span>
                        <span aria-hidden="true" class="text-xl text-stone-400">‹</span>
                    </Link>
                </div>
            </section>
        </div>
    </AppLayout>
</template>