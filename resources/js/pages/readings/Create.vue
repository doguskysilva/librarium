<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import ReadingController from '@/actions/App/Http/Controllers/ReadingController';
import InputError from '@/components/InputError.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';

type Book = { id: string; title: string };

defineProps<{ book: Book }>();
</script>

<template>
    <Head title="Start a new reading" />

    <div class="flex max-w-3xl flex-col gap-6 p-4">
        <Heading
            variant="small"
            title="Start a new reading"
            description="Create a queued reading attempt"
        />

        <Form
            v-bind="ReadingController.store.form(book.id)"
            v-slot="{ errors, processing }"
            class="grid gap-6"
        >
            <div class="grid gap-2">
                <Label for="review">Review</Label>
                <textarea
                    id="review"
                    name="review"
                    class="min-h-40 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                    placeholder="Optional Markdown review"
                />
                <InputError :message="errors.review" />
            </div>

            <div class="grid gap-2">
                <Label for="private_notes">Private notes</Label>
                <textarea
                    id="private_notes"
                    name="private_notes"
                    class="min-h-32 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                    placeholder="Optional private Markdown notes"
                />
                <InputError :message="errors.private_notes" />
            </div>

            <Button type="submit" :disabled="processing">Create reading</Button>
        </Form>
    </div>
</template>
