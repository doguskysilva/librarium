<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AuthorController from '@/actions/App/Http/Controllers/AuthorController';
import BookController from '@/actions/App/Http/Controllers/BookController';
import CategoryController from '@/actions/App/Http/Controllers/CategoryController';
import CollectionController from '@/actions/App/Http/Controllers/CollectionController';
import ReadingController from '@/actions/App/Http/Controllers/ReadingController';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';

type Book = {
    id: string;
    title: string;
    subtitle: string | null;
    isbn: string | null;
    publisher: string | null;
    edition: string | null;
    publication_year: number | null;
    language: string | null;
    description: string | null;
    authors: Relation[];
    categories: Relation[];
    collections: Relation[];
    readings: Reading[];
};

type Relation = {
    id: string;
    name: string;
};

type Reading = {
    id: string;
    status: string;
    started_on: string | null;
    started_year: number | null;
    finished_on: string | null;
    finished_year: number | null;
};

defineProps<{ book: Book }>();
</script>

<template>
    <Head :title="book.title" />

    <div class="flex max-w-3xl flex-col gap-6 p-4">
        <div class="flex items-start justify-between gap-4">
            <Heading
                variant="small"
                :title="book.title"
                :description="book.subtitle ?? undefined"
            />
            <div class="flex gap-2">
                <Button as-child variant="outline">
                    <Link :href="ReadingController.create.url(book.id)"
                        >Add reading</Link
                    >
                </Button>
                <Button as-child>
                    <Link :href="BookController.edit.url(book.id)">Edit</Link>
                </Button>
            </div>
        </div>

        <dl class="grid gap-4 rounded-xl border p-6 sm:grid-cols-2">
            <div>
                <dt class="text-sm text-muted-foreground">ISBN</dt>
                <dd>{{ book.isbn ?? 'Unknown' }}</dd>
            </div>
            <div>
                <dt class="text-sm text-muted-foreground">Publisher</dt>
                <dd>{{ book.publisher ?? 'Unknown' }}</dd>
            </div>
            <div>
                <dt class="text-sm text-muted-foreground">Edition</dt>
                <dd>{{ book.edition ?? 'Unknown' }}</dd>
            </div>
            <div>
                <dt class="text-sm text-muted-foreground">Publication year</dt>
                <dd>{{ book.publication_year ?? 'Unknown' }}</dd>
            </div>
            <div>
                <dt class="text-sm text-muted-foreground">Language</dt>
                <dd>{{ book.language ?? 'Unknown' }}</dd>
            </div>
            <div class="sm:col-span-2">
                <dt class="text-sm text-muted-foreground">Description</dt>
                <dd class="whitespace-pre-wrap">
                    {{ book.description ?? 'Unknown' }}
                </dd>
            </div>
        </dl>

        <div class="grid gap-6 md:grid-cols-3">
            <section
                class="rounded-xl border border-sidebar-border/70 p-6 dark:border-sidebar-border"
            >
                <h2 class="text-sm font-medium text-muted-foreground">
                    Authors
                </h2>
                <div
                    v-if="book.authors.length"
                    class="mt-3 flex flex-col gap-2"
                >
                    <Link
                        v-for="author in book.authors"
                        :key="author.id"
                        :href="AuthorController.show.url(author.id)"
                        class="font-medium hover:underline"
                    >
                        {{ author.name }}
                    </Link>
                </div>
                <p v-else class="mt-3 text-sm text-muted-foreground">
                    No authors assigned.
                </p>
            </section>

            <section
                class="rounded-xl border border-sidebar-border/70 p-6 dark:border-sidebar-border"
            >
                <h2 class="text-sm font-medium text-muted-foreground">
                    Categories
                </h2>
                <div
                    v-if="book.categories.length"
                    class="mt-3 flex flex-col gap-2"
                >
                    <Link
                        v-for="category in book.categories"
                        :key="category.id"
                        :href="CategoryController.show.url(category.id)"
                        class="font-medium hover:underline"
                    >
                        {{ category.name }}
                    </Link>
                </div>
                <p v-else class="mt-3 text-sm text-muted-foreground">
                    No categories assigned.
                </p>
            </section>

            <section
                class="rounded-xl border border-sidebar-border/70 p-6 dark:border-sidebar-border"
            >
                <h2 class="text-sm font-medium text-muted-foreground">
                    Collections
                </h2>
                <div
                    v-if="book.collections.length"
                    class="mt-3 flex flex-col gap-2"
                >
                    <Link
                        v-for="collection in book.collections"
                        :key="collection.id"
                        :href="CollectionController.show.url(collection.id)"
                        class="font-medium hover:underline"
                    >
                        {{ collection.name }}
                    </Link>
                </div>
                <p v-else class="mt-3 text-sm text-muted-foreground">
                    No collections assigned.
                </p>
            </section>
        </div>

        <section class="grid gap-4 rounded-xl border p-6">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="font-medium">Reading history</h2>
                    <p class="text-sm text-muted-foreground">
                        Track each attempt separately, including rereads.
                    </p>
                </div>
                <Button as-child size="sm">
                    <Link :href="ReadingController.create.url(book.id)"
                        >New reading</Link
                    >
                </Button>
            </div>

            <div v-if="book.readings.length" class="grid gap-3">
                <Link
                    v-for="reading in book.readings"
                    :key="reading.id"
                    :href="ReadingController.show.url(reading.id)"
                    class="flex items-center justify-between gap-4 rounded-lg border p-4 hover:bg-muted/50"
                >
                    <span>
                        <span class="block font-medium capitalize">{{
                            reading.status
                        }}</span>
                        <span class="text-sm text-muted-foreground">
                            {{
                                reading.started_on ??
                                reading.started_year ??
                                'Start unknown'
                            }}
                            ·
                            {{
                                reading.finished_on ??
                                reading.finished_year ??
                                'Open-ended'
                            }}
                        </span>
                    </span>
                    <span class="text-sm text-muted-foreground">View</span>
                </Link>
            </div>
            <p v-else class="text-sm text-muted-foreground">
                No reading attempts yet.
            </p>
        </section>
    </div>
</template>
