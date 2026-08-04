<?php

namespace App\Http\Requests\StatusPages;

use App\Models\StatusPage;

class StoreStatusPageRequest extends StatusPageRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', StatusPage::class);
    }
}
