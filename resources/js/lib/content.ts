import { trans } from '@/lib/i18n';
import type { ContentEntry } from '@/types/content';

export type ContentGroup = {
    category: string;
    entries: ContentEntry[];
};

/**
 * Fold docs into their categories.
 *
 * The server already returns them ordered by category then sort order, so
 * this only has to fold consecutive runs rather than re-sort anything —
 * which also means the display order always matches the stored order.
 */
export function groupByCategory(entries: ContentEntry[]): ContentGroup[] {
    const groups: ContentGroup[] = [];

    for (const entry of entries) {
        const category = entry.category ?? trans('content.public.uncategorised');
        const last = groups[groups.length - 1];

        if (last && last.category === category) {
            last.entries.push(entry);

            continue;
        }

        groups.push({ category, entries: [entry] });
    }

    return groups;
}
