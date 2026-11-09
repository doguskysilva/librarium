<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import BookController from '@/actions/App/Http/Controllers/BookController';
import BookMetadataLookupModal from '@/components/BookMetadataLookupModal.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

type BookFilters = {
    q?: string;
    category?: string;
    language?: string;
    filter?: string;
    year_from?: number | string;
    year_to?: number | string;
};

type Book = {
    id: string;
    title: string;
    subtitle: string | null;
    isbn: string | null;
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

const props = defineProps<{
    books: {
        data: Book[];
        links: Array<{ url: string | null; label: string; active: boolean }>;
    };
    filters: BookFilters;
}>();

const searchQuery = ref(props.filters.q ?? '');
const metadataModalOpen = ref(false);

const activeFilterLabels = computed(() => {
    const labels: string[] = [];

    if (props.filters.q) {
        labels.push(`Search: ${props.filters.q}`);
    }

    if (props.filters.category) {
        labels.push('Category');
    }

    if (props.filters.language) {
        labels.push(`Language: ${props.filters.language}`);
    }

    if (props.filters.filter) {
        labels.push(
            {
                missing_covers: 'Missing covers',
                unclassified: 'Unclassified',
                missing_isbn: 'Missing ISBN',
                missing_descriptions: 'Missing descriptions',
                unknown_language: 'Unknown language',
                unknown_year: 'Unknown year',
            }[props.filters.filter] ?? 'Filtered',
        );
    }

    if (props.filters.year_from || props.filters.year_to) {
        labels.push(
            `Publication years: ${props.filters.year_from ?? 'Any'}–${props.filters.year_to ?? 'Any'}`,
        );
    }

    return labels;
});

function filterBooks() {
    const query = searchQuery.value.trim();

    router.get(
        BookController.index.url({
            query: query ? { q: query } : {},
        }),
        {},
        { preserveScroll: true, preserveState: true },
    );
}

function openMetadataLookup() {
    metadataModalOpen.value = true;
}
</script>

<template>
    <Head title="Books" />

    <div class="flex w-full max-w-5xl flex-col gap-6 p-4">
        <div
            class="flex flex-col items-start justify-between gap-4 py-3 sm:flex-row sm:items-center"
        >
            <div class="space-y-2">
                <h1 class="text-2xl font-semibold tracking-tight">Books</h1>
                <p class="text-sm text-muted-foreground">
                    Catalog the physical editions in your library
                </p>
                <p
                    v-if="activeFilterLabels.length"
                    class="flex flex-wrap items-center gap-2 text-sm text-muted-foreground"
                >
                    <span>Filtered by {{ activeFilterLabels.join(', ') }}</span>
                    <Link
                        :href="BookController.index.url()"
                        class="font-medium text-foreground underline-offset-4 hover:underline"
                    >
                        Clear filters
                    </Link>
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <Button variant="outline" @click="openMetadataLookup">
                    Find by ISBN
                </Button>
                <Button as-child>
                    <Link :href="BookController.create.url()">Add book</Link>
                </Button>
            </div>
        </div>

        <form
            class="flex flex-col gap-2 sm:flex-row"
            role="search"
            @submit.prevent="filterBooks"
        >
            <Input
                v-model="searchQuery"
                aria-label="Search books by title or ISBN"
                placeholder="Search title or ISBN"
                class="sm:max-w-sm"
            />
            <div class="flex gap-2">
                <Button type="submit" variant="secondary">Search</Button>
                <Button
                    v-if="filters.q"
                    type="button"
                    variant="ghost"
                    @click="
                        searchQuery = '';
                        filterBooks();
                    "
                >
                    Clear search
                </Button>
            </div>
        </form>

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
            {{
                activeFilterLabels.length
                    ? 'No books match these filters.'
                    : 'No books have been cataloged yet.'
            }}
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

        <BookMetadataLookupModal v-model:open="metadataModalOpen" />
    </div>
</template>
