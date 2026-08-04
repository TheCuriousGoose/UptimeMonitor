<?php

namespace App\Http\Requests\Admin;

use App\Models\ContentEntry;

class StoreContentEntryRequest extends ContentEntryRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', ContentEntry::class);
    }
}
