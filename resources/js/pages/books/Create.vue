<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import BookController from '@/actions/App/Http/Controllers/BookController';
import InputError from '@/components/InputError.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index } from '@/routes/books';

type RelationOption = {
    id: string;
    name: string;
};

defineProps<{
    authors: RelationOption[];
    categories: RelationOption[];
    collections: RelationOption[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Books', href: index() },
            { title: 'Add book', href: BookController.create.url() },
        ],
    },
});
</script>

<template>
    <Head title="Add book" />

    <div class="flex max-w-3xl flex-col gap-6 p-4">
        <Heading
            variant="small"
            title="Add book"
            description="Record the edition exactly as printed"
        />

        <Form
            v-bind="BookController.store.form()"
            v-slot="{ errors, processing }"
            class="grid gap-6"
        >
            <div class="grid gap-2">
                <Label for="title">Title</Label>
                <Input id="title" name="title" required autofocus />
                <InputError :message="errors.title" />
            </div>

            <div class="grid gap-2">
                <Label for="subtitle">Subtitle</Label>
                <Input id="subtitle" name="subtitle" />
                <InputError :message="errors.subtitle" />
            </div>

            <div class="grid gap-6 md:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="isbn">ISBN</Label>
                    <Input id="isbn" name="isbn" />
                    <InputError :message="errors.isbn" />
                </div>
                <div class="grid gap-2">
                    <Label for="publication_year">Publication year</Label>
                    <Input
                        id="publication_year"
                        name="publication_year"
                        type="number"
                    />
                    <InputError :message="errors.publication_year" />
                </div>
            </div>

            <div class="grid gap-6 md:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="publisher">Publisher</Label>
                    <Input id="publisher" name="publisher" />
                    <InputError :message="errors.publisher" />
                </div>
                <div class="grid gap-2">
                    <Label for="edition">Edition</Label>
                    <Input id="edition" name="edition" />
                    <InputError :message="errors.edition" />
                </div>
            </div>

            <div class="grid gap-2">
                <Label for="language">Language</Label>
                <Input
                    id="language"
                    name="language"
                    placeholder="en or pt-BR"
                />
                <InputError :message="errors.language" />
            </div>

            <div class="grid gap-2">
                <Label for="description">Description</Label>
                <textarea
                    id="description"
                    name="description"
                    class="min-h-24 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                />
                <InputError :message="errors.description" />
            </div>

            <div class="grid gap-6 md:grid-cols-3">
                <div class="grid gap-2">
                    <Label for="author_ids">Authors</Label>
                    <select
                        id="author_ids"
                        name="author_ids[]"
                        multiple
                        size="5"
                        class="min-h-32 w-full rounded-md border border-input bg-background px-3 py-2 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                    >
                        <option
                            v-for="author in authors"
                            :key="author.id"
                            :value="author.id"
                        >
                            {{ author.name }}
                        </option>
                    </select>
                    <p class="text-xs text-muted-foreground">
                        Select all that apply.
                    </p>
                    <InputError :message="errors.author_ids" />
                </div>

                <div class="grid gap-2">
                    <Label for="category_ids">Categories</Label>
                    <select
                        id="category_ids"
                        name="category_ids[]"
                        multiple
                        size="5"
                        class="min-h-32 w-full rounded-md border border-input bg-background px-3 py-2 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                    >
                        <option
                            v-for="category in categories"
                            :key="category.id"
                            :value="category.id"
                        >
                            {{ category.name }}
                        </option>
                    </select>
                    <InputError :message="errors.category_ids" />
                </div>

                <div class="grid gap-2">
                    <Label for="collection_ids">Collections</Label>
                    <select
                        id="collection_ids"
                        name="collection_ids[]"
                        multiple
                        size="5"
                        class="min-h-32 w-full rounded-md border border-input bg-background px-3 py-2 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                    >
                        <option
                            v-for="collection in collections"
                            :key="collection.id"
                            :value="collection.id"
                        >
                            {{ collection.name }}
                        </option>
                    </select>
                    <InputError :message="errors.collection_ids" />
                </div>
            </div>

            <div class="flex gap-3">
                <Button type="submit" :disabled="processing">Save book</Button>
                <Button as-child variant="outline">
                    <a :href="index.url()">Cancel</a>
                </Button>
            </div>
        </Form>
    </div>
</template>
