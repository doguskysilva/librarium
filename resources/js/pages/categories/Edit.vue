<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import CategoryController from '@/actions/App/Http/Controllers/CategoryController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Category = {
    id: string;
    name: string;
    slug: string;
    description: string | null;
};

defineProps<{ category: Category }>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Categories', href: CategoryController.index.url() },
        ],
    },
});
</script>

<template>
    <Head :title="`Edit ${category.name}`" />

    <div class="flex max-w-3xl flex-col gap-6 p-4">
        <div class="space-y-2 py-3">
            <h1 class="text-2xl font-semibold tracking-tight">Edit category</h1>
            <p class="text-sm text-muted-foreground">{{ category.name }}</p>
        </div>

        <Form
            v-bind="CategoryController.update.form(category.id)"
            v-slot="{ errors, processing }"
            class="grid gap-6"
        >
            <div class="grid gap-2">
                <Label for="name">Name</Label>
                <Input
                    id="name"
                    name="name"
                    :default-value="category.name"
                    required
                    autofocus
                />
                <InputError :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="slug">Slug</Label>
                <Input
                    id="slug"
                    name="slug"
                    :default-value="category.slug"
                    required
                />
                <InputError :message="errors.slug" />
            </div>

            <div class="grid gap-2">
                <Label for="description">Description</Label>
                <textarea
                    id="description"
                    name="description"
                    :value="category.description ?? ''"
                    class="min-h-32 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                ></textarea>
                <InputError :message="errors.description" />
            </div>

            <div class="flex gap-3">
                <Button type="submit" :disabled="processing"
                    >Save changes</Button
                >
                <Button as-child variant="outline">
                    <Link :href="CategoryController.show.url(category.id)"
                        >Cancel</Link
                    >
                </Button>
            </div>
        </Form>
    </div>
</template>
