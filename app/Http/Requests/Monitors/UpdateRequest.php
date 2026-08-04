<?php

namespace App\Http\Requests\Monitors;

class UpdateRequest extends MonitorRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('monitor'));
    }
}
