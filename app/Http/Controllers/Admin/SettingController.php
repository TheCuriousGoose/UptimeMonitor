<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SettingType;
use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Settings\SettingRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    public function __construct(private readonly SettingRepository $settings) {}

    public function index(): Response
    {
        return Inertia::render('admin/Settings', [
            'settings' => $this->settings->all()->map(fn ($s) => [
                'id'          => $s->id,
                'key'         => $s->key,
                'group'       => $s->group,
                'label'       => $s->label,
                'description' => $s->description,
                'type'        => $s->type->value,
                'value'       => $s->value,
            ])->values(),
        ]);
    }

    public function update(Request $request, string $key): RedirectResponse
    {
        $setting = Setting::where('key', $key)->firstOrFail();

        $rules = match ($setting->type) {
            SettingType::Boolean => ['value' => ['required', 'boolean']],
            SettingType::Integer => ['value' => ['required', 'integer']],
            SettingType::Float   => ['value' => ['required', 'numeric']],
            SettingType::Json    => ['value' => ['required', 'json']],
            SettingType::String  => ['value' => ['required', 'string', 'max:10000']],
        };

        $data = $request->validate($rules);

        $this->settings->set($key, $data['value']);

        return back()->with('success', "Setting \"{$setting->label}\" updated.");
    }
}
