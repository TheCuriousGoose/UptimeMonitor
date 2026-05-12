import { ref } from 'vue';
import type { Ref } from 'vue';

type ColumnVisibility = Record<string, boolean>;
type Cache = Record<string, ColumnVisibility>;

// Module-level — one ref per tableKey, shared across all instances
const store = new Map<string, Ref<ColumnVisibility>>();

function getOrCreate(tableKey: string, defaultColumns: ColumnVisibility) {
    if (!store.has(tableKey)) {
        store.set(
            tableKey,
            ref<ColumnVisibility>(
                readLocalStorage(tableKey) ?? { ...defaultColumns },
            ),
        );
    }

    return store.get(tableKey)!;
}

function localStorageKey(tableKey: string) {
    return `prefs:columns:${tableKey}`;
}

function readLocalStorage(tableKey: string): ColumnVisibility | null {
    try {
        const raw = localStorage.getItem(localStorageKey(tableKey));

        return raw ? (JSON.parse(raw) as ColumnVisibility) : null;
    } catch {
        return null;
    }
}

function writeLocalStorage(tableKey: string, value: ColumnVisibility) {
    try {
        localStorage.setItem(localStorageKey(tableKey), JSON.stringify(value));
    } catch {
        // Storage quota exceeded — silently ignore
    }
}

export function useColumnPreferences(
    tableKey: string,
    defaultColumns: ColumnVisibility,
) {
    const columns = getOrCreate(tableKey, defaultColumns);

    let saveTimer: ReturnType<typeof setTimeout> | null = null;

    async function load() {
        try {
            const response = await fetch('/me/preferences');

            if (!response.ok) {
                return;
            }

            const prefs = (await response.json()) as { columns?: Cache };
            const tablePrefs = prefs.columns?.[tableKey] ?? {
                ...defaultColumns,
            };

            columns.value = tablePrefs;
            writeLocalStorage(tableKey, tablePrefs);
        } catch {
            // Network failure — keep localStorage/default values
        }
    }

    function toggle(col: string) {
        columns.value = { ...columns.value, [col]: !columns.value[col] };
        writeLocalStorage(tableKey, columns.value);

        if (saveTimer) {
            clearTimeout(saveTimer);
        }

        saveTimer = setTimeout(() => {
            fetch('/me/preferences', {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    columns: { [tableKey]: columns.value },
                }),
            }).catch(() => {
                // Best-effort save — local state is already updated
            });
        }, 500);
    }

    return { columns, load, toggle };
}
