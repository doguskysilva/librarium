<script setup lang="ts">
import { computed, ref } from 'vue';
import { Form } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { RouteFormDefinition } from '@/wayfinder';

type ReadingAction = RouteFormDefinition<'post' | 'put' | 'patch'>;

type ActionOption = {
    value: string;
    label: string;
    needsDate: boolean;
    action: ReadingAction;
};

const props = defineProps<{
    actions: ActionOption[];
}>();

const selectedAction = ref('');
const datePrecision = ref('exact');
const currentAction = computed(() =>
    props.actions.find((action) => action.value === selectedAction.value),
);

function chooseAction(value: string): void {
    selectedAction.value = value;
    datePrecision.value = 'exact';
}
</script>

<template>
    <div class="grid gap-4">
        <div class="flex flex-wrap gap-2">
            <Button
                v-for="action in actions"
                :key="action.value"
                type="button"
                variant="outline"
                :class="
                    selectedAction === action.value
                        ? 'border-primary bg-primary/10'
                        : ''
                "
                @click="chooseAction(action.value)"
            >
                {{ action.label }}
            </Button>
        </div>

        <Form
            v-if="currentAction"
            v-bind="currentAction.action"
            v-slot="{ errors, processing }"
            class="grid gap-4"
        >
            <template v-if="currentAction.needsDate">
                <div class="grid gap-2">
                    <Label for="date_precision">Date precision</Label>
                    <select
                        id="date_precision"
                        name="date_precision"
                        v-model="datePrecision"
                        required
                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                    >
                        <option value="exact">Exact date</option>
                        <option value="year">Year only</option>
                        <option value="unknown">Unknown</option>
                    </select>
                    <InputError :message="errors.date_precision" />
                </div>

                <div v-if="datePrecision === 'exact'" class="grid gap-2">
                    <Label for="date">Exact date</Label>
                    <Input id="date" name="date" type="date" />
                    <InputError :message="errors.date" />
                </div>

                <div v-if="datePrecision === 'year'" class="grid gap-2">
                    <Label for="year">Year only</Label>
                    <Input id="year" name="year" type="number" min="1000" />
                    <InputError :message="errors.year" />
                </div>
            </template>

            <Button type="submit" :disabled="processing">
                {{ currentAction.label }}
            </Button>
        </Form>
    </div>
</template>
