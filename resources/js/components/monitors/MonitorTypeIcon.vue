<template>
    <component :is="icons[type]" aria-hidden="true" />
</template>

<script setup lang="ts">
import {
    FileSearchIcon,
    GlobeIcon,
    NetworkIcon,
    PlugIcon,
    RadioIcon,
    ShieldCheckIcon,
} from 'lucide-vue-next';
import type { MonitorType } from '@/types/monitors';

defineProps<{
    type: MonitorType;
}>();

/**
 * The glyph for each monitor type. Shared rather than declared per screen:
 * the type picker appears in both the monitor form and the onboarding
 * wizard, and the two are meant to be the same control.
 */
const icons: Record<MonitorType, unknown> = {
    http: GlobeIcon,
    keyword: FileSearchIcon,
    port: PlugIcon,
    ping: RadioIcon,
    dns: NetworkIcon,
    ssl: ShieldCheckIcon,
};
</script>
