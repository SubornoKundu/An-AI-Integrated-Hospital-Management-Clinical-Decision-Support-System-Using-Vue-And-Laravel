<?php

use Illuminate\Support\Facades\Facade;

return [
    'name'             => env('APP_NAME', 'MediCare Hospital'),
    'env'              => env('APP_ENV', 'production'),
    'debug'            => (bool) env('APP_DEBUG', false),
    'url'              => env('APP_URL', 'http://localhost'),
    'timezone'         => 'Asia/Dhaka',
    'locale'           => env('APP_LOCALE', 'en'),
    'fallback_locale'  => env('APP_FALLBACK_LOCALE', 'en'),
    'faker_locale'     => env('APP_FAKER_LOCALE', 'en_US'),
    'cipher'           => 'AES-256-CBC',
    'key'              => env('APP_KEY'),
    'previous_keys'    => [
        ...array_filter(explode(',', env('APP_PREVIOUS_KEYS', ''))),
    ],
    'maintenance'      => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store'  => env('APP_MAINTENANCE_STORE', 'database'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Class Aliases
    |--------------------------------------------------------------------------
    |
    | This array of class aliases is registered when the application starts.
    | Many of the Blade views in this project call facades like Str:: and
    | Storage:: without a "use" import, which only works if they're aliased
    | into the global namespace here.
    |
    */
    'aliases' => Facade::defaultAliases()->merge([
        // 'Example' => App\Facades\Example::class,
    ])->toArray(),
];
