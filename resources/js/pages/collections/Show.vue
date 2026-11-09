<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import BookController from '@/actions/App/Http/Controllers/BookController';
import CollectionController from '@/actions/App/Http/Controllers/CollectionController';
import { Button } from '@/components/ui/button';

type Collection = {
    id: string;
    name: string;
    type: string;
    description: string | null;
    books: BookSummary[];
};

type BookSummary = {
    id: string;
    title: string;
};

const typeLabels: Record<string, string> = {
    editorial: 'Editorial',
    literary_series: 'Literary series',
    fictional_universe: 'Fictional universe',
    author_bibliography: 'Author bibliography',
    thematic: 'Thematic',
};

defineProps<{ collection: Collection }>();
</script>

<template>
    <Head :title="collection.name" />

    <div class="flex max-w-3xl flex-col gap-6 p-4">
        <div class="flex items-start justify-between gap-8 py-3">
            <div class="space-y-2">
                <h1 class="text-2xl font-semibold tracking-tight">
                    {{ collection.name }}
                </h1>
                <p class="text-sm text-muted-foreground">
                    {{ typeLabels[collection.type] ?? collection.type }}
                </p>
            </div>
            <Button as-child>
                <Link :href="CollectionController.edit.url(collection.id)"
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
                {{ collection.description ?? 'No description recorded.' }}
            </p>
        </div>

        <section
            class="rounded-xl border border-sidebar-border/70 p-6 dark:border-sidebar-border"
        >
            <h2 class="text-sm font-medium text-muted-foreground">Books</h2>
            <div
                v-if="collection.books.length"
                class="mt-3 flex flex-col gap-2"
            >
                <Link
                    v-for="book in collection.books"
                    :key="book.id"
                    :href="BookController.show.url(book.id)"
                    class="font-medium hover:underline"
                >
                    {{ book.title }}
                </Link>
            </div>
            <p v-else class="mt-3 text-sm text-muted-foreground">
                No books assigned.
            </p>
        </section>
    </div>
</template>
