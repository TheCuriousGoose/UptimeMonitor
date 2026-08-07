<?php

namespace App\Http\Requests\Onboarding;

use App\Http\Requests\Monitors\MonitorRequest;
use App\Models\Monitor;
use Illuminate\Support\Arr;

/**
 * The guided setup's single commit: a monitor, and optionally somewhere for it
 * to reach the user.
 *
 * Extends the ordinary write request so the wizard cannot accept a monitor the
 * normal form would reject — the flow is a different way through the same
 * decisions, not a second set of rules.
 */
class CompleteSetupRequest extends MonitorRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Monitor::class);
    }

    public function rules(): array
    {
        return parent::rules() + [
            // Set when the user takes the offered "just email me" option; the
            // channel is created alongside the monitor rather than leaving
            // them to go and find the integrations page.
            'alert_email' => ['sometimes', 'nullable', 'email', 'max:255'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function monitorAttributes(): array
    {
        // Where to send alerts, not a property of the monitor itself.
        return Arr::except(parent::monitorAttributes(), 'alert_email');
    }

    public function alertEmail(): ?string
    {
        $email = $this->validated('alert_email');

        return is_string($email) && $email !== '' ? $email : null;
    }
}
