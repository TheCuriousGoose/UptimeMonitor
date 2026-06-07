<template>
    <Button as="a" v-if="get('oauth.' + provider, 0) == 1" variant="outline" :href="oauth.redirect(provider)">
        <span class="gap-2 flex">
            <Icon :icon="'simple-icons:' + provider" class="size-5" />
            {{ $t('auth.oauth.' + provider) }}
        </span>
    </Button>
</template>

<script setup lang="ts">
import { Icon } from '@iconify/vue';
import { useSettings } from '@/composables/useSettings.js';
import oauth from '@/routes/oauth/index.js';
import Button from '../ui/button/Button.vue';

defineProps<{
    provider: string;
}>();

const { get } = useSettings()
</script>
