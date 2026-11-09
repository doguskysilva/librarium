<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import CollectionController from '@/actions/App/Http/Controllers/CollectionController';

type Collection = {
    id: string;
    name: string;
    type: string;
    description: string | null;
};

const typeLabels: Record<string, string> = {
    editorial: 'Editorial',
    literary_series: 'Literary series',
    fictional_universe: 'Fictional universe',
    author_bibliography: 'Author bibliography',
    thematic: 'Thematic',
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Collections', href: CollectionController.index.url() },
        ],
    },
});

defineProps<{
    collections: {
        data: Collection[];
        links: Array<{ url: string | null; label: string; active: boolean }>;
    };
}>();
</script>

<template>
    <Head title="Collections" />

    <div class="flex w-full max-w-5xl flex-col gap-6 p-4">
        <div class="flex items-start justify-between gap-8 py-3">
            <div class="space-y-2">
                <h1 class="text-2xl font-semibold tracking-tight">
                    Collections
                </h1>
                <p class="text-sm text-muted-foreground">
                    Group books into personal and editorial collections
                </p>
            </div>
            <Link
                :href="CollectionController.create.url()"
                class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground"
            >
                Add collection
            </Link>
        </div>

        <div
            v-if="collections.data.length"
            class="overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
        >
            <article
                v-for="collection in collections.data"
                :key="collection.id"
                class="border-b border-sidebar-border/70 px-6 py-5 last:border-b-0 hover:bg-muted/30 dark:border-sidebar-border"
            >
                <Link
                    :href="CollectionController.show.url(collection.id)"
                    class="block font-medium hover:underline"
                >
                    {{ collection.name }}
                </Link>
                <p class="mt-1 text-sm text-muted-foreground">
                    {{ typeLabels[collection.type] ?? collection.type }}
                    <span v-if="collection.description">
                        · {{ collection.description }}</span
                    >
                </p>
            </article>
        </div>

        <p
            v-else
            class="rounded-xl border border-dashed p-8 text-center text-muted-foreground"
        >
            No collections have been cataloged yet.
        </p>

        <nav v-if="collections.links.length > 3" class="flex flex-wrap gap-2">
            <Link
                v-for="link in collections.links"
                :key="link.label"
                :href="link.url ?? CollectionController.index.url()"
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
