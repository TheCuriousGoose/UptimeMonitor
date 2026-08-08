import { fileURLToPath } from 'node:url';
import vue from '@vitejs/plugin-vue';
import { defineConfig } from 'vitest/config';

/**
 * Kept separate from vite.config.ts on purpose: the app build pulls in the
 * Laravel, Wayfinder and Tailwind plugins, which shell out to PHP and write
 * into public/. None of that is wanted here, and the route helpers the
 * components import are already committed as plain TypeScript.
 */
export default defineConfig({
    plugins: [vue()],
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
        },
    },
    test: {
        environment: 'jsdom',
        include: ['resources/js/**/*.test.ts'],
        restoreMocks: true,
    },
});
