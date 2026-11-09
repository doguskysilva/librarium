<script setup lang="ts">
import { useHttp, Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import InventoryImportController from '@/actions/App/Http/Controllers/InventoryImportController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';

type ImportAction =
    | 'create'
    | 'update'
    | 'unchanged'
    | 'additive'
    | 'skipped'
    | 'ambiguous'
    | 'conflict';

type ImportRecord = {
    key?: string;
    name?: string;
    source_key?: string;
    collection?: string;
    action: ImportAction;
    reason?: string;
    matched_by?: string;
    preserved_fields?: string[];
    unverified_fields?: string[];
};

type ImportReport = {
    status: 'dry_run' | 'applied' | 'invalid' | 'blocked';
    inventory?: {
        name: string;
        is_complete: boolean;
    };
    summary?: Record<string, Record<string, number>>;
    records?: Record<string, ImportRecord[]>;
    errors?: Record<string, string[]>;
    record_labels?: Record<string, Record<number, string>>;
    notes?: string[];
};

const http = useHttp<{ file: File | null }, ImportReport>({ file: null });
const report = ref<ImportReport | null>(null);
const confirmed = ref(false);
const requestError = ref<string | null>(null);

const recordGroups = computed(() =>
    Object.entries(report.value?.records ?? {}).map(([type, records]) => ({
        type,
        records,
    })),
);

function recordIdentity(record: ImportRecord): string {
    if (record.key) {
        return record.key;
    }

    if (record.source_key && record.collection) {
        return `${record.source_key} → ${record.collection}`;
    }

    return (
        record.name ??
        record.source_key ??
        record.collection ??
        'Inventory record'
    );
}

function errorIdentity(path: string): string | null {
    const [entity, index] = path.split('.');

    if (!entity || index === undefined || !/^\d+$/.test(index)) {
        return null;
    }

    return report.value?.record_labels?.[entity]?.[Number(index)] ?? null;
}

function selectFile(event: Event): void {
    const input = event.target as HTMLInputElement;

    http.file = input.files?.[0] ?? null;
    report.value = null;
    confirmed.value = false;
    requestError.value = null;
    http.clearErrors();
}

async function preview(): Promise<void> {
    await submit(InventoryImportController.preview.url());
}

async function applyImport(): Promise<void> {
    await submit(InventoryImportController.apply.url());
}

async function submit(url: string): Promise<void> {
    report.value = null;
    requestError.value = null;

    try {
        report.value = await http.post(url);
        confirmed.value = false;
    } catch {
        requestError.value =
            'The upload could not be completed. Check the file and try again.';
    }
}

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Import inventory',
                href: InventoryImportController.index.url(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Import inventory" />

    <div class="flex w-full max-w-5xl flex-col gap-6 p-4">
        <Heading
            title="Import inventory"
            description="Upload a Librarium inventory JSON file, review a dry-run report, then apply verified records."
        />

        <section
            class="grid gap-5 rounded-xl border border-sidebar-border/70 p-6 dark:border-sidebar-border"
        >
            <div class="grid gap-2">
                <Label for="inventory-file">Inventory JSON</Label>
                <input
                    id="inventory-file"
                    type="file"
                    accept=".json,application/json"
                    class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm file:mr-4 file:rounded-md file:border-0 file:bg-muted file:px-3 file:py-1 file:text-sm file:font-medium"
                    @change="selectFile"
                />
                <p class="text-sm text-muted-foreground">
                    JSON files up to 10 MB. Previewing never changes your
                    catalog.
                </p>
                <InputError
                    :message="
                        typeof http.errors.file === 'string'
                            ? http.errors.file
                            : undefined
                    "
                />
            </div>

            <div v-if="http.progress" class="grid gap-2">
                <div
                    class="flex justify-between gap-4 text-sm text-muted-foreground"
                >
                    <span>Uploading inventory</span>
                    <span>{{ http.progress.percentage }}%</span>
                </div>
                <progress
                    class="h-2 w-full accent-primary"
                    :value="http.progress.percentage"
                    max="100"
                />
            </div>

            <p
                v-if="requestError"
                role="alert"
                class="text-sm text-destructive"
            >
                {{ requestError }}
            </p>

            <div class="flex flex-wrap gap-3">
                <Button
                    type="button"
                    :disabled="!http.file || http.processing"
                    @click="preview"
                >
                    {{ http.processing ? 'Processing…' : 'Preview import' }}
                </Button>
            </div>
        </section>

        <section
            v-if="report"
            class="grid gap-5 rounded-xl border border-sidebar-border/70 p-6 dark:border-sidebar-border"
            aria-live="polite"
        >
            <div class="grid gap-2">
                <h2 class="text-lg font-semibold">
                    {{
                        report.status === 'applied'
                            ? 'Import complete'
                            : report.status === 'dry_run'
                              ? 'Dry-run results'
                              : 'Import could not be applied'
                    }}
                </h2>
                <p
                    v-if="report.inventory"
                    class="text-sm text-muted-foreground"
                >
                    {{ report.inventory.name }}
                    <span v-if="!report.inventory.is_complete">
                        · Inventory marked incomplete
                    </span>
                </p>
                <p
                    v-if="report.status === 'dry_run'"
                    class="text-sm text-muted-foreground"
                >
                    No database changes have been made. Review the counts and
                    conflicts below.
                </p>
            </div>

            <div
                v-if="report.errors"
                class="grid gap-2 rounded-md border border-destructive/40 p-4"
            >
                <h3 class="font-medium text-destructive">Review required</h3>
                <p
                    v-for="(messages, path) in report.errors"
                    :key="path"
                    class="text-sm text-muted-foreground"
                >
                    <strong>{{ path }}</strong>
                    <span v-if="errorIdentity(path)">
                        · {{ errorIdentity(path) }}
                    </span>
                    : {{ messages.join(' ') }}
                </p>
            </div>

            <div v-if="report.summary" class="overflow-x-auto">
                <table class="w-full min-w-[640px] text-left text-sm">
                    <thead>
                        <tr class="border-b text-muted-foreground">
                            <th class="py-2 pr-4 font-medium">Records</th>
                            <th class="px-2 py-2 font-medium">Create</th>
                            <th class="px-2 py-2 font-medium">Update</th>
                            <th class="px-2 py-2 font-medium">Unchanged</th>
                            <th class="px-2 py-2 font-medium">Add</th>
                            <th class="px-2 py-2 font-medium">Skipped</th>
                            <th class="px-2 py-2 font-medium">Conflicts</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(counts, type) in report.summary"
                            :key="type"
                            class="border-b last:border-0"
                        >
                            <th class="py-3 pr-4 font-medium capitalize">
                                {{ type.replaceAll('_', ' ') }}
                            </th>
                            <td class="px-2 py-3">{{ counts.create ?? 0 }}</td>
                            <td class="px-2 py-3">{{ counts.update ?? 0 }}</td>
                            <td class="px-2 py-3">
                                {{ counts.unchanged ?? 0 }}
                            </td>
                            <td class="px-2 py-3">
                                {{ counts.additive ?? 0 }}
                            </td>
                            <td class="px-2 py-3">{{ counts.skipped ?? 0 }}</td>
                            <td class="px-2 py-3">
                                {{
                                    (counts.conflict ?? 0) +
                                    (counts.ambiguous ?? 0)
                                }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="recordGroups.length" class="grid gap-3">
                <h3 class="font-medium">Results for every record</h3>
                <details
                    v-for="group in recordGroups"
                    :key="group.type"
                    open
                    class="rounded-md border"
                >
                    <summary
                        class="cursor-pointer px-4 py-3 font-medium capitalize"
                    >
                        {{ group.type.replaceAll('_', ' ') }}
                        <span class="text-sm font-normal text-muted-foreground">
                            · {{ group.records.length }} records
                        </span>
                    </summary>
                    <ul class="grid gap-2 border-t p-3">
                        <li
                            v-for="(record, index) in group.records"
                            :key="`${recordIdentity(record)}-${index}`"
                            class="grid gap-1 rounded-md bg-muted/40 p-3 text-sm"
                        >
                            <p
                                class="flex flex-wrap items-center gap-2 font-medium"
                            >
                                <span>{{ recordIdentity(record) }}</span>
                                <span
                                    class="rounded-full border px-2 py-0.5 text-xs capitalize"
                                >
                                    {{ record.action }}
                                </span>
                            </p>
                            <p
                                v-if="record.reason"
                                class="text-muted-foreground"
                            >
                                {{ record.reason }}
                            </p>
                            <p
                                v-if="record.matched_by"
                                class="text-muted-foreground"
                            >
                                Matched by {{ record.matched_by }}.
                            </p>
                            <p
                                v-if="record.preserved_fields?.length"
                                class="text-muted-foreground"
                            >
                                Existing values preserved:
                                {{ record.preserved_fields.join(', ') }}.
                            </p>
                            <p
                                v-if="record.unverified_fields?.length"
                                class="text-muted-foreground"
                            >
                                Not imported because unverified:
                                {{ record.unverified_fields.join(', ') }}.
                            </p>
                        </li>
                    </ul>
                </details>
            </div>

            <ul
                v-if="report.notes?.length"
                class="grid gap-1 text-sm text-muted-foreground"
            >
                <li v-for="note in report.notes" :key="note">{{ note }}</li>
            </ul>

            <div
                v-if="
                    report.status === 'dry_run' && report.inventory?.is_complete
                "
                class="grid gap-4 border-t pt-5"
            >
                <label class="flex items-start gap-3 text-sm">
                    <input
                        v-model="confirmed"
                        type="checkbox"
                        class="mt-0.5 size-4 rounded border-input accent-primary"
                    />
                    <span>
                        I reviewed this dry-run report and want to apply the
                        verified records.
                    </span>
                </label>
                <div>
                    <Button
                        type="button"
                        variant="destructive"
                        :disabled="!confirmed || !http.file || http.processing"
                        @click="applyImport"
                    >
                        {{
                            http.processing
                                ? 'Applying…'
                                : 'Apply verified records'
                        }}
                    </Button>
                </div>
            </div>
        </section>
    </div>
</template>
