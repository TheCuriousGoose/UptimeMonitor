import { router } from '@inertiajs/vue3';
import { onMounted, onUnmounted } from 'vue';

/**
 * Move focus to the main landmark after each Inertia visit.
 *
 * A single-page navigation swaps the document without touching focus, so a
 * keyboard or screen-reader user stays wherever they were — usually a sidebar
 * link — while the page they asked for is silently rendered somewhere else.
 * A full page load does this for free; this restores it.
 */
export function useFocusOnNavigate(targetId = 'main-content') {
    let stop: (() => void) | undefined;

    onMounted(() => {
        stop = router.on('navigate', () => {
            // Wait for the incoming page to be in the DOM.
            requestAnimationFrame(() => {
                const target = document.getElementById(targetId);

                // Focusing the landmark rather than scrolling to it: the
                // heading is read out, and the next Tab continues from here.
                target?.focus({ preventScroll: true });
            });
        });
    });

    onUnmounted(() => stop?.());
}
