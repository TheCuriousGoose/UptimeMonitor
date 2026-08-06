import { ref } from 'vue';

/**
 * Open state for the command palette, shared at module scope.
 *
 * The palette is mounted once in the app shell but opened from two places —
 * the header button and the Cmd/Ctrl+K binding — so the state cannot live
 * inside the component without one of them reaching through a template ref.
 */
const open = ref(false);

export function useCommandPalette() {
    return {
        open,
        show: () => (open.value = true),
        toggle: () => (open.value = !open.value),
    };
}
