<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    Barcode,
    BookOpen,
    CalendarDays,
    FileText,
    ImageOff,
    Library,
    Tags,
    Users,
    type LucideIcon,
} from '@lucide/vue';
import { computed } from 'vue';
import AuthorController from '@/actions/App/Http/Controllers/AuthorController';
import BookController from '@/actions/App/Http/Controllers/BookController';
import CollectionController from '@/actions/App/Http/Controllers/CollectionController';
import { dashboard } from '@/routes';

type SummaryMetric = {
    label: string;
    count: number;
    href: string;
    icon: LucideIcon;
};

type CategoryMetric = {
    id: string;
    name: string;
    count: number;
    percentage: number;
};

type LanguageMetric = {
    language: string | null;
    label: string;
    count: number;
    percentage: number;
    members?: LanguageMetric[];
};

type TimelineMetric = {
    decadeStart: number | null;
    label: string;
    count: number;
};

const props = defineProps<{
    summary: {
        books: number;
        authors: number;
        collections: number;
    };
    composition: {
        booksByCategory: CategoryMetric[];
        booksByLanguage: LanguageMetric[];
    };
    catalogHealth: {
        missingCovers: number;
        unclassified: number;
        missingIsbn: number;
        missingDescriptions: number;
    };
    publicationTimeline: TimelineMetric[];
}>();

const summaryMetrics = computed<SummaryMetric[]>(() => [
    {
        label: 'Books',
        count: props.summary.books,
        href: BookController.index.url(),
        icon: BookOpen,
    },
    {
        label: 'Authors',
        count: props.summary.authors,
        href: AuthorController.index.url(),
        icon: Users,
    },
    {
        label: 'Collections',
        count: props.summary.collections,
        href: CollectionController.index.url(),
        icon: Library,
    },
]);

const healthMetrics = computed(() => [
    {
        label: 'Missing covers',
        count: props.catalogHealth.missingCovers,
        href: BookController.index.url({
            query: { filter: 'missing_covers' },
        }),
        icon: ImageOff,
    },
    {
        label: 'Unclassified',
        count: props.catalogHealth.unclassified,
        href: BookController.index.url({ query: { filter: 'unclassified' } }),
        icon: Tags,
    },
    {
        label: 'Missing ISBN',
        count: props.catalogHealth.missingIsbn,
        href: BookController.index.url({ query: { filter: 'missing_isbn' } }),
        icon: Barcode,
    },
    {
        label: 'Missing descriptions',
        count: props.catalogHealth.missingDescriptions,
        href: BookController.index.url({
            query: { filter: 'missing_descriptions' },
        }),
        icon: FileText,
    },
]);

const largestCategoryCount = computed(() =>
    Math.max(1, ...props.composition.booksByCategory.map(({ count }) => count)),
);
const largestLanguageCount = computed(() =>
    Math.max(1, ...props.composition.booksByLanguage.map(({ count }) => count)),
);
const largestDecadeCount = computed(() =>
    Math.max(1, ...props.publicationTimeline.map(({ count }) => count)),
);

function languageHref(language: string | null): string {
    return language === null
        ? BookController.index.url({ query: { filter: 'unknown_language' } })
        : BookController.index.url({ query: { language } });
}

