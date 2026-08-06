{{--
    Deliberately self-contained: no @vite, no @fonts, no JS.

    These views are the two cases the Inertia error page cannot cover. Laravel
    renders errors/503 directly during `artisan down`, long before the app boots
    far enough to reach Inertia, and falls back to errors/500 if the Inertia
    render in bootstrap/app.php throws on its own. Either way, built assets may
    not be reachable — so everything here is inline.

    Colours are copied from the light/dark tokens in resources/css/app.css so
    this reads as the same product, and follow the system preference because
    there is no script to apply the stored one.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex, nofollow">
        <title>@yield('title') - {{ config('app.name', 'Laravel') }}</title>

        <link rel="icon" href="/favicon.ico" sizes="32x32">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">

        <style>
            :root {
                --background: hsl(240 20% 98%);
                --foreground: hsl(240 10% 8%);
                --muted-foreground: hsl(240 5% 46%);
                --border: hsl(240 13% 87%);
            }

            @media (prefers-color-scheme: dark) {
                :root {
                    --background: hsl(240 12% 7%);
                    --foreground: hsl(240 15% 97%);
                    --muted-foreground: hsl(240 6% 64%);
                    --border: hsl(240 8% 21%);
                }
            }

            * { box-sizing: border-box; }

            body {
                margin: 0;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 1rem;
                background-color: var(--background);
                color: var(--foreground);
                font-family: 'Instrument Sans', ui-sans-serif, system-ui, -apple-system,
                    'Segoe UI', Roboto, sans-serif;
                -webkit-font-smoothing: antialiased;
            }

            main {
                width: 100%;
                max-width: 28rem;
                text-align: center;
            }

            .code {
                margin: 0;
                font-family: 'JetBrains Mono', ui-monospace, SFMono-Regular, Menlo, monospace;
                font-size: 3rem;
                font-weight: 600;
                letter-spacing: -0.025em;
                font-variant-numeric: tabular-nums;
            }

            h1 {
                margin: 1rem 0 0;
                font-family: 'JetBrains Mono', ui-monospace, SFMono-Regular, Menlo, monospace;
                font-size: 0.75rem;
                font-weight: 600;
                letter-spacing: 0.12em;
                text-transform: uppercase;
            }

            p {
                margin: 0.5rem 0 0;
                font-size: 0.875rem;
                line-height: 1.5;
                color: var(--muted-foreground);
            }

            a {
                display: inline-flex;
                align-items: center;
                margin-top: 1.5rem;
                padding: 0.5rem 0.875rem;
                border: 1px solid var(--border);
                border-radius: 0.125rem;
                font-size: 0.875rem;
                font-weight: 500;
                color: inherit;
                text-decoration: none;
            }

            a:hover { border-color: var(--muted-foreground); }

            a:focus-visible {
                outline: 2px solid var(--foreground);
                outline-offset: 1px;
            }
        </style>
    </head>
    <body>
        <main>
            <p class="code">@yield('code')</p>

            <div role="alert">
                <h1>@yield('title')</h1>
                <p>@yield('description')</p>
            </div>

            <a href="{{ url('/') }}">{{ __('errors.actions.home') }}</a>
        </main>
    </body>
</html>
