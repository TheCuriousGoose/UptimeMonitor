// Mirrors App\Enums\ContentType. `legal` belongs here even though it has no
// public index: the admin content screen lists every type, and omitting it is
// what let a legal entry reach code that assumed a /{segment}/{slug} URL.
export type ContentType = 'doc' | 'post' | 'changelog' | 'legal';

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
