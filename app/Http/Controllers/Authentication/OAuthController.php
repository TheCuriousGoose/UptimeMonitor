<?php

namespace App\Http\Controllers\Authentication;

use App\Http\Controllers\Controller;
use App\Models\OAuthConnection;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Throwable;

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

        try {
            $socialiteUser = Socialite::driver($provider)->user();
        } catch (InvalidStateException) {
            return to_route('login')->withErrors(['email' => __('auth.oauth_invalid_state')]);
        } catch (Throwable) {
            return to_route('login')->withErrors(['email' => __('auth.oauth_failed')]);
        }

        $email = $socialiteUser->getEmail();

        if (! $email) {
            return to_route('login')->withErrors(['email' => __('auth.oauth_no_email')]);
        }

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $socialiteUser->getName() ?? $socialiteUser->getNickname() ?? '',
                'email_verified_at' => now(),
            ],
        );

        OAuthConnection::updateOrCreate(
            ['provider' => $provider, 'provider_id' => $socialiteUser->getId()],
            ['user_id' => $user->id],
        );

        Auth::login($user, remember: true);

        return to_route('monitors.index')->with('success', __('auth.successful'));
    }
}

