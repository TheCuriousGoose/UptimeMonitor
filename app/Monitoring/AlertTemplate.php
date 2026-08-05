<?php

namespace App\Monitoring;

use App\Models\Monitor;

/**
 * Renders a channel's custom alert wording.
 *
 * Templates are user input, so substitution is a whitelist lookup and nothing
 * else — never Blade, never eval. A template containing `{{ $x }}` or `@php`
 * is inert text here, because nothing in this class ever compiles or executes
 * what it is given.
 */
class AlertTemplate
{
    /**
     * Every placeholder a template may reference. Anything else is rejected at
     * save time by StoreChannelRequest/UpdateChannelRequest.
     *
     * @var array<int, string>
     */
    public const PLACEHOLDERS = [
        'monitor.name',
        'monitor.url',
        'monitor.type',
        'monitor.uuid',
        'monitor.link',
        'event',
        'error',
        'occurred_at',
        'incident.duration',
    ];

    private const PATTERN = '/\{\{\s*([a-zA-Z0-9_.]+)\s*\}\}/';

    /**
     * @param  array<string, array<string, string|null>>|null  $templates
     */
    public function render(AlertMessage $message, ?array $templates): RenderedAlert
    {
        $forEvent = $templates[$message->event->value] ?? [];
        $values = $this->values($message);

        return new RenderedAlert(
            title: $this->apply($forEvent['title'] ?? null, $values) ?? $message->title(),
            body: $this->apply($forEvent['body'] ?? null, $values) ?? $message->body(),
        );
    }

    /**
     * Placeholders in a template that this renderer does not know about, so a
     * typo surfaces on the form instead of as a blank during an outage.
     *
     * @return array<int, string>
     */
    public static function unknownPlaceholders(string $template): array
    {
        preg_match_all(self::PATTERN, $template, $matches);

        return array_values(array_unique(
            array_diff($matches[1] ?? [], self::PLACEHOLDERS),
        ));
    }

    /**
     * Shared by the renderer and AlertMessage so a downtime figure reads the
     * same whether it came from a template or the built-in wording.
     */
    public static function humanDuration(int $seconds): string
    {
        return match (true) {
            $seconds < 60 => "{$seconds}s",
            $seconds < 3600 => floor($seconds / 60).'m',
            default => floor($seconds / 3600).'h '.floor(($seconds % 3600) / 60).'m',
        };
    }

    /**
     * @param  array<string, string>  $values
     */
    private function apply(?string $template, array $values): ?string
    {
        if ($template === null || trim($template) === '') {
            return null;
        }

        return preg_replace_callback(
            self::PATTERN,
            // An unknown placeholder renders empty rather than leaking braces
            // into the alert. Validation already rejects these on save.
            fn (array $match) => $values[$match[1]] ?? '',
            $template,
        );
    }

    /**
     * @return array<string, string>
     */
    private function values(AlertMessage $message): array
    {
        $monitor = $message->monitor;

        return [
            'monitor.name' => (string) $monitor->name,
            'monitor.url' => (string) $monitor->url,
            'monitor.type' => $monitor->type?->value ?? '',
            'monitor.uuid' => (string) $monitor->uuid,
            'monitor.link' => $this->link($monitor),
            'event' => $message->event->value,
            'error' => (string) $message->error,
            'occurred_at' => $message->occurredAt->toDayDateTimeString(),
            'incident.duration' => $message->incident
                ? self::humanDuration($message->incident->durationSeconds())
                : '',
        ];
    }

    /**
     * The test-send builds an unsaved sample monitor, which has no route key.
     */
    private function link(Monitor $monitor): string
    {
        return $monitor->exists
            ? route('monitors.show', $monitor)
            : (string) config('app.url');
    }
}
