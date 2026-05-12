<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <slot name="trigger">
                <Button variant="outline">
                    <Columns2Icon />
                </Button>
            </slot>
        </DropdownMenuTrigger>
        <DropdownMenuContent>
            <DropdownMenuLabel>{{ $t('tables.visible_columns') }}</DropdownMenuLabel>
            <DropdownMenuItem v-for="(visible, name) in columns" :key="name" @click="toggle(name)">
                {{ $t(`${columnTranslations}.${name}`) }}
                <DropdownMenuShortcut v-if="visible">
                    <CheckIcon />
                </DropdownMenuShortcut>
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>

<script setup lang="ts">
import { CheckIcon, Columns2Icon } from 'lucide-vue-next';
import { useColumnPreferences } from "@/composables/useColumnPreferences";
import { Button } from '../ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuShortcut, DropdownMenuTrigger } from '../ui/dropdown-menu';

const props = defineProps<{
    table: string;
    columnTranslations: string;
}>();

const { columns, toggle } = useColumnPreferences(props.table, {});
</script>