<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import CategoryController from '@/actions/App/Http/Controllers/CategoryController';
import { Button } from '@/components/ui/button';

type Category = {
    id: string;
    name: string;
    slug: string;
    description: string | null;
};

defineProps<{ category: Category }>();
</script>

<template>
    <Head :title="category.name" />

    <div class="flex max-w-3xl flex-col gap-6 p-4">
        <div class="flex items-start justify-between gap-8 py-3">
            <div class="space-y-2">
                <h1 class="text-2xl font-semibold tracking-tight">
                    {{ category.name }}
                </h1>
                <p class="text-sm text-muted-foreground">{{ category.slug }}</p>
            </div>
            <Button as-child>
                <Link :href="CategoryController.edit.url(category.id)"
                    >Edit</Link
                >
            </Button>
        </div>

        <div
            class="rounded-xl border border-sidebar-border/70 p-6 dark:border-sidebar-border"
        >
            <h2 class="text-sm font-medium text-muted-foreground">
                Description
            </h2>
            <p class="mt-3 whitespace-pre-wrap">
                {{ category.description ?? 'No description recorded.' }}
            </p>
        </div>
    </div>
</template>
