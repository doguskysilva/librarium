<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import CategoryController from '@/actions/App/Http/Controllers/CategoryController';

type Category = {
    id: string;
    name: string;
    slug: string;
    description: string | null;
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Categories', href: CategoryController.index.url() },
        ],
    },
});

defineProps<{
    categories: {
        data: Category[];
        links: Array<{ url: string | null; label: string; active: boolean }>;
    };
}>();
</script>

<template>
    <Head title="Categories" />

    <div class="flex w-full max-w-5xl flex-col gap-6 p-4">
        <div class="flex items-start justify-between gap-8 py-3">
            <div class="space-y-2">
                <h1 class="text-2xl font-semibold tracking-tight">
                    Categories
                </h1>
                <p class="text-sm text-muted-foreground">
                    Classify books by subject or genre
                </p>
            </div>
            <Link
                :href="CategoryController.create.url()"
                class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground"
            >
                Add category
            </Link>
        </div>

        <div
            v-if="categories.data.length"
            class="overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
        >
            <article
                v-for="category in categories.data"
                :key="category.id"
                class="border-b border-sidebar-border/70 px-6 py-5 last:border-b-0 hover:bg-muted/30 dark:border-sidebar-border"
            >
                <Link
                    :href="CategoryController.show.url(category.id)"
                    class="block font-medium hover:underline"
                >
                    {{ category.name }}
                </Link>
                <p class="mt-1 text-sm text-muted-foreground">
                    {{ category.slug }}
                    <span v-if="category.description">
                        · {{ category.description }}</span
                    >
                </p>
            </article>
        </div>

        <p
            v-else
            class="rounded-xl border border-dashed p-8 text-center text-muted-foreground"
        >
            No categories have been cataloged yet.
        </p>

        <nav v-if="categories.links.length > 3" class="flex flex-wrap gap-2">
            <Link
                v-for="link in categories.links"
                :key="link.label"
                :href="link.url ?? CategoryController.index.url()"
                :class="[
                    'rounded-md px-3 py-1 text-sm',
                    link.active
                        ? 'bg-primary text-primary-foreground'
                        : 'border hover:bg-muted',
                ]"
                v-html="link.label"
            />
        </nav>
    </div>
</template>
