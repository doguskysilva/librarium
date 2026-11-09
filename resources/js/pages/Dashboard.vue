<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import BookController from '@/actions/App/Http/Controllers/BookController';
import PlaceholderPattern from '@/components/PlaceholderPattern.vue';
import { BookOpen } from '@lucide/vue';
import { dashboard } from '@/routes';

defineProps<{
    booksCount: number;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Dashboard" />

    <div
        class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
    >
        <div class="grid auto-rows-min gap-4 md:grid-cols-3">
            <div
                class="relative overflow-hidden rounded-xl border border-sidebar-border/70 p-6 dark:border-sidebar-border"
            >
                <Link
                    :href="BookController.index.url()"
                    class="flex h-full flex-col justify-between gap-8"
                >
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <p class="text-sm text-muted-foreground">Catalog</p>
                            <h2 class="text-lg font-medium">Books</h2>
                        </div>
                        <BookOpen class="size-5 text-muted-foreground" />
                    </div>
                    <p class="text-3xl font-semibold">{{ booksCount }}</p>
                </Link>
            </div>
            <div
                class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
            >
                <PlaceholderPattern />
            </div>
            <div
                class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
            >
                <PlaceholderPattern />
            </div>
        </div>
        <div
            class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border"
        >
            <PlaceholderPattern />
        </div>
    </div>
</template>
