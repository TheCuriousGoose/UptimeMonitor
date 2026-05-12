import { createInertiaApp } from '@inertiajs/vue3';
import { i18nVue } from 'laravel-vue-i18n';
import { createSSRApp, h } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

const langFiles = {
    ...import.meta.glob('../../lang/*.json', { eager: true }),
    ...import.meta.glob('../../lang/php_*.json', { eager: true }),
} as Record<string, { default?: Record<string, string> }>;

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            case name === 'Welcome':
                return null;
            case name.startsWith('auth/'):
                return AuthLayout;
            case name.startsWith('settings/'):
                return [AppLayout, SettingsLayout];
            default:
                return AppLayout;
        }
    },
    setup({ App, props, plugin }) {
        const locale = (props.initialPage.props.locale as string) ?? 'en';

        return createSSRApp({ render: () => h(App, props) })
            .use(plugin)
            .use(i18nVue, {
                lang: locale,
                resolve: (lang: string) =>
                    langFiles[`../../lang/php_${lang}.json`]?.default ??
                    langFiles[`../../lang/${lang}.json`]?.default ??
                    {},
            });
    },
});
