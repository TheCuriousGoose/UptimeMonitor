<template>
    <div
        v-if="points.length === 0"
        class="flex h-48 items-center justify-center text-sm text-muted-foreground"
    >
        {{ $t('monitors.no_data') }}
    </div>

    <div v-else class="relative" @pointerleave="hoverIndex = null">
        <svg
            :viewBox="`0 0 ${width} ${height}`"
            class="h-48 w-full"
            preserveAspectRatio="none"
            role="img"
            :aria-label="ariaLabel"
            @pointermove="onPointerMove"
        >
            <!-- Recessive hairline grid; the data is the only loud thing here. -->
            <line
                v-for="tick in yTicks"
                :key="tick.value"
                :x1="0"
                :x2="width"
                :y1="tick.y"
                :y2="tick.y"
                stroke="var(--viz-grid)"
                stroke-width="1"
                vector-effect="non-scaling-stroke"
            />

            <template v-for="(segment, index) in segments" :key="`s-${index}`">
                <path
                    :d="segment.area"
                    :fill="seriesColor"
                    fill-opacity="0.1"
                />
                <path
                    :d="segment.line"
                    fill="none"
                    :stroke="seriesColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    vector-effect="non-scaling-stroke"
                />
            </template>

            <!-- Failures marked on the baseline so an outage is visible even
                 where the response line has no successful sample to draw. -->
            <rect
                v-for="failure in failureMarks"
                :key="`f-${failure.index}`"
                :x="failure.x - 1"
                :y="height - 6"
                width="2"
                height="6"
                :fill="downColor"
            />

            <template v-if="hovered">
                <line
                    :x1="hovered.x"
                    :x2="hovered.x"
                    :y1="0"
                    :y2="height"
                    stroke="var(--viz-grid)"
                    stroke-width="1"
                    vector-effect="non-scaling-stroke"
                />
                <circle
                    v-if="hovered.y !== null"
                    :cx="hovered.x"
                    :cy="hovered.y"
                    r="4"
                    :fill="seriesColor"
                    stroke="var(--background)"
                    stroke-width="2"
                    vector-effect="non-scaling-stroke"
                />
            </template>
        </svg>

        <div
            v-if="hovered"
            class="pointer-events-none absolute top-2 z-10 min-w-36 rounded-md border bg-popover px-2.5 py-1.5 text-xs shadow-md"
            :style="tooltipStyle"
        >
            <p class="text-muted-foreground">
                {{ formatDateTime(hovered.point.bucket) }}
            </p>
            <p class="mt-0.5 font-mono font-medium tabular-nums">
                {{
                    hovered.point.avg_response_ms === null
                        ? $t('monitors.no_data')
                        : formatResponseMs(hovered.point.avg_response_ms)
                }}
            </p>
            <p
                v-if="hovered.point.failures > 0"
                class="mt-0.5 text-muted-foreground"
            >
                {{ hovered.point.failures }} failed of {{ hovered.point.total }}
            </p>
        </div>

        <div
            class="mt-1 flex justify-between text-xs text-muted-foreground tabular-nums"
        >
            <span>{{ formatDateTime(points[0].bucket) }}</span>
            <span>{{ formatResponseMs(maxValue) }} max</span>
            <span>{{ formatDateTime(points[points.length - 1].bucket) }}</span>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { formatDateTime, formatResponseMs } from '@/lib/format';
import type { SeriesPoint } from '@/types/monitors';

const props = defineProps<{
    series: SeriesPoint[];
}>();

const width = 600;
const height = 160;
const seriesColor = 'var(--viz-series)';
const downColor = 'var(--viz-down)';

const hoverIndex = ref<number | null>(null);

const points = computed(() => props.series);

const maxValue = computed(() =>
    Math.max(
        1,
        ...points.value
            .map((point) => point.avg_response_ms)
            .filter((value): value is number => value !== null),
    ),
);

