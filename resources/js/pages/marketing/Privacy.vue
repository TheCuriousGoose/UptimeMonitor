<template>
    <MarketingPage :title="$t('marketing.nav.privacy')">
        <p class="font-mono text-xs text-muted-foreground">
            {{ $t('marketing.legal.updated', { date: updated }) }}
        </p>

        <div class="prose-console mt-8">
            <h2>What we store</h2>
            <p>
                Vigil Watch is self-hosted: your data lives on infrastructure
                you control, and the maintainers of this project never receive
                it. This page describes what the software stores on your own
                server.
            </p>

            <h2>Account data</h2>
            <p>
                An account record holds your name, email address, a hashed
                password, and — if you enable it — two-factor authentication
                secrets. Passwords are hashed and are never recoverable.
            </p>

            <h2>Monitoring data</h2>
            <p>
                For every monitor you create we store its target, its
                configuration, and the result of each check: whether it
                succeeded, how long it took, and any error returned. Check
                history is pruned automatically after the retention window.
            </p>

            <h2>Third parties</h2>
            <p>
                Outbound requests are made only to the targets you configure
                and to the alert destinations you connect. Credentials for
                those destinations are stored on your server and are never
                sent back to the browser once saved.
            </p>

            <h2>Cookies</h2>
            <p>
                A session cookie keeps you signed in, and two preference
                cookies remember your theme and whether the sidebar is
                collapsed. There is no analytics or advertising tracking.
            </p>
        </div>
    </MarketingPage>
</template>

<script setup lang="ts">
import MarketingPage from '@/components/marketing/MarketingPage.vue';

const updated = '5 August 2026';
</script>
