<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { useHttp } from '@inertiajs/vue3';
import BookMetadataController from '@/actions/App/Http/Controllers/BookMetadataController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';

type BookMetadata = {
    provider: string;
    title: string;
    subtitle: string | null;
    authors: string[];
    isbn: string | null;
    publisher: string | null;
    edition: string | null;
    publicationYear: number | null;
    language: string | null;
    description: string | null;
    coverUrl: string | null;
};

type ProviderResult = {
    metadata: BookMetadata | null;
    error: string | null;
};

type BookMetadataResults = {
    google_books: ProviderResult;
    open_library: ProviderResult;
};

type LocalBook = {
    title: string;
    subtitle: string | null;
    isbn: string | null;
    publisher: string | null;
    edition: string | null;
    publication_year: number | null;
    language: string | null;
    description: string | null;
    cover_url: string | null;
    authors: Array<{ id: string; name: string }>;
};

type MetadataField =
    | 'title'
    | 'subtitle'
    | 'authors'
    | 'isbn'
    | 'publisher'
    | 'edition'
    | 'publicationYear'
    | 'language'
    | 'description';

type FieldStatus = 'same' | 'different' | 'absent' | null;
type EditionStatus = 'same' | 'different' | 'absent' | 'unconfirmed' | null;

type ComparisonSource = {
    key: string;
    label: string;
    metadata: BookMetadata | null;
    error: string | null;
};

const props = defineProps<{
    initialIsbn?: string | null;
    localBook?: LocalBook | null;
}>();

const open = defineModel<boolean>('open', { default: false });
const metadataResults = ref<BookMetadataResults | null>(null);
const metadataRequestError = ref('');
const metadataLookup = useHttp<{ isbn: string }, BookMetadataResults>({
    isbn: '',
});
const comparisonFields: Array<{ key: MetadataField; label: string }> = [
    { key: 'authors', label: 'Authors' },
    { key: 'isbn', label: 'ISBN' },
    { key: 'publisher', label: 'Publisher' },
    { key: 'edition', label: 'Edition' },
    { key: 'publicationYear', label: 'Publication year' },
    { key: 'language', label: 'Language' },
];

const sources = computed<ComparisonSource[]>(() => {
    const providerSources = [
        {
            key: 'open_library',
            label: 'Open Library',
            metadata: metadataResults.value?.open_library.metadata ?? null,
            error: metadataResults.value?.open_library.error ?? null,
        },
        {
            key: 'google_books',
            label: 'Google Books',
            metadata: metadataResults.value?.google_books.metadata ?? null,
            error: metadataResults.value?.google_books.error ?? null,
        },
    ];

    if (!props.localBook) {
        return providerSources;
    }

    return [
        {
            key: 'local',
            label: 'Local Catalog',
            metadata: {
                provider: 'local',
                title: props.localBook.title,
                subtitle: props.localBook.subtitle,
                authors: props.localBook.authors.map((author) => author.name),
                isbn: props.localBook.isbn,
                publisher: props.localBook.publisher,
                edition: props.localBook.edition,
                publicationYear: props.localBook.publication_year,
                language: props.localBook.language,
                description: props.localBook.description,
                coverUrl: props.localBook.cover_url,
            },
            error: null,
        },
        ...providerSources,
    ];
});

function canonicalIsbn(value: string | null): string | null {
    const isbn = value?.replace(/[\s-]/g, '').toUpperCase();

    if (!isbn) {
        return null;
    }

    if (/^\d{9}[\dX]$/.test(isbn)) {
        const sum = [...isbn].reduce(
            (total, digit, index) =>
                total + (digit === 'X' ? 10 : Number(digit)) * (10 - index),
            0,
        );

        if (sum % 11 !== 0) {
            return null;
        }

        const prefix = `978${isbn.slice(0, 9)}`;
        const checksum =
            (10 -
                ([...prefix].reduce(
                    (total, digit, index) =>
                        total + Number(digit) * (index % 2 === 0 ? 1 : 3),
                    0,
                ) %
                    10)) %
            10;

        return `${prefix}${checksum}`;
    }

    if (/^\d{13}$/.test(isbn)) {
        const checksum =
            Array.from(isbn.slice(0, 12)).reduce(
                (total, digit, index) =>
                    total + Number(digit) * (index % 2 === 0 ? 1 : 3),
                0,
            ) % 10;

        return Number(isbn[12]) === (10 - checksum) % 10 ? isbn : null;
    }

    return null;
}