function xFor(index: number): number {
    if (points.value.length <= 1) {
        return width / 2;
    }

    return (index / (points.value.length - 1)) * width;
}

function yFor(value: number): number {
    // Leave headroom at the top so the peak never touches the frame.
    return height - (value / maxValue.value) * (height - 12) - 6;
}

/**
 * Catmull-Rom through the data points, converted to cubic Beziers (the
 * standard 1/6-tension formulation) — smooths the line without moving any
 * point off its true value, unlike a moving-average filter.
 */
function smoothPath(coords: { x: number; y: number }[]): string {
    if (coords.length < 2) {
        return coords.length === 1 ? `M${coords[0].x},${coords[0].y}` : '';
    }

    let path = `M${coords[0].x},${coords[0].y}`;

    for (let i = 0; i < coords.length - 1; i += 1) {
        const p0 = coords[i === 0 ? 0 : i - 1];
        const p1 = coords[i];
        const p2 = coords[i + 1];
        const p3 = coords[i + 2 < coords.length ? i + 2 : i + 1];

        const c1x = p1.x + (p2.x - p0.x) / 6;
        const c1y = p1.y + (p2.y - p0.y) / 6;
        const c2x = p2.x - (p3.x - p1.x) / 6;
        const c2y = p2.y - (p3.y - p1.y) / 6;

        path += ` C${c1x},${c1y} ${c2x},${c2y} ${p2.x},${p2.y}`;
    }

    return path;
}

/**
 * Buckets with no successful check carry a null average, and the line has to
 * break across them rather than dive to the baseline — so the series is drawn
 * as one path per contiguous run of real samples.
 */
const segments = computed(() => {
    const runs: { x: number; y: number }[][] = [];
    let current: { x: number; y: number }[] = [];

    points.value.forEach((point, index) => {
        if (point.avg_response_ms === null) {
            if (current.length > 0) {
                runs.push(current);
                current = [];
            }

            return;
        }

        current.push({ x: xFor(index), y: yFor(point.avg_response_ms) });
    });

    if (current.length > 0) {
        runs.push(current);
    }

    return runs.map((coords) => ({
        line: smoothPath(coords),
        area: `${smoothPath(coords)} L${coords[coords.length - 1].x},${height} L${coords[0].x},${height} Z`,
    }));
});

const failureMarks = computed(() =>
    points.value
        .map((point, index) => ({
            index,
            x: xFor(index),
            failures: point.failures,
        }))
        .filter((mark) => mark.failures > 0),
);

const yTicks = computed(() =>
    [0, 0.5, 1].map((fraction) => ({
        value: Math.round(maxValue.value * fraction),
        y: yFor(maxValue.value * fraction),
    })),
);

const hovered = computed(() => {
    if (hoverIndex.value === null || !points.value[hoverIndex.value]) {
        return null;
    }

    const point = points.value[hoverIndex.value];

    return {
        point,
        x: xFor(hoverIndex.value),
        // Null where the bucket has no successful sample — the crosshair still
        // shows, but there is no point on the line to mark.
        y: point.avg_response_ms === null ? null : yFor(point.avg_response_ms),
    };
});

const tooltipStyle = computed(() => {
    const ratio = hovered.value ? hovered.value.x / width : 0;

    return ratio > 0.6
        ? { right: `${(1 - ratio) * 100}%` }
        : { left: `${ratio * 100}%` };
});

function onPointerMove(event: PointerEvent) {
    const target = event.currentTarget as SVGSVGElement;
    const bounds = target.getBoundingClientRect();
    const ratio = (event.clientX - bounds.left) / bounds.width;

    hoverIndex.value = Math.max(
        0,
        Math.min(
            points.value.length - 1,
            Math.round(ratio * (points.value.length - 1)),
        ),
    );
}

const ariaLabel = computed(
    () =>
        `Average response time over time, peaking at ${maxValue.value} milliseconds.`,
);
</script>
