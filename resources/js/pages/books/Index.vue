<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import BookController from '@/actions/App/Http/Controllers/BookController';
import Heading from '@/components/Heading.vue';

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

    <div class="flex flex-col gap-6">
        <div class="flex items-start justify-between gap-4">
            <Heading
                variant="small"
                title="Books"
                description="Catalog the physical editions in your library"
            />
            <Link
                :href="BookController.create.url()"
                class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground"
            >
                Add book
            </Link>
        </div>

        <div v-if="books.data.length" class="grid gap-4 md:grid-cols-2">
            <article
                v-for="book in books.data"
                :key="book.id"
                class="flex flex-col gap-4 rounded-xl border border-sidebar-border/70 p-5 dark:border-sidebar-border"
            >
                <div class="flex flex-col gap-1">
                    <Link
                        :href="BookController.show.url(book.id)"
                        class="font-medium hover:underline"
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

                <div class="flex gap-3 text-sm">
                    <Link
                        :href="BookController.edit.url(book.id)"
                        class="underline underline-offset-4"
                    >
                        Edit
                    </Link>
                    <Link
                        :href="BookController.destroy.url(book.id)"
                        method="delete"
                        as="button"
                        class="text-destructive underline underline-offset-4"
                    >
                        Delete
                    </Link>
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
