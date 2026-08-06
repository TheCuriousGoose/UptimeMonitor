/**
 * Extra column configuration DataTable reads off a column definition.
 *
 * `sortable` column ids must match a key in the model's SORTS allowlist on
 * the server — the id is what gets sent as ?sort=.
 */
export type ColumnMeta = {
    sortable?: boolean;
    /** Fold this column away below md rather than forcing a sideways scroll. */
    hideOnMobile?: boolean;
};

export type SortDirection = 'asc' | 'desc';
