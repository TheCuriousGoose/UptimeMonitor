<template>
    <MarketingPage :title="$t('marketing.nav.terms')">
        <p class="font-mono text-xs text-muted-foreground">
            {{ $t('marketing.legal.updated', { date: updated }) }}
        </p>

        <div class="prose-console mt-8">
            <h2>The software</h2>
            <p>
                Vigil Watch is provided as software you run yourself. These
                terms cover use of the software, not a hosted service — there
                is no account held by us and no data processed on our behalf.
            </p>

            <h2>No warranty</h2>
            <p>
                The software is provided “as is”, without warranty of any
                kind. Uptime monitoring is a best-effort signal: a missed
                alert, a false alarm, or a period of unreported downtime is
                possible, and you should not rely on it as the sole safeguard
                for a critical system.
            </p>

            <h2>Acceptable use</h2>
            <p>
                You may only monitor systems you own or have permission to
                probe. The check engine issues real network requests to the
                targets you configure; pointing it at third-party
                infrastructure without consent may be unlawful.
            </p>

            <h2>Liability</h2>
            <p>
                To the fullest extent permitted by law, the authors are not
                liable for any loss arising from use of the software,
                including losses caused by undetected downtime.
            </p>
        </div>
    </MarketingPage>
</template>

<script setup lang="ts">
import MarketingPage from '@/components/marketing/MarketingPage.vue';

const updated = '5 August 2026';
</script>
