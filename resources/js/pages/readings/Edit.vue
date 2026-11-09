<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import ReadingController from '@/actions/App/Http/Controllers/ReadingController';
import InputError from '@/components/InputError.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Reading = {
    id: string;
    status: string;
    rating: number | null;
    review: string | null;
    private_notes: string | null;
    book: { id: string; title: string };
};

defineProps<{ reading: Reading }>();
</script>

<template>
    <Head title="Edit reading" />

    <div class="flex max-w-3xl flex-col gap-6 p-4">
        <Heading
            variant="small"
            title="Edit reading"
            :description="reading.book.title"
        />

        <Form
            v-bind="ReadingController.update.form(reading.id)"
            v-slot="{ errors, processing }"
            class="grid gap-6"
        >
            <div v-if="reading.status === 'completed'" class="grid gap-2">
                <Label for="rating">Rating</Label>
                <Input
                    id="rating"
                    name="rating"
                    type="number"
                    min="1"
                    max="5"
                    :default-value="reading.rating ?? ''"
                />
                <InputError :message="errors.rating" />
            </div>

            <div class="grid gap-2">
                <Label for="review">Review</Label>
                <textarea
                    id="review"
                    name="review"
                    :value="reading.review ?? ''"
                    class="min-h-48 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                />
                <InputError :message="errors.review" />
            </div>

            <div class="grid gap-2">
                <Label for="private_notes">Private notes</Label>
                <textarea
                    id="private_notes"
                    name="private_notes"
                    :value="reading.private_notes ?? ''"
                    class="min-h-32 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                />
                <InputError :message="errors.private_notes" />
            </div>

            <Button type="submit" :disabled="processing">Save changes</Button>
        </Form>
    </div>
</template>
