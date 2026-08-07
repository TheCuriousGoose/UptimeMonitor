<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SettingType;
use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Policies\SettingPolicy;
use App\Settings\SettingRepository;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

#[UsePolicy(SettingPolicy::class)]
class SettingController extends Controller
{
    public function __construct(private readonly SettingRepository $settings) {}

    public function index(): Response
    {
        $this->authorize('viewAny', Setting::class);

        return Inertia::render('admin/Settings', [
            'settings' => $this->settings->tree(),
        ]);
    }

    public function update(Request $request, string $key): RedirectResponse
    {
        $this->authorize('update', Setting::class);

        $setting = Setting::where('key', $key)->firstOrFail();

        $rules = match ($setting->type) {
            SettingType::Boolean => ['value' => ['required', 'boolean']],
            SettingType::Integer => ['value' => ['required', 'integer']],
            SettingType::Float => ['value' => ['required', 'numeric']],
            SettingType::Json => ['value' => ['required', 'json']],
            SettingType::String => ['value' => ['nullable', 'string', 'max:10000']],
            SettingType::Secret => ['value' => ['nullable', 'string', 'max:10000']],
        };

        $data = $request->validate($rules);

        // A blank secret means "leave the stored one alone" — the form never
        // receives the current value, so it cannot round-trip it back.
        if ($setting->isSecret() && ($data['value'] ?? '') === '') {
            return back();
        }

        $this->settings->set($key, $data['value'] ?? '');

        return back()->with('success', "Setting \"{$setting->label}\" updated.");
    }
}
