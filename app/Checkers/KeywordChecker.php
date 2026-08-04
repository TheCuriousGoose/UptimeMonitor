<?php

namespace App\Checkers;

use Illuminate\Http\Client\Response;

/**
 * An HTTP check that also asserts on the response body, so a page that
 * returns 200 while rendering an error is still reported as down.
 */
class KeywordChecker extends HttpChecker
{
    /**
     * @param  array<string, mixed>  $config
     */
    protected function assertBody(Response $response, array $config): ?string
    {
        $keyword = (string) ($config['keyword'] ?? '');

        if ($keyword === '') {
            return null;
        }

        $found = str_contains($response->body(), $keyword);
        $shouldBeAbsent = filter_var($config['invert'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if ($shouldBeAbsent && $found) {
            return "Keyword \"{$keyword}\" was found but should be absent";
        }

        if (! $shouldBeAbsent && ! $found) {
            return "Keyword \"{$keyword}\" not found in response body";
        }

        return null;
    }
}
