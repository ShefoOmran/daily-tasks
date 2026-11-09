<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';

defineProps({
    balance: { type: String, required: true },
    current_subscription_id: { type: Number, required: true },
    subscriptions: { type: Array, required: true },
});

const form = useForm({ subscription_id: null });
const adminForm = useForm({
    name: '',
    price: '',
    daily_task_limit: 5,
    reward_amount: '',
    duration_minutes: 30,
    is_free: false,
});

function subscribe(id) {
    form.subscription_id = id;
    form.post('/subscriptions');
}

function addPlan() {
    adminForm.post('/admin/subscriptions', {
        onSuccess: () => adminForm.reset(),
    });
}
</script>

<template>
    <AppLayout>
        <Head title="الاشتراكات" />
        <div>
            <h1 class="text-3xl font-semibold">الاشتراكات</h1>
            <p class="mt-2 text-stone-600">رصيدك الحالي {{ balance }}. التفعيل يخصم سعر الخطة من الرصيد.</p>
            <p v-if="form.errors.subscription_id" class="mt-3 text-sm text-red-700">{{ form.errors.subscription_id }}</p>

            <section class="mt-6 grid gap-4 md:grid-cols-3">
                <article
                    v-for="plan in subscriptions"
                    :key="plan.id"
                    class="rounded-2xl border bg-white p-5"
                    :class="plan.id === current_subscription_id ? 'border-teal-700' : 'border-stone-200'"
                >
                    <p class="text-sm text-teal-800">{{ plan.is_free ? 'مجانية' : 'مدفوعة' }}</p>
                    <h2 class="mt-1 text-xl font-semibold">{{ plan.name }}</h2>
                    <p class="mt-3 text-2xl font-semibold">{{ plan.price }}</p>
                    <ul class="mt-4 space-y-1 text-sm text-stone-600">
                        <li>{{ plan.daily_task_limit }} مهمة يومياً</li>
                        <li>ربح المهمة {{ plan.reward_amount }}</li>
                        <li>الوقت بين المهام {{ plan.duration_minutes }} دقيقة</li>
                    </ul>
                    <button
                        type="button"
                        class="mt-5 w-full rounded-lg bg-stone-900 px-4 py-2 text-sm text-white disabled:opacity-50"
                        :disabled="form.processing || plan.id === current_subscription_id"
                        @click="subscribe(plan.id)"
                    >
                        {{ plan.id === current_subscription_id ? 'خطتك الحالية' : 'تفعيل' }}
                    </button>
                </article>
            </section>

            <form v-if="$page.props.auth.user?.is_admin" class="mt-8 rounded-2xl border border-dashed border-teal-300 bg-white p-5" @submit.prevent="addPlan">
                <h2 class="text-lg font-semibold">إضافة اشتراك</h2>
                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    <input v-model="adminForm.name" required placeholder="اسم الخطة" class="rounded-lg border border-stone-300 px-3 py-2" />
                    <input v-model="adminForm.price" required type="number" min="0" step="0.01" placeholder="السعر" class="rounded-lg border border-stone-300 px-3 py-2" />
                    <input v-model="adminForm.daily_task_limit" required type="number" min="1" placeholder="عدد المهام" class="rounded-lg border border-stone-300 px-3 py-2" />
                    <input v-model="adminForm.reward_amount" required type="number" min="0.01" step="0.01" placeholder="ربح المهمة" class="rounded-lg border border-stone-300 px-3 py-2" />
                    <input v-model="adminForm.duration_minutes" required type="number" min="1" placeholder="المدة بالدقائق" class="rounded-lg border border-stone-300 px-3 py-2" />
                    <label class="flex items-center gap-2 text-sm">
                        <input v-model="adminForm.is_free" type="checkbox" />
                        خطة مجانية
                    </label>
                </div>
                <p v-if="adminForm.errors.is_free" class="mt-2 text-sm text-red-700">{{ adminForm.errors.is_free }}</p>
                <button type="submit" class="mt-4 rounded-lg bg-teal-800 px-4 py-2 text-white" :disabled="adminForm.processing">إضافة الاشتراك</button>
            </form>
        </div>
    </AppLayout>
</template>
