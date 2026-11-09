<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AuthorController from '@/actions/App/Http/Controllers/AuthorController';

type Author = {
    id: string;
    name: string;
    biography: string | null;
};

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Authors', href: AuthorController.index.url() }],
    },
});

defineProps<{
    authors: {
        data: Author[];
        links: Array<{ url: string | null; label: string; active: boolean }>;
    };
}>();
</script>

<template>
    <Head title="Authors" />

    <div class="flex w-full max-w-5xl flex-col gap-6 p-4">
        <div class="flex items-start justify-between gap-8 py-3">
            <div class="space-y-2">
                <h1 class="text-2xl font-semibold tracking-tight">Authors</h1>
                <p class="text-sm text-muted-foreground">
                    Keep track of the people credited on your books
                </p>
            </div>
            <Link
                :href="AuthorController.create.url()"
                class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground"
            >
                Add author
            </Link>
        </div>

        <div
            v-if="authors.data.length"
            class="overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
        >
            <article
                v-for="author in authors.data"
                :key="author.id"
                class="border-b border-sidebar-border/70 px-6 py-5 last:border-b-0 hover:bg-muted/30 dark:border-sidebar-border"
            >
                <Link
                    :href="AuthorController.show.url(author.id)"
                    class="block font-medium hover:underline"
                >
                    {{ author.name }}
                </Link>
                <p
                    v-if="author.biography"
                    class="mt-1 line-clamp-2 text-sm text-muted-foreground"
                >
                    {{ author.biography }}
                </p>
            </article>
        </div>

        <p
            v-else
            class="rounded-xl border border-dashed p-8 text-center text-muted-foreground"
        >
            No authors have been cataloged yet.
        </p>

        <nav v-if="authors.links.length > 3" class="flex flex-wrap gap-2">
            <Link
                v-for="link in authors.links"
                :key="link.label"
                :href="link.url ?? AuthorController.index.url()"
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
