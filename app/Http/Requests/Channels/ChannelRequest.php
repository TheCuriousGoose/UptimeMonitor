<?php

namespace App\Http\Requests\Channels;

use App\Enums\ChannelType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class ChannelRequest extends FormRequest
{
    public function rules(): array
    {
        $type = ChannelType::tryFrom((string) $this->input('type'));

        return array_merge([
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', Rule::enum(ChannelType::class)],
            'is_active' => ['sometimes', 'boolean'],
            'config' => ['required', 'array'],
        ], $type?->configRules() ?? []);
    }

    /**
     * @return array<string, mixed>
     */
    public function channelAttributes(): array
    {
        $data = $this->safe()->only(['name', 'type', 'is_active', 'config']);
        $type = ChannelType::tryFrom((string) $this->input('type'));

        // Keep only the config key this channel type actually uses.
        $allowed = $type === ChannelType::Email ? ['email'] : ['url'];
        $data['config'] = array_intersect_key($data['config'] ?? [], array_flip($allowed));

        return $data;
    }
}
