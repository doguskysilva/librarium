<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import AuthorController from '@/actions/App/Http/Controllers/AuthorController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Author = {
    id: string;
    name: string;
    biography: string | null;
};

defineProps<{ author: Author }>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Authors', href: AuthorController.index.url() }],
    },
});
</script>

<template>
    <Head :title="`Edit ${author.name}`" />

    <div class="flex max-w-3xl flex-col gap-6 p-4">
        <div class="space-y-2 py-3">
            <h1 class="text-2xl font-semibold tracking-tight">Edit author</h1>
            <p class="text-sm text-muted-foreground">{{ author.name }}</p>
        </div>

        <Form
            v-bind="AuthorController.update.form(author.id)"
            v-slot="{ errors, processing }"
            class="grid gap-6"
        >
            <div class="grid gap-2">
                <Label for="name">Name</Label>
                <Input
                    id="name"
                    name="name"
                    :default-value="author.name"
                    required
                    autofocus
                />
                <InputError :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="biography">Biography</Label>
                <textarea
                    id="biography"
                    name="biography"
                    :value="author.biography ?? ''"
                    class="min-h-32 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                ></textarea>
                <InputError :message="errors.biography" />
            </div>

            <div class="flex gap-3">
                <Button type="submit" :disabled="processing"
                    >Save changes</Button
                >
                <Button as-child variant="outline">
                    <Link :href="AuthorController.show.url(author.id)"
                        >Cancel</Link
                    >
                </Button>
            </div>
        </Form>
    </div>
</template>
