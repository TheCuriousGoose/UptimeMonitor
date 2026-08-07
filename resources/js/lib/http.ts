/**
 * Helpers for the handful of requests that do not go through Inertia.
 *
 * Inertia signs its own visits; a bare `fetch` does not, and the web group
 * runs PreventRequestForgery, so an unsigned write is rejected with a 419 that
 * `fetch` reports as a non-ok response rather than throwing. That failure is
 * easy to miss — which is exactly how it went unnoticed here.
 */

/** Laravel's XSRF cookie, which its forgery middleware accepts as a header. */
export function csrfHeaders(): Record<string, string> {
    const token = document.cookie
        .split('; ')
        .find((row) => row.startsWith('XSRF-TOKEN='))
        ?.split('=')
        .slice(1)
        .join('=');

    return token ? { 'X-XSRF-TOKEN': decodeURIComponent(token) } : {};
}
