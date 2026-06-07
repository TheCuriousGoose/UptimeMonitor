<template>
    <div class="grid gap-2">
        <Button v-if="get('oauth.google', 0) == 1" variant="outline" type="button">
            <span class="gap-2 flex">
                <Icon icon="simple-icons:google" class="size-5" />
                {{ $t('auth.oauth.google') }}
            </span>
        </Button>
        <Button v-if="get('oauth.github', 0) == 1" variant="outline" type="button">
            <span class="gap-2 flex">
                <Icon icon="simple-icons:github" class="size-5" />
                {{ $t('auth.oauth.github') }}
            </span>
        </Button>
    </div>
</template>

<script setup lang="ts">
import { Icon } from '@iconify/vue'
import { useSettings } from '@/composables/useSettings'
import Button from '../ui/button/Button.vue';

const { get } = useSettings()
</script>