import { router, usePoll } from '@inertiajs/vue3';
import { onMounted, ref, watch } from 'vue';

/**
 * Whether auto-refresh is on, shared by every indicator in the app.
 *
 * Device-scoped rather than saved to the account the way column preferences
 * are: "keep this live" is a property of the screen it is on. A wall display
 * and a laptop on a metered connection want opposite answers, and the same
 * person owns both.
 */
const STORAGE_KEY = 'prefs:live';

function readPreference(): boolean {
    try {
        return localStorage.getItem(STORAGE_KEY) !== 'off';
    } catch {
        return true;
    }
}

const enabled = ref(readPreference());

function toggle(): void {
    enabled.value = !enabled.value;

    try {
        localStorage.setItem(STORAGE_KEY, enabled.value ? 'on' : 'off');
    } catch {}
}

/**
 * Poll a page's data in the background.
 *
 * `only` is required rather than optional: a full reload would replace props
 * the user is interacting with, and on the dashboard it re-runs every uptime
 * aggregate for props that did not change.
 *
 * Inertia stops polls while the tab is hidden (keepAlive defaults to false),
 * so a backgrounded dashboard costs nothing.
 */
export function useLiveData(intervalMs: number, only: string[]) {
    const lastUpdatedAt = ref(Date.now());
    const isRefreshing = ref(false);

    const options = {
        only,
        preserveState: true,
        preserveScroll: true,
        onStart: () => {
            isRefreshing.value = true;
        },
        onFinish: () => {
            isRefreshing.value = false;
            lastUpdatedAt.value = Date.now();
        },
    };

    const poll = usePoll(intervalMs, options, { autoStart: false });

    onMounted(() => {
        if (enabled.value) {
            poll.start();
        }
    });

    watch(enabled, (on) => (on ? poll.start() : poll.stop()));

    /** Refresh once, whether or not polling is on. */
    function refresh(): void {
        if (isRefreshing.value) {
            return;
        }

        router.reload(options);
    }

    return { enabled, toggle, lastUpdatedAt, isRefreshing, refresh };
}
