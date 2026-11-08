<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import CategoryController from '@/actions/App/Http/Controllers/CategoryController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index } from '@/routes/categories';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Categories', href: index.url() },
            { title: 'Add category', href: CategoryController.create.url() },
        ],
    },
});
</script>

<template>
    <Head title="Add category" />

    <div class="flex max-w-3xl flex-col gap-6 p-4">
        <div class="space-y-2 py-3">
            <h1 class="text-2xl font-semibold tracking-tight">Add category</h1>
            <p class="text-sm text-muted-foreground">
                Define a subject or genre classification
            </p>
        </div>

        <Form
            v-bind="CategoryController.store.form()"
            v-slot="{ errors, processing }"
            class="grid gap-6"
        >
            <div class="grid gap-2">
                <Label for="name">Name</Label>
                <Input id="name" name="name" required autofocus />
                <InputError :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="slug">Slug</Label>
                <Input
                    id="slug"
                    name="slug"
                    required
                    placeholder="science-fiction"
                />
                <InputError :message="errors.slug" />
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
                    >Save category</Button
                >
                <Button as-child variant="outline">
                    <Link :href="index.url()">Cancel</Link>
                </Button>
            </div>
        </Form>
    </div>
</template>
