<?php

namespace App\Providers;

use App\Checkers\CheckerRegistry;
use App\Checkers\DnsChecker;
use App\Checkers\HttpChecker;
use App\Checkers\KeywordChecker;
use App\Checkers\PingChecker;
use App\Checkers\PortChecker;
use App\Checkers\SslChecker;
use App\Checkers\Support\CertificateReader;
use App\Checkers\Support\DnsResolver;
use App\Checkers\Support\PingRunner;
use App\Checkers\Support\SocketConnector;
use App\Checkers\Support\StreamCertificateReader;
use App\Checkers\Support\StreamSocketConnector;
use App\Checkers\Support\SystemDnsResolver;
use App\Checkers\Support\SystemPingRunner;
use App\Enums\ChannelType;
use App\Enums\MonitorType;
use App\Monitoring\Notifiers\DiscordNotifier;
use App\Monitoring\Notifiers\MailNotifier;
use App\Monitoring\Notifiers\NotifierRegistry;
use App\Monitoring\Notifiers\SlackNotifier;
use App\Monitoring\Notifiers\WebhookNotifier;
use App\Settings\SettingRepository;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(CheckerRegistry::class, fn () => new CheckerRegistry([
            MonitorType::Http->value => HttpChecker::class,
            MonitorType::Keyword->value => KeywordChecker::class,
            MonitorType::Port->value => PortChecker::class,
            MonitorType::Ping->value => PingChecker::class,
            MonitorType::Dns->value => DnsChecker::class,
            MonitorType::Ssl->value => SslChecker::class,
        ]));

        $this->app->singleton(NotifierRegistry::class, fn () => new NotifierRegistry([
            ChannelType::Email->value => MailNotifier::class,
            ChannelType::Webhook->value => WebhookNotifier::class,
            ChannelType::Slack->value => SlackNotifier::class,
            ChannelType::Discord->value => DiscordNotifier::class,
        ]));

        // Bound as interfaces so tests can swap in fakes for network access.
        $this->app->bind(SocketConnector::class, StreamSocketConnector::class);
        $this->app->bind(DnsResolver::class, SystemDnsResolver::class);
        $this->app->bind(CertificateReader::class, StreamCertificateReader::class);
        $this->app->bind(PingRunner::class, SystemPingRunner::class);

        $this->app->singleton(SettingRepository::class);

        $this->registerTelescope();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();

        Gate::before(function ($user, $ability) {
            return $user->hasRole('Super Admin') ? true : null;
        });
    }

    /**
     * Telescope ships as a dev dependency. Registering its provider from
     * bootstrap/providers.php would fatal on a `--no-dev` install, so it is
     * only wired up when the package is actually present.
     */
    protected function registerTelescope(): void
    {
        if (! class_exists(\Laravel\Telescope\Telescope::class)) {
            return;
        }

        if (! $this->app->environment('local')) {
            return;
        }

        $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
        $this->app->register(TelescopeServiceProvider::class);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
