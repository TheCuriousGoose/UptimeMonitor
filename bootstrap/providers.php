<?php

use App\Providers\AppServiceProvider;
use App\Providers\FortifyServiceProvider;
use App\Providers\TelescopeServiceProvider;

return [
    AppServiceProvider::class,
    FortifyServiceProvider::class,
    ...in_array(config('app.env'), ['local', 'development']) ? [TelescopeServiceProvider::class] : [],
];
