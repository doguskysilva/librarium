<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import BookController from '@/actions/App/Http/Controllers/BookController';
import InputError from '@/components/InputError.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

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

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Books', href: BookController.index.url() }],
    },
});
</script>

<template>
    <Head :title="`Edit ${book.title}`" />

    <div class="flex max-w-3xl flex-col gap-6 p-4">
        <Heading variant="small" title="Edit book" :description="book.title" />

        <Form
            v-bind="BookController.update.form(book.id)"
            v-slot="{ errors, processing }"
            class="grid gap-6"
        >
            <div class="grid gap-2">
                <Label for="title">Title</Label>
                <Input
                    id="title"
                    name="title"
                    :default-value="book.title"
                    required
                    autofocus
                />
                <InputError :message="errors.title" />
            </div>
            <div class="grid gap-2">
                <Label for="subtitle">Subtitle</Label>
                <Input
                    id="subtitle"
                    name="subtitle"
                    :default-value="book.subtitle ?? ''"
                />
                <InputError :message="errors.subtitle" />
            </div>
            <div class="grid gap-6 md:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="isbn">ISBN</Label>
                    <Input
                        id="isbn"
                        name="isbn"
                        :default-value="book.isbn ?? ''"
                    />
                    <InputError :message="errors.isbn" />
                </div>
                <div class="grid gap-2">
                    <Label for="publication_year">Publication year</Label>
                    <Input
                        id="publication_year"
                        name="publication_year"
                        type="number"
                        :default-value="book.publication_year ?? ''"
                    />
                    <InputError :message="errors.publication_year" />
                </div>
            </div>
            <div class="grid gap-6 md:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="publisher">Publisher</Label>
                    <Input
                        id="publisher"
                        name="publisher"
                        :default-value="book.publisher ?? ''"
                    />
                    <InputError :message="errors.publisher" />
                </div>
                <div class="grid gap-2">
                    <Label for="edition">Edition</Label>
                    <Input
                        id="edition"
                        name="edition"
                        :default-value="book.edition ?? ''"
                    />
                    <InputError :message="errors.edition" />
                </div>
            </div>
            <div class="grid gap-2">
                <Label for="language">Language</Label>
                <Input
                    id="language"
                    name="language"
                    :default-value="book.language ?? ''"
                />
                <InputError :message="errors.language" />
            </div>
            <div class="grid gap-2">
                <Label for="description">Description</Label>
                <textarea
                    id="description"
                    name="description"
                    :value="book.description ?? ''"
                    class="min-h-24 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                />
                <InputError :message="errors.description" />
            </div>
            <div class="flex gap-3">
                <Button type="submit" :disabled="processing"
                    >Save changes</Button
                >
                <Button as-child variant="outline">
                    <Link :href="BookController.show.url(book.id)">Cancel</Link>
                </Button>
            </div>
        </Form>
    </div>
</template>
