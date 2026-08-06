<?php

namespace App\Http\Requests\Monitors;

use App\Checkers\Support\OutboundGuard;
use App\Enums\MonitorType;
use App\Models\Monitor;
use App\Monitoring\ConfigMasker;
use App\Rules\Hostname;
use App\Rules\HttpHeaderName;
use App\Rules\PublicUrl;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Shared validation for creating and updating monitors. The `config` payload
 * is validated against the rules declared by the selected monitor type.
 */
abstract class MonitorRequest extends FormRequest
{
    /**
     * The form always submits the notification_channels key, using a blank
     * entry when nothing is ticked, so that clearing the list is possible.
     * Drop those blanks before the exists rule sees them.
     */
    protected function prepareForValidation(): void
    {
        if (! $this->has('notification_channels')) {
            return;
        }

        $this->merge([
            'notification_channels' => array_values(array_filter(
                (array) $this->input('notification_channels'),
                fn ($uuid) => is_string($uuid) && $uuid !== '',
            )),
        ]);
    }

    public function rules(): array
    {
        $type = $this->monitorType();

        return array_merge([
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', Rule::enum(MonitorType::class)],
            'url' => $this->targetRules($type),
            'is_active' => ['sometimes', 'boolean'],
            'timeout' => ['required', 'integer', 'min:1', 'max:'.config('monitoring.max_timeout_seconds', 300)],
            'interval_seconds' => [
                'required',
                'integer',
                'min:'.config('monitoring.min_interval_seconds', 30),
                'max:'.config('monitoring.max_interval_seconds', 86400),
            ],
            'confirmation_threshold' => ['sometimes', 'integer', 'min:1', 'max:10'],
            'recovery_threshold' => ['sometimes', 'integer', 'min:1', 'max:10'],
            // Above the timeout it could never fire, so that is the ceiling.
            'degraded_response_ms' => [
                'sometimes', 'nullable', 'integer', 'min:1',
                'max:'.(config('monitoring.max_timeout_seconds', 300) * 1000),
            ],
            'config' => ['sometimes', 'array'],
            'notification_channels' => ['sometimes', 'array'],
            'notification_channels.*' => [
                'string',
                Rule::exists('notification_channels', 'uuid')->where('user_id', $this->user()->id),
            ],
        ], $type?->configRules() ?? []);
    }

    public function attributes(): array
    {
        return [
            'url' => __('monitors.form.url.title'),
            'interval_seconds' => __('monitors.form.check_interval.title'),
            'config.keyword' => __('monitors.form.config.keyword.title'),
            'config.port' => __('monitors.form.config.port.title'),
            'config.record_type' => __('monitors.form.config.record_type.title'),
        ];
    }

    /**
     * Attributes for the monitor itself, with the channel list stripped out.
     *
     * @return array<string, mixed>
     */
    public function monitorAttributes(): array
    {
        $data = $this->safe()->except('notification_channels');

        $type = $this->monitorType();

        if ($type !== null) {
            // Drop any config keys that do not belong to the chosen type.
            $allowed = array_keys($type->defaultConfig());
            $config = $this->castConfig(
                array_intersect_key($data['config'] ?? [], array_flip($allowed)),
            );

            // Anything the form sent back as the mask is unchanged, so put
            // the stored credential back rather than overwriting it.
            $existing = $this->route('monitor');

            $data['config'] = $existing instanceof Monitor
                ? ConfigMasker::unmask($config, $existing->resolvedConfig())
                : $config;
        }

        return $data;
    }

    /**
     * @return array<int, string>
     */
    public function channelUuids(): array
    {
        return array_values(array_filter((array) $this->safe()->input('notification_channels', [])));
    }

    /**
     * The config cast serialises whatever it is handed, so a checkbox posting
     * "0" would be stored as the string "0" — which is truthy everywhere it
     * gets read. Coerce values to their real types here.
     *
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    private function castConfig(array $config): array
    {
        $casts = [
            'verify_ssl' => 'bool',
            'invert' => 'bool',
            'follow_redirects' => 'bool',
            'port' => 'int',
            'warn_days' => 'int',
            'max_redirects' => 'int',
            'expected_status' => 'nullable_int',
            'expected' => 'nullable_string',
            'body' => 'nullable_string',
            'content_type' => 'nullable_string',
            'auth_username' => 'nullable_string',
            'auth_password' => 'nullable_string',
            'auth_token' => 'nullable_string',
            'headers' => 'array',
            'expected_status_codes' => 'array',
        ];

        foreach ($config as $key => $value) {
            $config[$key] = match ($casts[$key] ?? 'string') {
                'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
                'int' => (int) $value,
                'nullable_int' => ($value === null || $value === '') ? null : (int) $value,
                'nullable_string' => ($value === null || $value === '') ? null : (string) $value,
                'array' => is_array($value) ? $value : [],
                default => $value,
            };
        }

        return $config;
    }

    /**
     * Cross-field checks that need the whole payload.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $config = (array) $this->input('config', []);

                $this->validateHeaderNames($validator, $config);
                $this->validateCredentialTarget($validator, $config);
            },
        ];
    }

    /**
     * Header names arrive as array keys, which Laravel's rules cannot reach.
     *
     * @param  array<string, mixed>  $config
     */
    private function validateHeaderNames(Validator $validator, array $config): void
    {
        foreach (array_keys((array) ($config['headers'] ?? [])) as $name) {
            if ($message = HttpHeaderName::reject($name)) {
                $validator->errors()->add('config.headers', __($message, ['name' => (string) $name]));
            }
        }
    }

    /**
     * Credentials must never be pointed at a private address, whatever
     * monitoring.outbound.allow_private_targets says. Monitoring an internal
     * host stays allowed; sending secrets to one does not, because the app
     * cannot tell a service the user owns from one they are probing.
     *
     * @param  array<string, mixed>  $config
     */
    private function validateCredentialTarget(Validator $validator, array $config): void
    {
        $carriesSecrets = ($config['headers'] ?? []) !== []
            || ($config['auth_type'] ?? 'none') !== 'none'
            || (is_string($config['body'] ?? null) && $config['body'] !== '');

        if (! $carriesSecrets || ! $this->monitorType()?->expectsUrl()) {
            return;
        }

        $rule = new PublicUrl(app(OutboundGuard::class));

        if (! $rule->allows($this->input('url'))) {
            $validator->errors()->add('url', __('validation.public_url'));
        }
    }

    protected function monitorType(): ?MonitorType
    {
        return MonitorType::tryFrom((string) $this->input('type'));
    }

    /**
     * @return array<int, mixed>
     */
    private function targetRules(?MonitorType $type): array
    {
        if ($type === null || $type->expectsUrl()) {
            return ['required', 'string', 'max:255', 'url:http,https'];
        }

        return ['required', 'string', 'max:255', new Hostname];
    }
}