function editionStatus(source: ComparisonSource): EditionStatus {
    if (!source.metadata) {
        return null;
    }

    const requestedIsbn = canonicalIsbn(metadataLookup.isbn);

    if (!requestedIsbn) {
        return 'unconfirmed';
    }

    const sourceIsbn = canonicalIsbn(source.metadata.isbn);

    if (!sourceIsbn) {
        return 'absent';
    }

    return sourceIsbn === requestedIsbn ? 'same' : 'different';
}

function normalizedField(
    source: ComparisonSource,
    field: MetadataField,
): string | null {
    const value = source.metadata?.[field];

    if (value === null || value === undefined || value === '') {
        return null;
    }

    if (field === 'authors' && Array.isArray(value)) {
        const authors = value
            .map(normalizeText)
            .filter(Boolean)
            .sort()
            .join('|');

        return authors || null;
    }

    if (field === 'isbn' && typeof value === 'string') {
        return canonicalIsbn(value);
    }

    if (field === 'language' && typeof value === 'string') {
        return normalizeLanguage(value) || null;
    }

    return normalizeText(String(value)) || null;
}

function normalizeText(value: string): string {
    return value
        .normalize('NFD')
        .replace(/\p{Diacritic}/gu, '')
        .toLowerCase()
        .replace(/[^\p{Letter}\p{Number}]+/gu, ' ')
        .trim()
        .replace(/\s+/g, ' ');
}

function normalizeLanguage(value: string): string {
    const normalized = value.trim().replaceAll('_', '-').toLowerCase();
    const languageAliases: Record<string, string> = {
        eng: 'en',
        en: 'en',
        por: 'pt',
        pt: 'pt',
        'pt-br': 'pt-br',
        spa: 'es',
        es: 'es',
        fre: 'fr',
        fra: 'fr',
        fr: 'fr',
    };

    return languageAliases[normalized] ?? normalized;
}

function fieldStatus(
    source: ComparisonSource,
    field: MetadataField,
): FieldStatus {
    if (!source.metadata) {
        return null;
    }

    if (field === 'isbn') {
        const status = editionStatus(source);

        return status === 'same' ||
            status === 'different' ||
            status === 'absent'
            ? status
            : null;
    }

    if (editionStatus(source) !== 'same') {
        return null;
    }

    const comparableSources = sources.value.filter(
        (candidate) =>
            candidate.metadata !== null && editionStatus(candidate) === 'same',
    );
    const value = normalizedField(source, field);
    const localSource = comparableSources.find(
        (candidate) => candidate.key === 'local',
    );

    if (localSource && source.key === 'local') {
        const localValue = normalizedField(source, field);
        const providerValues = comparableSources
            .filter((candidate) => candidate.key !== 'local')
            .map((candidate) => normalizedField(candidate, field))
            .filter((candidate): candidate is string => candidate !== null);

        if (localValue === null) {
            return providerValues.length ? 'absent' : null;
        }

        if (providerValues.length === 0) {
            return null;
        }

        return providerValues.every((candidate) => candidate === localValue)
            ? 'same'
            : 'different';
    }

    if (localSource && source.key !== 'local') {
        const localValue = normalizedField(localSource, field);

        if (localValue === null) {
            return null;
        }

        if (value === null) {
            return 'absent';
        }

        return value === localValue ? 'same' : 'different';
    }

    if (value === null) {
        return comparableSources.some(
            (candidate) => normalizedField(candidate, field) !== null,
        )
            ? 'absent'
            : null;
    }

    const values = comparableSources
        .map((candidate) => normalizedField(candidate, field))
        .filter((candidate): candidate is string => candidate !== null);

    if (values.length < 2) {
        return null;
    }

    return values.every((candidate) => candidate === values[0])
        ? 'same'
        : 'different';
}

