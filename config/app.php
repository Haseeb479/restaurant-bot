<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application, which will be used when the
    | framework needs to place the application's name in a notification or
    | other UI elements where an application name needs to be displayed.
    |
    */

    'name' => env('APP_NAME', 'Laravel'),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services the application utilizes. Set this in your ".env" file.
    |
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */

    'debug' => (bool) env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | the application so that it's available within Artisan commands.
    |
    */

    'url' => env('APP_URL', 'http://localhost'),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. The timezone
    | is set to "UTC" by default as it is suitable for most use cases.
    |
    */

    'timezone' => env('APP_TIMEZONE', 'Asia/Karachi'),

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by Laravel's translation / localization methods. This option can be
    | set to any locale for which you plan to have translation strings.
    |
    */

    'locale' => env('APP_LOCALE', 'en'),

    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),

    'faker_locale' => env('APP_FAKER_LOCALE', 'en_US'),

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is utilized by Laravel's encryption services and should be set
    | to a random, 32 character string to ensure that all encrypted values
    | are secure. You should do this prior to deploying the application.
    |
    */

    'cipher' => 'AES-256-CBC',

    'key' => env('APP_KEY'),

    'previous_keys' => [
        ...array_filter(
            explode(',', (string) env('APP_PREVIOUS_KEYS', ''))
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Maintenance Mode Driver
    |--------------------------------------------------------------------------
    |
    | These configuration options determine the driver used to determine and
    | manage Laravel's "maintenance mode" status. The "cache" driver will
    | allow maintenance mode to be controlled across multiple machines.
    |
    | Supported drivers: "file", "cache"
    |
    */

    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', 'database'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Super-Admin Master Password
    |--------------------------------------------------------------------------
    |
    | REQUIRED to use the super-admin panel. There is deliberately no default:
    | a missing value means no password can log in. May be a plaintext value or
    | a bcrypt hash — on first successful login it is persisted as a hash in the
    | `settings` table, after which it can be rotated from Admin → Settings.
    |
    */

    'admin_password' => env('ADMIN_PASSWORD'),

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Bot Internal Control API
    |--------------------------------------------------------------------------
    |
    | Base URL of the Node bot's internal HTTP control server (QR status,
    | send-message, restart, cache invalidation). Kept in config so it resolves
    | correctly even when the config cache is warm. See BOT_INTERNAL_PORT on the
    | bot side for the matching listen port.
    |
    */

    'bot_internal_api' => env('BOT_INTERNAL_API', 'http://127.0.0.1:3000'),

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Bot Internal Control Token
    |--------------------------------------------------------------------------
    |
    | Shared secret sent as `X-Bot-Token` on every call to the bot's control
    | server, and required by the bot before it will answer. The control server
    | can hand out the WhatsApp pairing QR and send messages as the restaurant,
    | so it must never be reachable without this. The bot refuses to serve any
    | route when the token is unset, so both sides must agree.
    |
    | Generate one with:  php -r "echo bin2hex(random_bytes(32));"
    |
    */

    'bot_internal_token' => env('BOT_INTERNAL_TOKEN', ''),

];
