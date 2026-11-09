<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import ReadingController from '@/actions/App/Http/Controllers/ReadingController';
import BookController from '@/actions/App/Http/Controllers/BookController';
import ReadingDateForm from '@/components/ReadingDateForm.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';

type Reading = {
    id: string;
    status: string;
    started_on: string | null;
    started_year: number | null;
    finished_on: string | null;
    finished_year: number | null;
    rating: number | null;
    review: string | null;
    private_notes: string | null;
    review_html: string | null;
    private_notes_html: string | null;
    book: { id: string; title: string };
};

defineProps<{ reading: Reading }>();
</script>

<template>
    <Head :title="`Reading · ${reading.book.title}`" />

    <div class="flex max-w-3xl flex-col gap-6 p-4">
        <div class="flex items-start justify-between gap-4">
            <Heading
                variant="small"
                :title="reading.book.title"
                :description="`Reading · ${reading.status}`"
            />
            <div class="flex gap-2">
                <Button as-child variant="outline">
                    <Link :href="BookController.show.url(reading.book.id)"
                        >Book</Link
                    >
                </Button>
                <Button as-child>
                    <Link :href="ReadingController.edit.url(reading.id)"
                        >Edit</Link
                    >
                </Button>
            </div>
        </div>

        <dl class="grid gap-4 rounded-xl border p-6 sm:grid-cols-2">
            <div>
                <dt class="text-sm text-muted-foreground">Status</dt>
                <dd class="capitalize">{{ reading.status }}</dd>
            </div>
            <div>
                <dt class="text-sm text-muted-foreground">Rating</dt>
                <dd>{{ reading.rating ?? 'Not rated' }}</dd>
            </div>
            <div>
                <dt class="text-sm text-muted-foreground">Started</dt>
                <dd>
                    {{
                        reading.started_on ?? reading.started_year ?? 'Unknown'
                    }}
                </dd>
            </div>
            <div>
                <dt class="text-sm text-muted-foreground">Finished / closed</dt>
                <dd>
                    {{
                        reading.finished_on ??
                        reading.finished_year ??
                        'Unknown'
                    }}
                </dd>
            </div>
        </dl>

        <section
            v-if="reading.review_html"
            class="grid gap-3 rounded-xl border p-6"
        >
            <h2 class="font-medium">Review</h2>
            <div
                class="prose prose-sm dark:prose-invert max-w-none"
                v-html="reading.review_html"
            />
        </section>

        <section
            v-if="reading.private_notes_html"
            class="grid gap-3 rounded-xl border p-6"
        >
            <h2 class="font-medium">Private notes</h2>
            <div
                class="prose prose-sm dark:prose-invert max-w-none"
                v-html="reading.private_notes_html"
            />
        </section>

        <section class="grid gap-4 rounded-xl border p-6">
            <h2 class="font-medium">Actions</h2>

            <ReadingDateForm
                v-if="reading.status === 'queued'"
                :actions="[
                    {
                        value: 'start',
                        label: 'Start reading',
                        needsDate: true,
                        action: ReadingController.start.form(reading.id),
                    },
                    {
                        value: 'skip',
                        label: 'Skip reading',
                        needsDate: true,
                        action: ReadingController.skip.form(reading.id),
                    },
                ]"
            />
            <ReadingDateForm
                v-if="reading.status === 'reading'"
                :actions="[
                    {
                        value: 'pause',
                        label: 'Pause reading',
                        needsDate: false,
                        action: ReadingController.pause.form(reading.id),
                    },
                    {
                        value: 'complete',
                        label: 'Complete reading',
                        needsDate: true,
                        action: ReadingController.complete.form(reading.id),
                    },
                    {
                        value: 'abandon',
                        label: 'Abandon reading',
                        needsDate: true,
                        action: ReadingController.abandon.form(reading.id),
                    },
                ]"
            />
            <ReadingDateForm
                v-if="reading.status === 'paused'"
                :actions="[
                    {
                        value: 'resume',
                        label: 'Resume reading',
                        needsDate: false,
                        action: ReadingController.resume.form(reading.id),
                    },
                    {
                        value: 'complete',
                        label: 'Complete reading',
                        needsDate: true,
                        action: ReadingController.complete.form(reading.id),
                    },
                    {
                        value: 'abandon',
                        label: 'Abandon reading',
                        needsDate: true,
                        action: ReadingController.abandon.form(reading.id),
                    },
                ]"
            />

            <p
                v-if="
                    ['completed', 'skipped', 'abandoned'].includes(
                        reading.status,
                    )
                "
                class="text-sm text-muted-foreground"
            >
                This reading is closed. Start a new reading from the book to
                record a reread.
            </p>
        </section>
    </div>
</template>
