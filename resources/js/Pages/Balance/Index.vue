<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '../../Layouts/AppLayout.vue';

const props = defineProps({
    is_admin: { type: Boolean, required: true },
    balance: { type: String, default: null },
    methods: { type: Array, required: true },
    agents: { type: Array, required: true },
    transactions: { type: Array, required: true },
});

const page = useForm;
const depositMethods = computed(() => props.methods.filter((method) => method.type === 'deposit'));
const withdrawalMethods = computed(() => props.methods.filter((method) => method.type === 'withdrawal'));

const depositForm = page({ amount: '', payment_method_id: '' });
const withdrawalForm = page({ amount: '', payment_method_id: '' });
const methodForm = page({ name: '', type: 'deposit', details: '' });
const agentForm = page({ name: '', phone: '', note: '' });

function submitDeposit() {
    depositForm.post('/deposits', { onSuccess: () => depositForm.reset() });
}

function submitWithdrawal() {
    withdrawalForm.post('/withdrawals', { onSuccess: () => withdrawalForm.reset() });
}

function submitMethod() {
    methodForm.post('/admin/payment-methods', { onSuccess: () => methodForm.reset('name', 'details') });
}

function submitAgent() {
    agentForm.post('/admin/agents', { onSuccess: () => agentForm.reset() });
}
</script>

