<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import BookController from '@/actions/App/Http/Controllers/BookController';
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
};

defineProps<{ book: Book }>();
</script>

<template>
    <Head :title="book.title" />

    <div class="flex max-w-3xl flex-col gap-6">
        <div class="flex items-start justify-between gap-4">
            <Heading
                variant="small"
                :title="book.title"
                :description="book.subtitle ?? undefined"
            />
            <Button as-child>
                <Link :href="BookController.edit.url(book.id)">Edit</Link>
            </Button>
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
    </div>
</template>
