<template>
    <div class="flex items-center justify-between gap-4 pb-4">
        <div class="flex flex-1 items-center gap-2">
            <slot name="filters" />
        </div>
        <div class="flex items-center gap-2">
            <slot name="actions" />
        </div>
    </div>
</template>

