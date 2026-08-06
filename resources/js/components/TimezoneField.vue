<script setup lang="ts">
import { computed, useId } from 'vue';
import { Input } from '@/components/ui/input';

defineProps<{
    id?: string;
    placeholder?: string;
}>();

const model = defineModel<string>({ required: true });

// A datalist rather than a Select: the IANA list runs to several hundred
// entries, and typing "Ber" to reach Europe/Berlin beats scrolling. It also
// stays a free-text field, which matches the server's `timezone` rule — an
// unlisted-but-valid zone is still accepted.
const listId = `timezones-${useId()}`;

const zones = computed(() => {
    // Baseline in every browser Vite targets, but a runtime guard keeps the
    // field usable as a plain text input rather than throwing if it is absent.
    if (typeof Intl.supportedValuesOf !== 'function') {
        return [];
    }

    return Intl.supportedValuesOf('timeZone');
});

function useBrowserZone() {
    model.value = Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC';
}

defineExpose({ useBrowserZone });
</script>

<template>
    <div class="grid gap-1.5">
        <Input
            :id="id"
            v-model="model"
            :list="listId"
            :placeholder="placeholder"
            autocomplete="off"
            spellcheck="false"
        />
        <datalist :id="listId">
            <option v-for="zone in zones" :key="zone" :value="zone" />
        </datalist>
    </div>
</template>
