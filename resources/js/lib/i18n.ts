import { createI18n } from 'vue-i18n';

type LocaleMessages = Record<string, unknown>;
type LocaleModule = { default: LocaleMessages };

/**
 * Recursively convert Laravel-style `:placeholder` to vue-i18n `{placeholder}`.
 */
function normalizePlaceholders(value: unknown): unknown {
    if (typeof value === 'string') {
        return value.replace(/:([a-zA-Z_][a-zA-Z0-9_]*)/g, '{$1}');
    }

    if (Array.isArray(value)) {
        return value.map(normalizePlaceholders);
    }

    if (value !== null && typeof value === 'object') {
        return Object.fromEntries(
            Object.entries(value as Record<string, unknown>).map(([k, v]) => [
                k,
                normalizePlaceholders(v),
            ]),
        );
    }

    return value;
}

const modules = import.meta.glob<LocaleModule>(
    '../../../lang/*.json',
    { eager: true },
);
const loaders = import.meta.glob<LocaleModule>('../../../lang/*.json');

const messages: Record<string, LocaleMessages> = Object.fromEntries(
    Object.entries(modules).map(([path, mod]) => {
        const locale = path.match(/([^/]+)\.json$/)?.[1] ?? 'en';

        return [locale, normalizePlaceholders(mod.default) as LocaleMessages];
    }),
);

export const i18n = createI18n({
    legacy: false,
    globalInjection: true,
    locale: 'en',
    fallbackLocale: 'en',
    messages: messages as never,
});

const global = i18n.global as unknown as {
    t: (key: string, named?: Record<string, string | number>) => string;
    locale: { value: string };
    setLocaleMessage: (locale: string, message: LocaleMessages) => void;
};

const loadLocale = async (locale: string): Promise<void> => {
    const loader = loaders[`../../../lang/${locale}.json`];

    if (!loader) {
        return;
    }

    const messages = normalizePlaceholders((await loader()).default) as LocaleMessages;

    global.setLocaleMessage(locale, messages);
};

/** Translate a key. Safe to call from non-component modules. */
export const t = (key: string, named?: Record<string, string | number>): string => global.t(key, named);
export const trans = t;

export const setLocale = (locale: string): void => {
    global.locale.value = locale;
};

if (import.meta.hot) {
    import.meta.hot.on('lang:updated', async ({ locale }: { locale: string }) => {
        await loadLocale(locale);
    });
}
