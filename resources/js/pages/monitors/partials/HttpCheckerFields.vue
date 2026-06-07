<template>
    <Field>
        <FieldLabel for="url">{{ $t('monitors.form.url.title') }}</FieldLabel>
        <Input
            id="url"
            name="url"
            autocomplete="off"
            type="url"
            :default-value="url"
        />
        <FieldError>{{ errors?.url }}</FieldError>
        <FieldDescription>{{ $t('monitors.form.url.description') }}</FieldDescription>
    </Field>
</template>

<script setup lang="ts">
import { Field, FieldDescription, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';

defineProps<{
    errors?: Record<string, string>;
    url?: string;
}>();
</script>