function decadeHref(decadeStart: number | null): string {
    return decadeStart === null
        ? BookController.index.url({ query: { filter: 'unknown_year' } })
        : BookController.index.url({
              query: {
                  year_from: decadeStart,
                  year_to: decadeStart + 9,
              },
          });
}

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Library overview" />

    <div class="flex w-full flex-1 flex-col gap-8 p-4">
        <header class="grid gap-2">
            <h1 class="text-2xl font-semibold tracking-tight">
                Library overview
            </h1>
            <p class="text-sm text-muted-foreground">
                A snapshot of your catalog and its completeness.
            </p>
        </header>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <Link
                v-for="metric in summaryMetrics"
                :key="metric.label"
                :href="metric.href"
                class="flex min-h-36 flex-col justify-between gap-6 rounded-xl border border-sidebar-border/70 p-5 transition-colors hover:bg-muted/30 dark:border-sidebar-border"
            >
                <div class="flex items-center justify-between gap-4">
                    <h2 class="text-sm font-medium text-muted-foreground">
                        {{ metric.label }}
                    </h2>
                    <component
                        :is="metric.icon"
                        aria-hidden="true"
                        class="size-5 text-muted-foreground"
                    />
                </div>
                <p class="text-3xl font-semibold tabular-nums">
                    {{ metric.count }}
                </p>
            </Link>
        </section>

        <section class="grid gap-4 xl:grid-cols-2">
            <article
                class="grid content-start gap-5 rounded-xl border border-sidebar-border/70 p-5 dark:border-sidebar-border"
            >
                <div class="grid gap-1">
                    <h2 class="font-semibold">Books by category</h2>
                    <p class="text-sm text-muted-foreground">
                        Top categories as a share of the full catalog. Books may
                        appear in more than one category.
                    </p>
                </div>

                <ul
                    v-if="composition.booksByCategory.length"
                    class="grid gap-4"
                >
                    <li
                        v-for="category in composition.booksByCategory"
                        :key="category.id"
                        class="grid gap-2"
                    >
                        <div
                            class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1 text-sm"
                        >
                            <Link
                                :href="
                                    BookController.index.url({
                                        query: { category: category.id },
                                    })
                                "
                                class="min-w-0 font-medium break-words hover:underline"
                            >
                                {{ category.name }}
                            </Link>
                            <span class="text-muted-foreground">
                                {{ category.count }} ·
                                {{ category.percentage }}%
                            </span>
                        </div>
                        <div
                            role="img"
                            :aria-label="`${category.name}: ${category.count} books, ${category.percentage}% of catalog`"
                            class="h-2 overflow-hidden rounded-full bg-muted"
                        >
                            <div
                                class="h-full rounded-full bg-primary"
                                :style="{
                                    width: `${Math.max(4, (category.count / largestCategoryCount) * 100)}%`,
                                }"
                            />
                        </div>
                    </li>
                </ul>
                <p
                    v-else
                    class="rounded-md border border-dashed p-6 text-center text-sm text-muted-foreground"
                >
                    No books have category assignments yet.
                </p>
            </article>

            <article
                class="grid content-start gap-5 rounded-xl border border-sidebar-border/70 p-5 dark:border-sidebar-border"
            >
                <div class="grid gap-1">
                    <h2 class="font-semibold">Books by language</h2>
                    <p class="text-sm text-muted-foreground">
                        Distribution by the language stored on each book.
                    </p>
                </div>

                <ul
                    v-if="composition.booksByLanguage.length"
                    class="grid gap-4"
                >
                    <li
                        v-for="language in composition.booksByLanguage"
                        :key="language.label"
                        class="grid gap-2"
                    >
                        <div
                            class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1 text-sm"
                        >
                            <Link
                                v-if="language.label !== 'Other'"
                                :href="languageHref(language.language)"
                                class="min-w-0 font-medium break-words hover:underline"
                            >
                                {{ language.label }}
                            </Link>
                            <span v-else class="font-medium">
                                {{ language.label }}
                            </span>
                            <span class="text-muted-foreground">
                                {{ language.count }} ·
                                {{ language.percentage }}%
                            </span>
                        </div>
                        <div
                            role="img"
                            :aria-label="`${language.label}: ${language.count} books, ${language.percentage}% of catalog`"
                            class="h-2 overflow-hidden rounded-full bg-muted"
                        >
                            <div
                                class="h-full rounded-full bg-primary"
                                :style="{
                                    width: `${Math.max(4, (language.count / largestLanguageCount) * 100)}%`,
                                }"
                            />
                        </div>
                        <details
                            v-if="language.members?.length"
                            class="text-sm text-muted-foreground"
                        >
                            <summary
                                class="w-fit cursor-pointer hover:text-foreground"
                            >
                                Show languages grouped as Other
                            </summary>
                            <ul class="mt-2 grid gap-2 pl-4">
                                <li
                                    v-for="member in language.members"
                                    :key="member.label"
                                >
                                    <Link
                                        :href="languageHref(member.language)"
                                        class="hover:text-foreground hover:underline"
                                    >
                                        {{ member.label }} ·
                                        {{ member.count }} ({{
                                            member.percentage
                                        }}%)
                                    </Link>
                                </li>
                            </ul>
                        </details>
                    </li>
                </ul>
                <p
                    v-else
                    class="rounded-md border border-dashed p-6 text-center text-sm text-muted-foreground"
                >
                    No books have been cataloged yet.
                </p>
            </article>
        </section>

        <section class="grid gap-4">
            <div class="grid gap-1">
                <h2 class="text-lg font-semibold">Catalog Health</h2>
                <p class="text-sm text-muted-foreground">
                    Select a count to review books that need catalog details.
                </p>
            </div>
            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <Link
                    v-for="metric in healthMetrics"
                    :key="metric.label"
                    :href="metric.href"
                    class="flex items-center justify-between gap-4 rounded-xl border border-sidebar-border/70 p-4 transition-colors hover:bg-muted/30 dark:border-sidebar-border"
                >
                    <div class="grid gap-1">
                        <span class="text-sm text-muted-foreground">
                            {{ metric.label }}
                        </span>
                        <span class="text-2xl font-semibold tabular-nums">
                            {{ metric.count }}
                        </span>
                    </div>
                    <component
                        :is="metric.icon"
                        aria-hidden="true"
                        class="size-5 shrink-0 text-muted-foreground"
                    />
                </Link>
            </div>
        </section>

        <section
            class="grid gap-5 rounded-xl border border-sidebar-border/70 p-5 dark:border-sidebar-border"
        >
            <div class="flex items-center gap-3">
                <CalendarDays
                    aria-hidden="true"
                    class="size-5 text-muted-foreground"
                />
                <div class="grid gap-1">
                    <h2 class="font-semibold">Publication timeline</h2>
                    <p class="text-sm text-muted-foreground">
                        Books grouped by publication decade.
                    </p>
                </div>
            </div>

            <ul
                v-if="publicationTimeline.length"
                class="grid gap-4 sm:grid-cols-2"
            >
                <li
                    v-for="decade in publicationTimeline"
                    :key="decade.label"
                    class="grid gap-2"
                >
                    <div
                        class="flex items-baseline justify-between gap-4 text-sm"
                    >
                        <Link
                            :href="decadeHref(decade.decadeStart)"
                            class="font-medium hover:underline"
                        >
                            {{ decade.label }}
                        </Link>
                        <span class="text-muted-foreground">
                            {{ decade.count }}
                        </span>
                    </div>
                    <div
                        role="img"
                        :aria-label="`${decade.label}: ${decade.count} books`"
                        class="h-2 overflow-hidden rounded-full bg-muted"
                    >
                        <div
                            class="h-full rounded-full bg-primary"
                            :style="{
                                width: `${Math.max(4, (decade.count / largestDecadeCount) * 100)}%`,
                            }"
                        />
                    </div>
                </li>
            </ul>
            <p
                v-else
                class="rounded-md border border-dashed p-6 text-center text-sm text-muted-foreground"
            >
                Publication years will appear here as they are added to the
                catalog.
            </p>
        </section>
    </div>
</template>
