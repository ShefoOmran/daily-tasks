<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

function submit() {
    form.post('/register', {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
}
</script>

<template>
    <div class="flex min-h-screen items-center justify-center px-4">
        <Head title="إنشاء حساب" />
        <form class="w-full max-w-md rounded-2xl border border-stone-200 bg-white p-8 shadow-sm" @submit.prevent="submit">
            <h1 class="text-2xl font-semibold">إنشاء حساب</h1>
            <p class="mt-2 text-sm text-stone-500">الحساب الجديد يبدأ بالخطة المجانية: 5 مهام يومياً.</p>

            <label class="mt-6 block text-sm font-medium">
                الاسم
                <input v-model="form.name" type="text" required class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2" />
            </label>
            <p v-if="form.errors.name" class="mt-1 text-sm text-red-700">{{ form.errors.name }}</p>

            <label class="mt-4 block text-sm font-medium">
                البريد الإلكتروني
                <input v-model="form.email" type="email" required class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2" />
            </label>
            <p v-if="form.errors.email" class="mt-1 text-sm text-red-700">{{ form.errors.email }}</p>

            <label class="mt-4 block text-sm font-medium">
                كلمة المرور
                <input v-model="form.password" type="password" required class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2" />
            </label>
            <p v-if="form.errors.password" class="mt-1 text-sm text-red-700">{{ form.errors.password }}</p>

            <label class="mt-4 block text-sm font-medium">
                تأكيد كلمة المرور
                <input v-model="form.password_confirmation" type="password" required class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2" />
            </label>

            <button type="submit" class="mt-6 w-full rounded-lg bg-teal-800 px-4 py-2.5 font-medium text-white disabled:opacity-60" :disabled="form.processing">
                إنشاء الحساب
            </button>

            <p class="mt-4 text-center text-sm text-stone-600">
                لديك حساب؟
                <Link href="/login" class="font-medium text-teal-800">تسجيل الدخول</Link>
            </p>
        </form>
    </div>
</template>
