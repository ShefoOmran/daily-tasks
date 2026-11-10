<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '../../Layouts/AppLayout.vue';

defineProps({
    is_admin: { type: Boolean, required: true },
    balance: { type: String, default: null },
    current_subscription_id: { type: Number, default: null },
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
const deleteForm = useForm({});
const editingPlanId = ref(null);
const deleteTargetId = ref(null);

function subscribe(id) {
    form.subscription_id = id;
    form.post('/subscriptions');
}

function savePlan() {
    const options = {
        onSuccess: () => {
            adminForm.reset();
            editingPlanId.value = null;
        },
    };

    if (editingPlanId.value !== null) {
        adminForm.put(`/admin/subscriptions/${editingPlanId.value}`, options);
        return;
    }

    adminForm.post('/admin/subscriptions', options);
}

function editPlan(plan) {
    editingPlanId.value = plan.id;
    adminForm.name = plan.name;
    adminForm.price = plan.price;
    adminForm.daily_task_limit = plan.daily_task_limit;
    adminForm.reward_amount = plan.reward_amount;
    adminForm.duration_minutes = plan.duration_minutes;
    adminForm.is_free = plan.is_free;
    adminForm.clearErrors();
}

function cancelEditing() {
    adminForm.reset();
    adminForm.clearErrors();
    editingPlanId.value = null;
}

function deletePlan(plan) {
    if (! window.confirm(`هل تريد حذف اشتراك ${plan.name}؟`)) {
        return;
    }

    deleteTargetId.value = plan.id;
    deleteForm.delete(`/admin/subscriptions/${plan.id}`, {
        onSuccess: () => {
            deleteTargetId.value = null;
        },
    });
}
</script>

<template>
    <AppLayout>
        <Head title="الاشتراكات" />
        <div>
            <h1 class="text-3xl font-semibold">الاشتراكات</h1>
            <p v-if="!is_admin" class="mt-2 text-stone-600">رصيدك الحالي {{ balance }}. التفعيل يخصم سعر الخطة من الرصيد.</p>
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
                        v-if="!is_admin"
                        type="button"
                        class="mt-5 w-full rounded-lg bg-stone-900 px-4 py-2 text-sm text-white disabled:opacity-50"
                        :disabled="form.processing || plan.id === current_subscription_id"
                        @click="subscribe(plan.id)"
                    >
                        {{ plan.id === current_subscription_id ? 'خطتك الحالية' : 'تفعيل' }}
                    </button>
                    <div v-if="is_admin" class="mt-3 flex gap-2">
                        <button
                            type="button"
                            class="rounded-lg border border-stone-300 px-3 py-2 text-sm text-stone-700 hover:bg-stone-50"
                            @click="editPlan(plan)"
                        >
                            تعديل
                        </button>
                        <button
                            type="button"
                            class="rounded-lg border border-red-200 px-3 py-2 text-sm text-red-700 hover:bg-red-50 disabled:opacity-50"
                            :disabled="deleteForm.processing"
                            @click="deletePlan(plan)"
                        >
                            حذف
                        </button>
                    </div>
                    <p v-if="deleteTargetId === plan.id && deleteForm.errors.subscription" class="mt-2 text-sm text-red-700">
                        {{ deleteForm.errors.subscription }}
                    </p>
                </article>
            </section>

            <form v-if="is_admin" class="mt-8 rounded-2xl border border-dashed border-teal-300 bg-white p-5" @submit.prevent="savePlan">
                <h2 class="text-lg font-semibold">{{ editingPlanId ? 'تعديل الاشتراك' : 'إضافة اشتراك' }}</h2>
                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    <input v-model="adminForm.name" required placeholder="اسم الخطة" class="rounded-lg border border-stone-300 px-3 py-2" />
                    <input v-model="adminForm.price" required type="number" min="0" step="0.01" placeholder="السعر" class="rounded-lg border border-stone-300 px-3 py-2" />
                    <input v-model="adminForm.daily_task_limit" required type="number" min="1" placeholder="عدد المهام" class="rounded-lg border border-stone-300 px-3 py-2" />
                    <input v-model="adminForm.reward_amount" required type="number" min="0.01" step="0.01" placeholder="ربح المهمة" class="rounded-lg border border-stone-300 px-3 py-2" />
                    <label class="text-sm">
                        الفاصل بين المهام (دقيقة)
                        <input v-model="adminForm.duration_minutes" required type="number" min="1" max="1440" class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2" />
                    </label>
                    <label class="flex items-center gap-2 text-sm">
                        <input v-model="adminForm.is_free" type="checkbox" />
                        خطة مجانية
                    </label>
                </div>
                <p v-for="(error, field) in adminForm.errors" :key="field" class="mt-2 text-sm text-red-700">{{ error }}</p>
                <div class="mt-4 flex gap-2">
                    <button type="submit" class="rounded-lg bg-teal-800 px-4 py-2 text-white" :disabled="adminForm.processing">
                        {{ editingPlanId ? 'حفظ التعديلات' : 'إضافة الاشتراك' }}
                    </button>
                    <button
                        v-if="editingPlanId"
                        type="button"
                        class="rounded-lg border border-stone-300 px-4 py-2 text-stone-700"
                        @click="cancelEditing"
                    >
                        إلغاء
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
