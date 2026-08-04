<?php

namespace App\Http\Requests\StatusPages;

class UpdateStatusPageRequest extends StatusPageRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('status_page'));
    }
}
