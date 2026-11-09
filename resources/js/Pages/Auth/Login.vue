<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

function submit() {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
}
</script>

<template>
    <div class="flex min-h-screen items-center justify-center px-4">
        <Head title="تسجيل الدخول" />
        <form class="w-full max-w-md rounded-2xl border border-stone-200 bg-white p-8 shadow-sm" @submit.prevent="submit">
            <h1 class="text-2xl font-semibold text-stone-900">تسجيل الدخول</h1>
            <p class="mt-2 text-sm text-stone-500">ادخل إلى حساب المهام اليومية.</p>

            <label class="mt-6 block text-sm font-medium">
                البريد الإلكتروني
                <input v-model="form.email" type="email" required autofocus class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2" />
            </label>
            <p v-if="form.errors.email" class="mt-1 text-sm text-red-700">{{ form.errors.email }}</p>

            <label class="mt-4 block text-sm font-medium">
                كلمة المرور
                <input v-model="form.password" type="password" required class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2" />
            </label>
            <p v-if="form.errors.password" class="mt-1 text-sm text-red-700">{{ form.errors.password }}</p>

            <label class="mt-4 flex items-center gap-2 text-sm text-stone-600">
                <input v-model="form.remember" type="checkbox" />
                تذكرني
            </label>

            <button type="submit" class="mt-6 w-full rounded-lg bg-teal-800 px-4 py-2.5 font-medium text-white disabled:opacity-60" :disabled="form.processing">
                دخول
            </button>

            <p class="mt-4 text-center text-sm text-stone-600">
                ليس لديك حساب؟
                <Link href="/register" class="font-medium text-teal-800">إنشاء حساب</Link>
            </p>
        </form>
    </div>
</template>