function statusLabel(status: FieldStatus | EditionStatus): string {
    if (!status) {
        return '';
    }

    return {
        same: 'Same',
        different: 'Different',
        absent: 'Not provided',
        unconfirmed: 'Edition unconfirmed',
    }[status];
}

function statusClass(status: FieldStatus | EditionStatus): string {
    if (!status) {
        return '';
    }

    return {
        same: 'border-emerald-500/30 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300',
        different:
            'border-amber-500/40 bg-amber-500/10 text-amber-800 dark:text-amber-300',
        absent: 'border-muted bg-muted text-muted-foreground',
        unconfirmed: 'border-muted bg-muted text-muted-foreground',
    }[status];
}

watch(open, (isOpen) => {
    if (!isOpen) {
        return;
    }

    metadataLookup.resetAndClearErrors();
    metadataLookup.isbn = props.initialIsbn ?? '';
    metadataResults.value = null;
    metadataRequestError.value = '';
});

async function findMetadata() {
    metadataResults.value = null;
    metadataRequestError.value = '';

    try {
        metadataResults.value = await metadataLookup.post(
            BookMetadataController.lookup.url(),
        );
    } catch {
        metadataRequestError.value =
            'The metadata lookup could not be completed. Please try again.';
    }
}
</script>

<template>
    <Dialog :open="open" @update:open="open = $event">
        <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-7xl">
            <DialogHeader>
                <DialogTitle>Compare book metadata</DialogTitle>
                <DialogDescription>
                    Compare your catalog entry with Google Books and Open
                    Library. Provider results are not saved to your catalog.
                </DialogDescription>
            </DialogHeader>

            <form
                class="flex flex-col gap-2 sm:flex-row"
                @submit.prevent="findMetadata"
            >
                <div class="flex-1">
                    <Input
                        v-model="metadataLookup.isbn"
                        aria-label="ISBN"
                        placeholder="ISBN-10 or ISBN-13"
                        autocomplete="off"
                        :aria-invalid="Boolean(metadataLookup.errors.isbn)"
                    />
                    <InputError :message="metadataLookup.errors.isbn" />
                </div>
                <Button type="submit" :disabled="metadataLookup.processing">
                    {{
                        metadataLookup.processing
                            ? 'Searching…'
                            : 'Search providers'
                    }}
                </Button>
            </form>

            <p v-if="metadataRequestError" class="text-sm text-destructive">
                {{ metadataRequestError }}
            </p>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <section
                    v-for="source in sources"
                    :key="source.key"
                    class="flex flex-col gap-3 rounded-xl border p-4"
                >
                    <h3 class="font-semibold">{{ source.label }}</h3>
                    <p
                        v-if="
                            source.metadata &&
                            editionStatus(source) === 'different'
                        "
                        class="text-sm text-amber-800 dark:text-amber-300"
                    >
                        This ISBN identifies a different edition; other fields
                        are not compared.
                    </p>
                    <p
                        v-else-if="
                            source.metadata &&
                            editionStatus(source) === 'unconfirmed'
                        "
                        class="text-sm text-muted-foreground"
                    >
                        Search a valid ISBN to confirm this edition before
                        comparing other fields.
                    </p>
                    <p
                        v-else-if="
                            source.metadata &&
                            editionStatus(source) === 'absent'
                        "
                        class="text-sm text-muted-foreground"
                    >
                        ISBN not provided; other fields are not compared.
                    </p>
                    <p
                        v-if="source.key !== 'local' && !metadataResults"
                        class="text-sm text-muted-foreground"
                    >
                        Search an ISBN to compare this source.
                    </p>
                    <p
                        v-else-if="source.error"
                        class="text-sm text-destructive"
                    >
                        {{ source.error }}
                    </p>
                    <template v-else-if="source.metadata">
                        <div
                            v-if="source.metadata.coverUrl"
                            class="flex justify-center rounded-lg bg-muted p-3"
                        >
                            <img
                                :src="source.metadata.coverUrl"
                                :alt="`Cover of ${source.metadata.title}`"
                                class="max-h-48 max-w-full object-contain"
                            />
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h4 class="text-lg font-medium">
                                {{ source.metadata.title }}
                            </h4>
                            <span
                                v-if="fieldStatus(source, 'title')"
                                :class="[
                                    'rounded-full border px-2 py-0.5 text-xs',
                                    statusClass(fieldStatus(source, 'title')),
                                ]"
                            >
                                {{ statusLabel(fieldStatus(source, 'title')) }}
                            </span>
                        </div>
                        <p
                            v-if="
                                source.metadata.subtitle ||
                                fieldStatus(source, 'subtitle')
                            "
                            class="flex flex-wrap items-center gap-2 text-sm text-muted-foreground"
                        >
                            <span>
                                {{ source.metadata.subtitle ?? 'Not provided' }}
                            </span>
                            <span
                                v-if="fieldStatus(source, 'subtitle')"
                                :class="[
                                    'rounded-full border px-2 py-0.5 text-xs',
                                    statusClass(
                                        fieldStatus(source, 'subtitle'),
                                    ),
                                ]"
                            >
                                {{
                                    statusLabel(fieldStatus(source, 'subtitle'))
                                }}
                            </span>
                        </p>
                        <dl class="grid gap-2 text-sm">
                            <div
                                v-for="field in comparisonFields"
                                :key="field.key"
                                class="grid grid-cols-[7rem_1fr] gap-2"
                            >
                                <dt class="text-muted-foreground">
                                    {{ field.label }}
                                </dt>
                                <dd class="flex flex-wrap items-center gap-2">
                                    <span>
                                        {{
                                            field.key === 'authors'
                                                ? source.metadata.authors.join(
                                                      ', ',
                                                  ) || 'Not provided'
                                                : (source.metadata[field.key] ??
                                                  'Not provided')
                                        }}
                                    </span>
                                    <span
                                        v-if="fieldStatus(source, field.key)"
                                        :class="[
                                            'rounded-full border px-2 py-0.5 text-xs',
                                            statusClass(
                                                fieldStatus(source, field.key),
                                            ),
                                        ]"
                                    >
                                        {{
                                            statusLabel(
                                                fieldStatus(source, field.key),
                                            )
                                        }}
                                    </span>
                                </dd>
                            </div>
                        </dl>
                        <p
                            v-if="
                                source.metadata.description ||
                                fieldStatus(source, 'description')
                            "
                            class="flex flex-wrap items-start gap-2 text-sm text-muted-foreground"
                        >
                            <span class="line-clamp-5 whitespace-pre-wrap">{{
                                source.metadata.description ?? 'Not provided'
                            }}</span>
                            <span
                                v-if="fieldStatus(source, 'description')"
                                :class="[
                                    'rounded-full border px-2 py-0.5 text-xs',
                                    statusClass(
                                        fieldStatus(source, 'description'),
                                    ),
                                ]"
                            >
                                {{
                                    statusLabel(
                                        fieldStatus(source, 'description'),
                                    )
                                }}
                            </span>
                        </p>
                    </template>
                    <p v-else class="text-sm text-muted-foreground">
                        No matching book was found.
                    </p>
                </section>
            </div>

            <DialogFooter>
                <Button type="button" variant="outline" @click="open = false">
                    Close
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
