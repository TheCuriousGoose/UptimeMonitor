import { ref } from 'vue';
import type { Ref } from 'vue';
import { csrfHeaders } from '@/lib/http';

type ColumnVisibility = Record<string, boolean>;
type Cache = Record<string, ColumnVisibility>;

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
    } catch {}
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
            const tablePrefs = {
                ...defaultColumns,
                ...(prefs.columns?.[tableKey] ?? {}),
            };

            columns.value = tablePrefs;
            writeLocalStorage(tableKey, tablePrefs);
        } catch {}
    }

    function toggle(col: string) {
        columns.value = { ...columns.value, [col]: !columns.value[col] };
        writeLocalStorage(tableKey, columns.value);

        if (saveTimer) {
            clearTimeout(saveTimer);
        }

        saveTimer = setTimeout(() => {
            // Unsigned this was a silent 419: the choice stuck locally, so the
            // write never coming back was invisible until another browser.
            fetch('/me/preferences', {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    ...csrfHeaders(),
                },
                body: JSON.stringify({
                    columns: { [tableKey]: columns.value },
                }),
            }).catch(() => {});
        }, 500);
    }

    return { columns, load, toggle };
}
