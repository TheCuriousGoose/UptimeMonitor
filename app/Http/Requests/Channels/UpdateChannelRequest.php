<?php

namespace App\Http\Requests\Channels;

class UpdateChannelRequest extends ChannelRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('channel'));
    }
}
