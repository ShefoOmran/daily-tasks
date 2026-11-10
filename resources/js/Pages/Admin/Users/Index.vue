<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    users: { type: Object, required: true },
    subscriptions: { type: Array, required: true },
    filters: { type: Object, required: true },
});

const searchForm = useForm({ search: props.filters.search });

function searchUsers() {
    searchForm.get('/admin/users', { preserveState: true, replace: true });
}

function updateSubscription(user, subscriptionId) {
    router.put(`/admin/users/${user.id}/subscription`, {
        subscription_id: subscriptionId,
    }, { preserveScroll: true });
}
</script>

<template>
    <AppLayout>
        <Head title="إدارة المستخدمين" />
        <div dir="rtl">
            <h1 class="text-3xl font-semibold">إدارة المستخدمين</h1>

            <form class="mt-5 flex max-w-xl gap-2" @submit.prevent="searchUsers">
                <input v-model="searchForm.search" type="search" placeholder="ابحث بالاسم أو البريد الإلكتروني" class="min-w-0 flex-1 rounded-lg border border-stone-300 px-3 py-2" />
                <button type="submit" class="rounded-lg bg-stone-900 px-4 py-2 text-white" :disabled="searchForm.processing">بحث</button>
            </form>

            <div class="mt-6 overflow-x-auto border-y border-stone-200">
                <table class="w-full min-w-[640px] text-sm">
                    <thead class="text-stone-500">
                        <tr>
                            <th class="py-3 text-right font-medium">المستخدم</th>
                            <th class="py-3 text-right font-medium">البريد الإلكتروني</th>
                            <th class="py-3 text-right font-medium">الاشتراك</th>
                            <th class="py-3 text-right font-medium">تغيير الاشتراك</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="user in users.data" :key="user.id" class="border-t border-stone-100">
                            <td class="py-3 font-medium">{{ user.name }}</td>
                            <td class="py-3 text-stone-600">{{ user.email }}</td>
                            <td class="py-3">{{ user.subscription_name }}</td>
                            <td class="py-3">
                                <select
                                    :value="user.subscription_id"
                                    class="min-w-40 rounded-lg border border-stone-300 px-3 py-2"
                                    @change="updateSubscription(user, $event.target.value)"
                                >
                                    <option v-for="subscription in subscriptions" :key="subscription.id" :value="subscription.id">
                                        {{ subscription.name }}
                                    </option>
                                </select>
                            </td>
                        </tr>
                        <tr v-if="users.data.length === 0">
                            <td colspan="4" class="py-8 text-center text-stone-500">لا يوجد مستخدمون مطابقون للبحث.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <nav v-if="users.prev_page_url || users.next_page_url" class="mt-4 flex justify-between text-sm">
                <Link v-if="users.prev_page_url" :href="users.prev_page_url" class="text-teal-800">السابق</Link>
                <span v-else />
                <span class="text-stone-500">صفحة {{ users.current_page }} من {{ users.last_page }}</span>
                <Link v-if="users.next_page_url" :href="users.next_page_url" class="text-teal-800">التالي</Link>
            </nav>
        </div>
    </AppLayout>
</template>