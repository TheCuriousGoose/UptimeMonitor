<template>
    <!-- Wraps rather than overflowing: the filters and actions together are
         wider than a phone, and a filter bar you have to scroll sideways to
         reach is worse than one on two lines. -->
    <div
        class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2 pb-4"
    >
        <div class="flex min-w-0 flex-1 flex-wrap items-center gap-2">
            <slot name="filters" />
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <slot name="actions" />
        </div>
    </div>
</template>
