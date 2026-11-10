<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '../../../Layouts/AppLayout.vue';

defineProps({
    activity: { type: Object, required: true },
});
</script>

<template>
    <AppLayout>
        <Head title="متابعة تنفيذ المهام" />
        <div dir="rtl">
            <h1 class="text-3xl font-semibold">متابعة تنفيذ المهام</h1>

            <p v-if="activity.data.length === 0" class="mt-7 border-y border-stone-200 py-8 text-center text-stone-500">
                لا توجد عمليات تنفيذ حتى الآن.
            </p>

            <section v-else class="mt-6 overflow-x-auto border-y border-stone-200">
                <table class="w-full min-w-[720px] text-sm">
                    <thead class="text-stone-500">
                        <tr>
                            <th class="py-3 text-right font-medium">المستخدم</th>
                            <th class="py-3 text-right font-medium">المهمة</th>
                            <th class="py-3 text-right font-medium">المكافأة</th>
                            <th class="py-3 text-right font-medium">الحالة</th>
                            <th class="py-3 text-right font-medium">وقت الانتقال</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="entry in activity.data" :key="entry.id" class="border-t border-stone-100">
                            <td class="py-3">
                                <span class="block font-medium">{{ entry.member_name }}</span>
                                <span class="text-xs text-stone-500">{{ entry.member_email }}</span>
                            </td>
                            <td class="py-3">{{ entry.task_title }}</td>
                            <td class="py-3 tabular-nums">{{ entry.reward_amount }}</td>
                            <td class="py-3">{{ entry.status_label }}</td>
                            <td class="py-3 text-stone-600">{{ entry.visited_at || '—' }}</td>
                        </tr>
                    </tbody>
                </table>
            </section>

            <nav v-if="activity.prev_page_url || activity.next_page_url" class="mt-4 flex justify-between text-sm">
                <Link v-if="activity.prev_page_url" :href="activity.prev_page_url" class="text-teal-800">السابق</Link>
                <span v-else />
                <span class="text-stone-500">صفحة {{ activity.current_page }} من {{ activity.last_page }}</span>
                <Link v-if="activity.next_page_url" :href="activity.next_page_url" class="text-teal-800">التالي</Link>
            </nav>
        </div>
    </AppLayout>
</template>