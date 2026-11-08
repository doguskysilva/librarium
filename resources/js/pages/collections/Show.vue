<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import CollectionController from '@/actions/App/Http/Controllers/CollectionController';
import { Button } from '@/components/ui/button';

type Collection = {
    id: string;
    name: string;
    type: string;
    description: string | null;
};

const typeLabels: Record<string, string> = {
    editorial: 'Editorial',
    literary_series: 'Literary series',
    fictional_universe: 'Fictional universe',
    author_bibliography: 'Author bibliography',
    thematic: 'Thematic',
};

defineProps<{ collection: Collection }>();
</script>

<template>
    <Head :title="collection.name" />

    <div class="flex max-w-3xl flex-col gap-6 p-4">
        <div class="flex items-start justify-between gap-8 py-3">
            <div class="space-y-2">
                <h1 class="text-2xl font-semibold tracking-tight">
                    {{ collection.name }}
                </h1>
                <p class="text-sm text-muted-foreground">
                    {{ typeLabels[collection.type] ?? collection.type }}
                </p>
            </div>
            <Button as-child>
                <Link :href="CollectionController.edit.url(collection.id)"
                    >Edit</Link
                >
            </Button>
        </div>

        <div
            class="rounded-xl border border-sidebar-border/70 p-6 dark:border-sidebar-border"
        >
            <h2 class="text-sm font-medium text-muted-foreground">
                Description
            </h2>
            <p class="mt-3 whitespace-pre-wrap">
                {{ collection.description ?? 'No description recorded.' }}
            </p>
        </div>
    </div>
</template>
