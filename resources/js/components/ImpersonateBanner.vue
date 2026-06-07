<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { ShieldAlertIcon } from 'lucide-vue-next';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { destroy } from '@/routes/admin/impersonate';
import type { Auth } from '@/types';

const page = usePage();
const impersonatingRole = computed(() => (page.props.auth as Auth).impersonating_role);

function stop() {
    router.delete(destroy().url);
}
</script>

<template>
    <div
        v-if="impersonatingRole"
        class="flex items-center justify-between gap-3 bg-amber-500/15 border-b border-amber-500/30 px-4 py-2 text-sm text-amber-700 dark:text-amber-400"
    >
        <div class="flex items-center gap-2">
            <ShieldAlertIcon class="size-4 shrink-0" />
            <span>Impersonating role: <strong>{{ impersonatingRole }}</strong></span>
        </div>
        <Button variant="outline" size="sm" class="h-7 text-xs border-amber-500/50 hover:bg-amber-500/10" @click="stop">
            Stop impersonating
        </Button>
    </div>
</template>
