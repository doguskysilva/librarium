<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import CollectionController from '@/actions/App/Http/Controllers/CollectionController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index } from '@/routes/collections';

const collectionTypes = [
    { value: 'editorial', label: 'Editorial' },
    { value: 'literary_series', label: 'Literary series' },
    { value: 'fictional_universe', label: 'Fictional universe' },
    { value: 'author_bibliography', label: 'Author bibliography' },
    { value: 'thematic', label: 'Thematic' },
];

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Collections', href: index.url() },
            {
                title: 'Add collection',
                href: CollectionController.create.url(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Add collection" />

    <div class="flex max-w-3xl flex-col gap-6 p-4">
        <div class="space-y-2 py-3">
            <h1 class="text-2xl font-semibold tracking-tight">
                Add collection
            </h1>
            <p class="text-sm text-muted-foreground">
                Create a named grouping for your books
            </p>
        </div>

        <Form
            v-bind="CollectionController.store.form()"
            v-slot="{ errors, processing }"
            class="grid gap-6"
        >
            <div class="grid gap-2">
                <Label for="name">Name</Label>
                <Input id="name" name="name" required autofocus />
                <InputError :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="type">Type</Label>
                <select
                    id="type"
                    name="type"
                    required
                    class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                >
                    <option value="" disabled>Select a type</option>
                    <option
                        v-for="item in collectionTypes"
                        :key="item.value"
                        :value="item.value"
                    >
                        {{ item.label }}
                    </option>
                </select>
                <InputError :message="errors.type" />
            </div>

            <div class="grid gap-2">
                <Label for="description">Description</Label>
                <textarea
                    id="description"
                    name="description"
                    class="min-h-32 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                ></textarea>
                <InputError :message="errors.description" />
            </div>

            <div class="flex gap-3">
                <Button type="submit" :disabled="processing"
                    >Save collection</Button
                >
                <Button as-child variant="outline">
                    <Link :href="index.url()">Cancel</Link>
                </Button>
            </div>
        </Form>
    </div>
</template>
