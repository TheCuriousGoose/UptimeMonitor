<template>
    <div class="flex items-center gap-1">
        <Button
            :as="Link"
            variant="ghost"
            size="sm"
            :href="monitorsRoute.show(monitor).url"
        >
            <EyeIcon />
        </Button>
        <Button
            :as="Link"
            variant="ghost"
            size="sm"
            :href="monitorsRoute.edit(monitor.uuid).url"
        >
            <PencilIcon />
        </Button>
    </div>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { EyeIcon, PencilIcon } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import * as monitorsRoute from '@/routes/monitors';
import type { Monitor } from '@/types/monitors';

defineProps<{
    monitor: Monitor;
}>();
</script>
