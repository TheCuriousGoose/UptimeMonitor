<?php

namespace App\Http\Requests\Channels;

use App\Enums\AlertScope;
use App\Enums\ChannelType;
use App\Monitoring\AlertEvent;
use App\Monitoring\AlertTemplate;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class ChannelRequest extends FormRequest
{
    public function rules(): array
    {
        $type = ChannelType::tryFrom((string) $this->input('type'));

        return array_merge([
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', Rule::enum(ChannelType::class)],
            'is_active' => ['sometimes', 'boolean'],
            'config' => ['required', 'array'],
            'alert_scope' => ['sometimes', Rule::enum(AlertScope::class)],
            'renotify_minutes' => ['sometimes', 'nullable', 'integer', 'min:5', 'max:1440'],
            'renotify_limit' => ['sometimes', 'integer', 'min:1', 'max:20'],
            'quiet_hours_start' => ['sometimes', 'nullable', 'date_format:H:i', 'required_with:quiet_hours_end'],
            'quiet_hours_end' => ['sometimes', 'nullable', 'date_format:H:i', 'required_with:quiet_hours_start'],
            'quiet_hours_timezone' => ['sometimes', 'nullable', 'timezone', 'required_with:quiet_hours_start'],
            'monitors' => ['sometimes', 'array'],
            'monitors.*' => ['string', 'uuid'],
            'templates' => ['sometimes', 'nullable', 'array'],
            'templates.*.title' => ['sometimes', 'nullable', 'string', 'max:255', $this->placeholderRule()],
            'templates.*.body' => ['sometimes', 'nullable', 'string', 'max:2000', $this->placeholderRule()],
        ], $type?->configRules() ?? []);
    }

    /**
     * Rejects placeholders the renderer does not know, so a typo shows up on
     * the form rather than as a blank space during an outage.
     */
    private function placeholderRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) {
            $unknown = AlertTemplate::unknownPlaceholders((string) $value);

            if ($unknown !== []) {
                $fail(__('integrations.validation.unknown_placeholder', [
                    'placeholders' => implode(', ', $unknown),
                ]));
            }
        };
    }

    /**
     * Monitor uuids this channel should alert on, meaningful only when the
     * scope is `selected`.
     *
     * @return array<int, string>
     */
    public function monitorUuids(): array
    {
        return array_values(array_filter((array) $this->input('monitors', [])));
    }

    public function alertScope(): AlertScope
    {
        return AlertScope::tryFrom((string) $this->input('alert_scope')) ?? AlertScope::All;
    }

    /**
     * @return array<string, mixed>
     */
    public function channelAttributes(): array
    {
        // only() is an allowlist — a new column that is not named here is
        // silently dropped rather than saved.
        $data = $this->safe()->only([
            'name', 'type', 'is_active', 'config', 'alert_scope',
            'renotify_minutes', 'renotify_limit',
            'quiet_hours_start', 'quiet_hours_end', 'quiet_hours_timezone',
        ]);
        $type = ChannelType::tryFrom((string) $this->input('type'));

        // Keep only the config key this channel type actually uses, so a
        // payload cannot smuggle in keys belonging to another type.
        $allowed = $type ? [$type->destinationKey()] : [];
        $data['config'] = array_intersect_key($data['config'] ?? [], array_flip($allowed));

        if ($this->has('templates')) {
            $data['templates'] = $this->templates();
        }

        return $data;
    }

    /**
     * Normalised templates, keyed by event. Blank fields are dropped rather
     * than stored as empty strings so clearing one restores the built-in
     * wording instead of sending an alert with no text.
     *
     * @return array<string, array<string, string>>|null
     */
    private function templates(): ?array
    {
        $input = (array) $this->input('templates', []);
        $templates = [];

        foreach (AlertEvent::cases() as $event) {
            foreach (['title', 'body'] as $field) {
                $value = trim((string) ($input[$event->value][$field] ?? ''));

                if ($value !== '') {
                    $templates[$event->value][$field] = $value;
                }
            }
        }

        return $templates === [] ? null : $templates;
    }
}
