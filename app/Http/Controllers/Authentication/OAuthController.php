<?php

namespace App\Http\Controllers\Authentication;

use App\Actions\Users\CreateUser;
use App\Http\Controllers\Controller;
use App\Models\OAuthConnection;
use App\Settings\SettingRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Throwable;

class OAuthController extends Controller
{
    protected array $providers = ['google', 'github'];

    public function __construct(private readonly SettingRepository $settings) {}

    public function redirect(string $provider): RedirectResponse
    {
        $this->configure($provider);

        return Socialite::driver($provider)->redirect();
    }

    public function callback(string $provider): RedirectResponse
    {
        $this->configure($provider);

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

        $user = app(CreateUser::class)->firstOrCreate(
            name: $socialiteUser->getName() ?? $socialiteUser->getNickname() ?? '',
            email: $email,
            verified: true,
        );

        OAuthConnection::updateOrCreate(
            ['provider' => $provider, 'provider_id' => $socialiteUser->getId()],
            ['user_id' => $user->id],
        );

        Auth::login($user, remember: true);

        return to_route('monitors.index')->with('success', __('auth.successful'));
    }

    /**
     * Credentials live in settings so an operator can change them without a
     * deploy; the .env values stay as the fallback.
     */
    private function configure(string $provider): void
    {
        abort_unless(in_array($provider, $this->providers, true), 404);
        abort_unless($this->settings->get("oauth.{$provider}", false), 404);

        $stored = $this->settings->childrenOf("oauth.{$provider}");

        $clientId = $stored->get('client_id') ?: config("services.{$provider}.client_id");
        $clientSecret = $stored->get('client_secret') ?: config("services.{$provider}.client_secret");

        abort_if(blank($clientId) || blank($clientSecret), 404);

        config([
            "services.{$provider}.client_id" => $clientId,
            "services.{$provider}.client_secret" => $clientSecret,
            "services.{$provider}.redirect" => $stored->get('redirect')
                ?: (config("services.{$provider}.redirect") ?: route('oauth.callback', $provider)),
        ]);
    }
}
