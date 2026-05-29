import { createI18n } from 'vue-i18n';

type LocaleMessages = Record<string, unknown>;

const modules = import.meta.glob<{ default: LocaleMessages }>(
    '../../../lang/*.json',
    { eager: true },
);

const messages: Record<string, LocaleMessages> = Object.fromEntries(
    Object.entries(modules).map(([path, mod]) => {
        const locale = path.match(/([^/]+)\.json$/)?.[1] ?? 'en';

        return [locale, mod.default];
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
    t: (key: string) => string;
    locale: { value: string };
};

/** Translate a key. Safe to call from non-component modules. */
export const t = (key: string): string => global.t(key);
export const trans = t;

export const setLocale = (locale: string): void => {
    global.locale.value = locale;
};
