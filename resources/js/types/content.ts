export type ContentType = 'doc' | 'post' | 'changelog';

export interface ContentEntry {
    uuid: string;
    type: ContentType;
    title: string;
    slug: string;
    excerpt: string | null;
    version: string | null;
    category: string | null;
    sort_order: number;
    published_at: string | null;
    is_published: boolean;
    author?: { name: string };
    /** Raw markdown — only sent to the admin editor. */
    body?: string;
}
