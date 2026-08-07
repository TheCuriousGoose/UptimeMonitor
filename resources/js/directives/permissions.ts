import { watchEffect } from 'vue';
import type { App, WatchHandle } from 'vue';
import { usePermissions } from '@/composables/usePermissions';

const watchers = new WeakMap<HTMLElement, WatchHandle>();
const originalDisplay = new WeakMap<HTMLElement, string>();

// Tracked rather than applied once, so an element mounted before the page
// props settle is not left in whichever state it started in.
function track(el: HTMLElement, resolve: () => boolean): void {
    watchers.get(el)?.();

    if (!originalDisplay.has(el)) {
        originalDisplay.set(el, el.style.display);
    }

    const original = originalDisplay.get(el) ?? '';

    watchers.set(
        el,
        watchEffect(() => {
            el.style.display = resolve() ? original : 'none';
        }),
    );
}

function release(el: HTMLElement): void {
    watchers.get(el)?.();
    watchers.delete(el);
    originalDisplay.delete(el);
}

export function registerPermissionDirectives(app: App): void {
    const { can, canAny } = usePermissions();

    app.directive<HTMLElement, string>('can', {
        mounted: (el, { value }) => track(el, () => can(value)),
        updated: (el, { value }) => track(el, () => can(value)),
        unmounted: release,
    });

    app.directive<HTMLElement, string | string[]>('can-any', {
        mounted: (el, { value }) => track(el, () => canAny(value)),
        updated: (el, { value }) => track(el, () => canAny(value)),
        unmounted: release,
    });
}
