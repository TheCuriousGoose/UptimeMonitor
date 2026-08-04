<?php

namespace App\Http\Requests\Monitors;

use App\Models\Monitor;

class StoreRequest extends MonitorRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Monitor::class);
    }
}
