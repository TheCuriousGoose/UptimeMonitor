<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class HandleSeo
{
    /**
     * Routes that search engines should be allowed to index.
     *
     * The root template used to send "noindex, nofollow" on every response,
     * which also covered the marketing site, the docs, the blog and every
     * public status page — the four things a customer is most likely to link
     * to. Everything behind auth stays out of the index, but by name rather
     * than by blanket.
     */
    private const INDEXABLE = [
        '/',
        'features',
        'about',
        'contact',
        'roadmap',
        'privacy',
        'terms',
        'docs',
        'docs/*',
        'blog',
        'blog/*',
        'changelog',
        'changelog/*',
        'status/*',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        View::share('indexable', $request->is(...self::INDEXABLE));
        View::share('canonical', $request->url());

        return $next($request);
    }
}
