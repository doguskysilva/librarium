<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import BookController from '@/actions/App/Http/Controllers/BookController';

type Book = {
    id: string;
    title: string;
    subtitle: string | null;
    publisher: string | null;
    publication_year: number | null;
    language: string | null;
};

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Books',
                href: BookController.index.url(),
            },
        ],
    },
});

defineProps<{
    books: {
        data: Book[];
        links: Array<{ url: string | null; label: string; active: boolean }>;
    };
}>();
</script>

<template>
    <Head title="Books" />

    <div class="flex w-full max-w-5xl flex-col gap-6 p-4">
        <div class="flex items-start justify-between gap-8 py-3">
            <div class="space-y-2">
                <h1 class="text-2xl font-semibold tracking-tight">Books</h1>
                <p class="text-sm text-muted-foreground">
                    Catalog the physical editions in your library
                </p>
            </div>
            <Link
                :href="BookController.create.url()"
                class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground"
            >
                Add book
            </Link>
        </div>

        <div
            v-if="books.data.length"
            class="overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
        >
            <article
                v-for="book in books.data"
                :key="book.id"
                class="flex items-center justify-between gap-6 border-b border-sidebar-border/70 px-6 py-5 last:border-b-0 hover:bg-muted/30 dark:border-sidebar-border"
            >
                <div class="min-w-0 space-y-1">
                    <Link
                        :href="BookController.show.url(book.id)"
                        class="block truncate font-medium hover:underline"
                    >
                        {{ book.title }}
                    </Link>
                    <p
                        v-if="book.subtitle"
                        class="text-sm text-muted-foreground"
                    >
                        {{ book.subtitle }}
                    </p>
                    <p class="text-sm text-muted-foreground">
                        {{
                            [
                                book.publisher,
                                book.publication_year,
                                book.language,
                            ]
                                .filter(Boolean)
                                .join(' · ') || 'No additional metadata'
                        }}
                    </p>
                </div>
            </article>
        </div>

        <p
            v-else
            class="rounded-xl border border-dashed p-8 text-center text-muted-foreground"
        >
            No books have been cataloged yet.
        </p>

        <nav v-if="books.links.length > 3" class="flex flex-wrap gap-2">
            <Link
                v-for="link in books.links"
                :key="link.label"
                :href="link.url ?? BookController.index.url()"
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
