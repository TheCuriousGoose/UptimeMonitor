<?php

namespace App\Http\Requests\Admin;

use App\Models\ContentEntry;

class UpdateContentEntryRequest extends ContentEntryRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', ContentEntry::class);
    }
}
