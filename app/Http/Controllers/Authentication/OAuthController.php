<?php

namespace App\Http\Controllers\Authentication;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class OAuthController extends Controller
{
    protected array $providers = ['google', 'github'];

    public function redirect(string $provider): RedirectResponse
    {
        abort_unless(in_array($provider, $this->providers), 404);

        return Socialite::driver($provider)->redirect();
    }

    public function callback(string $provider): RedirectResponse
    {
        abort_unless(in_array($provider, $this->providers), 404);
        
        $socialiteUser = Socialite::driver($provider)->user();

        $user = User::firstOrCreate(
            ['oauth_provider' => $provider, 'oauth_id' => $socialiteUser->getId()],
            [
                'name' => $socialiteUser->getName() ?? $socialiteUser->getNickname() ?? '',
                'email' => $socialiteUser->getEmail(),
                'email_verified_at' => now(),
            ],
        );

        Auth::login($user, remember: true);

        return to_route('monitors.index')->with('success', __('auth.successful'));
    }
}

