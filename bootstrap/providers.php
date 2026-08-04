<?php

use App\Providers\AppServiceProvider;
use App\Providers\FortifyServiceProvider;
use App\Providers\HorizonServiceProvider;
use App\Providers\RateLimitServiceProvider;

return [
    AppServiceProvider::class,
    FortifyServiceProvider::class,
    HorizonServiceProvider::class,
    RateLimitServiceProvider::class,
    // Telescope is a dev-only dependency, so it is registered conditionally
    // from AppServiceProvider rather than listed here — listing it breaks
    // `composer install --no-dev`, where its parent class does not exist.
];
