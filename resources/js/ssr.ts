import { createInertiaApp } from '@inertiajs/vue3';
import { createSSRApp, h } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { i18n, setLocale } from '@/lib/i18n';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

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

        setLocale(locale);

        return createSSRApp({ render: () => h(App, props) })
            .use(plugin)
            .use(i18n);
    },
});
