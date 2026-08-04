<?php

namespace App\Http\Requests\Channels;

use App\Models\NotificationChannel;

class StoreChannelRequest extends ChannelRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', NotificationChannel::class);
    }
}