<template>
    <AppLayout>
        <Head :title="is_admin ? 'إعدادات الدفع' : 'الرصيد'" />
        <div>
            <h1 class="text-3xl font-semibold">{{ is_admin ? 'إعدادات الدفع' : 'الرصيد' }}</h1>
            <p v-if="!is_admin" class="mt-2 text-stone-600">الرصيد الحالي: <span class="font-semibold text-teal-800">{{ balance }}</span></p>

            <section v-if="!is_admin" class="mt-6 grid gap-4 lg:grid-cols-2">
                <form class="rounded-2xl border border-stone-200 bg-white p-5" @submit.prevent="submitDeposit">
                    <h2 class="text-lg font-semibold">إيداع</h2>
                    <label class="mt-4 block text-sm">
                        المبلغ
                        <input v-model="depositForm.amount" type="number" min="1" step="0.01" required class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2" />
                    </label>
                    <p v-if="depositForm.errors.amount" class="mt-1 text-sm text-red-700">{{ depositForm.errors.amount }}</p>
                    <label class="mt-4 block text-sm">
                        طريقة الإيداع
                        <select v-model="depositForm.payment_method_id" required class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2">
                            <option value="" disabled>اختر الطريقة</option>
                            <option v-for="method in depositMethods" :key="method.id" :value="method.id">{{ method.name }}</option>
                        </select>
                    </label>
                    <p v-if="depositForm.errors.payment_method_id" class="mt-1 text-sm text-red-700">{{ depositForm.errors.payment_method_id }}</p>
                    <p v-if="depositMethods.find((method) => method.id === Number(depositForm.payment_method_id))" class="mt-2 text-xs text-stone-500">
                        {{ depositMethods.find((method) => method.id === Number(depositForm.payment_method_id)).details }}
                    </p>
                    <button type="submit" class="mt-4 rounded-lg bg-teal-800 px-4 py-2 text-white disabled:opacity-60" :disabled="depositForm.processing">تسجيل الإيداع</button>
                </form>

                <form class="rounded-2xl border border-stone-200 bg-white p-5" @submit.prevent="submitWithdrawal">
                    <h2 class="text-lg font-semibold">سحب</h2>
                    <label class="mt-4 block text-sm">
                        المبلغ
                        <input v-model="withdrawalForm.amount" type="number" min="1" step="0.01" required class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2" />
                    </label>
                    <p v-if="withdrawalForm.errors.amount" class="mt-1 text-sm text-red-700">{{ withdrawalForm.errors.amount }}</p>
                    <label class="mt-4 block text-sm">
                        طريقة السحب
                        <select v-model="withdrawalForm.payment_method_id" required class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2">
                            <option value="" disabled>اختر الطريقة</option>
                            <option v-for="method in withdrawalMethods" :key="method.id" :value="method.id">{{ method.name }}</option>
                        </select>
                    </label>
                    <p v-if="withdrawalForm.errors.payment_method_id" class="mt-1 text-sm text-red-700">{{ withdrawalForm.errors.payment_method_id }}</p>
                    <button type="submit" class="mt-4 rounded-lg bg-stone-900 px-4 py-2 text-white disabled:opacity-60" :disabled="withdrawalForm.processing">تسجيل السحب</button>
                </form>
            </section>

            <section v-if="!is_admin" class="mt-6 rounded-2xl border border-stone-200 bg-white p-5">
                <h2 class="text-lg font-semibold">الوكلاء</h2>
                <p v-if="agents.length === 0" class="mt-3 text-sm text-stone-500">لا يوجد وكلاء حالياً.</p>
                <ul v-else class="mt-3 divide-y divide-stone-100">
                    <li v-for="agent in agents" :key="agent.id" class="py-3">
                        <p class="font-medium">{{ agent.name }}</p>
                        <p class="text-sm text-stone-600">{{ agent.phone }}</p>
                        <p v-if="agent.note" class="text-sm text-stone-500">{{ agent.note }}</p>
                    </li>
                </ul>
            </section>

            <section v-if="!is_admin" class="mt-6 rounded-2xl border border-stone-200 bg-white p-5">
                <h2 class="text-lg font-semibold">آخر العمليات</h2>
                <p v-if="transactions.length === 0" class="mt-3 text-sm text-stone-500">لا توجد عمليات بعد.</p>
                <table v-else class="mt-3 w-full text-sm">
                    <thead class="text-stone-500">
                        <tr>
                            <th class="py-2 text-right font-medium">النوع</th>
                            <th class="py-2 text-right font-medium">المبلغ</th>
                            <th class="py-2 text-right font-medium">الطريقة</th>
                            <th class="py-2 text-right font-medium">الوقت</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="transaction in transactions" :key="transaction.id" class="border-t border-stone-100">
                            <td class="py-2">{{ transaction.label }}</td>
                            <td class="py-2">{{ transaction.amount }}</td>
                            <td class="py-2">{{ transaction.method || transaction.note || '—' }}</td>
                            <td class="py-2">{{ transaction.created_at }}</td>
                        </tr>
                    </tbody>
                </table>
            </section>

            <section v-if="is_admin" class="mt-6 grid gap-4 lg:grid-cols-2">
                <form class="rounded-2xl border border-dashed border-teal-300 bg-white p-5" @submit.prevent="submitMethod">
                    <h2 class="text-lg font-semibold">إضافة طريقة دفع</h2>
                    <input v-model="methodForm.name" required placeholder="الاسم" class="mt-4 w-full rounded-lg border border-stone-300 px-3 py-2" />
                    <select v-model="methodForm.type" class="mt-3 w-full rounded-lg border border-stone-300 px-3 py-2">
                        <option value="deposit">إيداع</option>
                        <option value="withdrawal">سحب</option>
                    </select>
                    <textarea v-model="methodForm.details" required placeholder="تفاصيل الطريقة" class="mt-3 w-full rounded-lg border border-stone-300 px-3 py-2" />
                    <p v-if="methodForm.errors.name || methodForm.errors.details" class="mt-2 text-sm text-red-700">تحقق من الحقول.</p>
                    <button type="submit" class="mt-3 rounded-lg bg-teal-800 px-4 py-2 text-white" :disabled="methodForm.processing">إضافة</button>
                </form>

                <form class="rounded-2xl border border-dashed border-teal-300 bg-white p-5" @submit.prevent="submitAgent">
                    <h2 class="text-lg font-semibold">إضافة وكيل</h2>
                    <input v-model="agentForm.name" required placeholder="الاسم" class="mt-4 w-full rounded-lg border border-stone-300 px-3 py-2" />
                    <input v-model="agentForm.phone" required placeholder="الهاتف" class="mt-3 w-full rounded-lg border border-stone-300 px-3 py-2" />
                    <input v-model="agentForm.note" placeholder="ملاحظة" class="mt-3 w-full rounded-lg border border-stone-300 px-3 py-2" />
                    <button type="submit" class="mt-3 rounded-lg bg-teal-800 px-4 py-2 text-white" :disabled="agentForm.processing">إضافة</button>
                </form>
            </section>
        </div>
    </AppLayout>
</template>
