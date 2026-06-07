import { execFileSync } from 'node:child_process';
import {
    existsSync,
    mkdirSync,
    readdirSync,
    statSync,
    writeFileSync,
} from 'node:fs';
import { basename, join, resolve } from 'node:path';
import type { Plugin, ViteDevServer } from 'vite';

interface Options {
    /** Directory containing locale subfolders with PHP translation files. */
    langDir?: string;
    /** Directory where compiled `<locale>.json` files are written. */
    outDir?: string;
    /** PHP binary used to evaluate translation files. */
    php?: string;
}

/**
 * Vite plugin that compiles `lang/<locale>/*.php` files into a single
 * `lang/<locale>.json` per locale, watching for changes during dev.
 *
 * Each PHP filename becomes the top-level namespace, e.g.
 * `lang/en/monitors.php` -> `{ "monitors": { ... } }` in `lang/en.json`.
 */
export default function lang(options: Options = {}): Plugin {
    const root = process.cwd();
    const langDir = resolve(root, options.langDir ?? 'lang');
    const outDir = resolve(root, options.outDir ?? 'lang');
    const php = options.php ?? 'php';

    const emitLocaleUpdate = (devServer: ViteDevServer, locale: string): void => {
        devServer.ws.send({
            type: 'custom',
            event: 'lang:updated',
            data: { locale },
        });
    };

    const compileLocale = (locale: string): void => {
        const dir = join(langDir, locale);

        if (!existsSync(dir) || !statSync(dir).isDirectory()) {
            return;
        }

        const messages: Record<string, unknown> = {};

        for (const file of readdirSync(dir)) {
            if (!file.endsWith('.php')) {
                continue;
            }

            const filePath = join(dir, file);
            const json = execFileSync(
                php,
                [
                    '-r',
                    'echo json_encode(require($argv[1]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);',
                    '--',
                    filePath,
                ],
                { encoding: 'utf8' },
            );

            messages[basename(file, '.php')] = JSON.parse(json);
        }

        mkdirSync(outDir, { recursive: true });
        writeFileSync(
            join(outDir, `${locale}.json`),
            JSON.stringify(messages, null, 2) + '\n',
        );
    };

    const compileAll = (): void => {
        if (!existsSync(langDir)) {
            return;
        }

        for (const entry of readdirSync(langDir)) {
            if (statSync(join(langDir, entry)).isDirectory()) {
                compileLocale(entry);
            }
        }
    };

    return {
        name: 'vite-plugin-lang-php',
        buildStart() {
            compileAll();
        },
        configureServer(devServer: ViteDevServer) {
            devServer.watcher.add(join(langDir, '**/*.php'));

            const handle = (path: string) => {
                if (!path.endsWith('.php') || !path.startsWith(langDir)) {
                    return;
                }

                const rel = path
                    .slice(langDir.length + 1)
                    .replace(/\\/g, '/');
                const locale = rel.split('/')[0];

                if (!locale) {
                    return;
                }

                try {
                    compileLocale(locale);
                    emitLocaleUpdate(devServer, locale);
                } catch (error) {
                    devServer.config.logger.error(
                        `[lang] failed to compile "${locale}": ${(error as Error).message}`,
                    );
                }
            };

            devServer.watcher.on('add', handle);
            devServer.watcher.on('change', handle);
            devServer.watcher.on('unlink', handle);
        },
    };
}
