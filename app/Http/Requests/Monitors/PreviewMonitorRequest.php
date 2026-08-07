<?php

namespace App\Http\Requests\Monitors;

use App\Models\Monitor;
use Illuminate\Support\Arr;

/**
 * A check the user has configured but not saved.
 *
 * Extends the write request so a preview is validated by exactly the rules
 * that would accept the save — a check that runs here cannot then be rejected
 * on submit, and the outbound-target guards apply either way. Scheduling and
 * alerting fields are dropped: nothing about a one-off run is scheduled, and
 * nothing about it notifies anybody.
 */
class PreviewMonitorRequest extends MonitorRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Monitor::class);
    }

    public function rules(): array
    {
        return array_diff_key(parent::rules(), array_flip([
            'name',
            'is_active',
            'interval_seconds',
            'confirmation_threshold',
            'recovery_threshold',
            'degraded_response_ms',
            'notification_channels',
            'notification_channels.*',
        ])) + [
            // Names the monitor being edited, so credentials the form sent
            // back as the mask resolve from storage rather than being tested
            // as the literal string of asterisks.
            'monitor' => ['sometimes', 'nullable', 'string', 'uuid'],
        ];
    }

    /**
     * An in-memory monitor for the checker to run against.
     *
     * Never saved and deliberately never given a key: everything downstream
     * of a real check — recording the result, opening an incident, notifying
     * a channel — is keyed by monitor id and must find nothing to act on.
     */
    public function toMonitor(): Monitor
    {
        // `monitor` names the record to unmask against; it is not an attribute
        // of the throwaway one being built.
        return new Monitor(Arr::except($this->monitorAttributes(), 'monitor'));
    }

    /**
     * Resolved through the user's own monitors: the uuid arrives from the
     * form, so it must not be able to reach somebody else's credentials.
     */
    protected function existingMonitor(): ?Monitor
    {
        $uuid = $this->input('monitor');

        if (! is_string($uuid) || $uuid === '') {
            return null;
        }

        return Monitor::query()
            ->forUser($this->user())
            ->where('uuid', $uuid)
            ->first();
    }
}
