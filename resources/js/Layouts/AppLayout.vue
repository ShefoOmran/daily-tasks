<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth.user);
const success = computed(() => page.props.flash.success);

const links = [
    { href: '/dashboard', label: 'الرئيسية' },
    { href: '/balance', label: 'الرصيد' },
    { href: '/subscriptions', label: 'الاشتراكات' },
];
</script>

<template>
    <div class="min-h-screen">
        <header class="border-b border-stone-200 bg-white">
            <div class="mx-auto flex max-w-5xl flex-wrap items-center justify-between gap-4 px-4 py-4">
                <Link href="/dashboard" class="text-lg font-semibold text-teal-800">المهام اليومية</Link>
                <nav class="flex flex-wrap items-center gap-2 text-sm">
                    <Link
                        v-for="link in links"
                        :key="link.href"
                        :href="link.href"
                        class="rounded-full px-3 py-1.5 text-stone-700 hover:bg-stone-100"
                        prefetch
                    >
                        {{ link.label }}
                    </Link>
                    <Link
                        v-if="user?.is_admin"
                        href="/admin"
                        class="rounded-full px-3 py-1.5 text-stone-700 hover:bg-stone-100"
                    >
                        لوحة الإدارة
                    </Link>
                    <span v-if="user" class="px-2 text-stone-500">{{ user.name }}</span>
                    <Link href="/logout" method="post" as="button" class="rounded-full bg-stone-900 px-3 py-1.5 text-white">
                        خروج
                    </Link>
                </nav>
            </div>
        </header>

        <main class="mx-auto max-w-5xl px-4 py-8">
            <p v-if="success" class="mb-6 rounded-xl border border-teal-200 bg-teal-50 px-4 py-3 text-sm text-teal-900">
                {{ success }}
            </p>
            <slot />
        </main>
    </div>
</template>
