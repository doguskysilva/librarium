<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import AuthorController from '@/actions/App/Http/Controllers/AuthorController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index } from '@/routes/authors';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Authors', href: index.url() },
            { title: 'Add author', href: AuthorController.create.url() },
        ],
    },
});
</script>

<template>
    <Head title="Add author" />

    <div class="flex max-w-3xl flex-col gap-6 p-4">
        <div class="space-y-2 py-3">
            <h1 class="text-2xl font-semibold tracking-tight">Add author</h1>
            <p class="text-sm text-muted-foreground">
                Record the credited name as printed on the book
            </p>
        </div>

        <Form
            v-bind="AuthorController.store.form()"
            v-slot="{ errors, processing }"
            class="grid gap-6"
        >
            <div class="grid gap-2">
                <Label for="name">Name</Label>
                <Input id="name" name="name" required autofocus />
                <InputError :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="biography">Biography</Label>
                <textarea
                    id="biography"
                    name="biography"
                    class="min-h-32 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                ></textarea>
                <InputError :message="errors.biography" />
            </div>

            <div class="flex gap-3">
                <Button type="submit" :disabled="processing"
                    >Save author</Button
                >
                <Button as-child variant="outline">
                    <Link :href="index.url()">Cancel</Link>
                </Button>
            </div>
        </Form>
    </div>
</template>
