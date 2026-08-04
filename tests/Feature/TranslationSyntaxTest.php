<?php

namespace Tests\Feature;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

/**
 * The front end compiles these strings with vue-i18n, whose message syntax
 * gives "@" and "{" special meaning. A bad string does not fail the build —
 * it throws while rendering, which takes out whatever component used it.
 */
class TranslationSyntaxTest extends TestCase
{
    /**
     * @return array<int, array{0: string, 1: string}>
     */
    private function messages(): array
    {
        $messages = [];

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(lang_path(), RecursiveDirectoryIterator::SKIP_DOTS),
        );

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $this->flatten(require $file->getPathname(), $file->getFilename(), $messages);
        }

        return $messages;
    }

    /**
     * @param  array<string, mixed>  $items
     * @param  array<int, array{0: string, 1: string}>  $messages
     */
    private function flatten(array $items, string $prefix, array &$messages): void
    {
        foreach ($items as $key => $value) {
            $path = "{$prefix}.{$key}";

            if (is_array($value)) {
                $this->flatten($value, $path, $messages);
            } elseif (is_string($value)) {
                $messages[] = [$path, $value];
            }
        }
    }

    /**
     * vue-i18n treats "@" as the start of a linked message. An email address
     * or handle in a placeholder must be escaped as {'@'} or the compiler
     * raises INVALID_LINKED_FORMAT while rendering.
     */
    public function test_no_translation_contains_an_unescaped_at_sign(): void
    {
        $offenders = [];

        foreach ($this->messages() as [$path, $message]) {
            if (str_contains(str_replace("{'@'}", '', $message), '@')) {
                $offenders[] = "{$path} => {$message}";
            }
        }

        $this->assertSame(
            [],
            $offenders,
            "Escape @ as {'@'} in these translations:\n".implode("\n", $offenders),
        );
    }

    /**
     * Braces delimit interpolation, so an unmatched one breaks compilation.
     */
    public function test_translation_braces_are_balanced(): void
    {
        $offenders = [];

        foreach ($this->messages() as [$path, $message]) {
            if (substr_count($message, '{') !== substr_count($message, '}')) {
                $offenders[] = "{$path} => {$message}";
            }
        }

        $this->assertSame([], $offenders, "Unbalanced braces:\n".implode("\n", $offenders));
    }
}
