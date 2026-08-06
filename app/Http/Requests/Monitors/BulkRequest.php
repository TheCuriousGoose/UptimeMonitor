<?php

namespace App\Http\Requests\Monitors;

use App\Models\Monitor;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkRequest extends FormRequest
{
    public const ACTIONS = ['pause', 'resume', 'delete'];

    /**
     * Deliberately permissive: authorisation is per monitor, not per request.
     * A batch is only as allowed as its least allowed member, and that cannot
     * be decided before the models are resolved.
     */
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', Monitor::class);
    }

    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in(self::ACTIONS)],
            // Capped so one request cannot turn into an unbounded write.
            'monitors' => ['required', 'array', 'min:1', 'max:100'],
            'monitors.*' => ['string', 'uuid'],
        ];
    }

    /**
     * The selected monitors, scoped to the owner.
     *
     * Resolving through forUser() rather than trusting the submitted uuids is
     * what stops a crafted request from pausing or deleting someone else's
     * monitors. Ids that do not belong to the user simply do not come back.
     */
    public function monitors(): Collection
    {
        return Monitor::query()
            ->forUser($this->user())
            ->whereIn('uuid', $this->array('monitors'))
            ->get();
    }

    public function action(): string
    {
        return (string) $this->input('action');
    }
}
