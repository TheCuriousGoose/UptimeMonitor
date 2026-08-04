<template>
    <Dialog :open="open" @update:open="(value) => emit('update:open', value)">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>{{ title }}</DialogTitle>
                <DialogDescription v-if="description">{{
                    description
                }}</DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <Button variant="outline" @click="emit('update:open', false)">
                    {{ cancelLabel ?? $t('base.cancel') }}
                </Button>
                <Button
                    :variant="destructive ? 'destructive' : 'default'"
                    @click="confirm"
                >
                    {{ confirmLabel ?? $t('base.confirm') }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>

<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

withDefaults(
    defineProps<{
        open: boolean;
        title: string;
        description?: string;
        confirmLabel?: string;
        cancelLabel?: string;
        destructive?: boolean;
    }>(),
    { destructive: false },
);

const emit = defineEmits<{
    'update:open': [value: boolean];
    confirm: [];
}>();

function confirm() {
    emit('confirm');
    emit('update:open', false);
}
</script>
