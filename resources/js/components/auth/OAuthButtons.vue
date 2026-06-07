<template>
    <div class="grid gap-2">
        <OAuthButton v-for="provider in providers" :key="provider" :provider="provider" />
    </div>
</template>

<script setup lang="ts">
import OAuthButton from './OAuthButton.vue';

const providers = [
    'google',
    'github',
];
</script>