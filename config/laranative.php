<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | The display name of your native application. This appears in the app
    | launcher, title bar, and platform-specific metadata.
    |
    */

    'name' => env('LARANATIVE_APP_NAME', config('app.name', 'LaraNative App')),

    /*
    |--------------------------------------------------------------------------
    | Application ID
    |--------------------------------------------------------------------------
    |
    | A unique identifier for your application. Used as the Android package
    | name, iOS bundle identifier, and Windows identity.
    | Format: com.yourcompany.yourapp
    |
    */

    'id' => env('LARANATIVE_APP_ID', 'com.example.app'),

    /*
    |--------------------------------------------------------------------------
    | Application Version
    |--------------------------------------------------------------------------
    |
    | The application version. Used for platform versioning, update detection,
    | and metadata.
    |
    */

    'version' => env('LARANATIVE_VERSION', '1.0.0'),

    /*
    |--------------------------------------------------------------------------
    | Version Code
    |--------------------------------------------------------------------------
    |
    | Numeric version code (primarily used by Android). Must be incremented
    | for each release published to the Play Store.
    |
    */

    'version_code' => (int) env('LARANATIVE_VERSION_CODE', 1),

    /*
    |--------------------------------------------------------------------------
    | Platforms
    |--------------------------------------------------------------------------
    |
    | Configure individual platform settings. Each platform can be enabled
    | or disabled and has its own configuration section.
    |
    */

    'platforms' => [

        'android' => [
            'enabled' => true,
            'package' => env('LARANATIVE_ANDROID_PACKAGE', null), // Falls back to 'id'
            'min_sdk' => 24,
            'target_sdk' => 35,
            'compile_sdk' => 35,
            'permissions' => [
                'INTERNET',
                'ACCESS_NETWORK_STATE',
            ],
            'signing' => [
                'keystore' => env('LARANATIVE_ANDROID_KEYSTORE', null),
                'keystore_password' => env('LARANATIVE_ANDROID_KEYSTORE_PASSWORD', null),
                'key_alias' => env('LARANATIVE_ANDROID_KEY_ALIAS', null),
                'key_password' => env('LARANATIVE_ANDROID_KEY_PASSWORD', null),
            ],
        ],

        'ios' => [
            'enabled' => true,
            'bundle_id' => env('LARANATIVE_IOS_BUNDLE_ID', null), // Falls back to 'id'
            'deployment_target' => '15.0',
            'team_id' => env('LARANATIVE_IOS_TEAM_ID', null),
            'provisioning_profile' => env('LARANATIVE_IOS_PROVISIONING_PROFILE', null),
        ],

        'windows' => [
            'enabled' => true,
            'identity' => env('LARANATIVE_WINDOWS_IDENTITY', null),
            'certificate' => env('LARANATIVE_WINDOWS_CERTIFICATE', null),
        ],

        'macos' => [
            'enabled' => true,
            'bundle_id' => env('LARANATIVE_MACOS_BUNDLE_ID', null),
            'team_id' => env('LARANATIVE_MACOS_TEAM_ID', null),
            'notarize' => false,
        ],

        'linux' => [
            'enabled' => true,
            'format' => 'appimage', // appimage, deb, rpm, snap
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Database
    |--------------------------------------------------------------------------
    |
    | Database configuration for the native application. LaraNative uses
    | SQLite as the primary offline database. The path is dynamically
    | resolved per platform.
    |
    */

    'database' => [
        'connection' => 'sqlite',
        'filename' => 'laranative.sqlite',
        'auto_migrate' => true,
        'wal_mode' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Runtime
    |--------------------------------------------------------------------------
    |
    | Runtime configuration for the embedded PHP server that powers the
    | Laravel application on the target platform.
    |
    */

    'runtime' => [
        'host' => '127.0.0.1',
        'port' => 8080,
        'workers' => 2,
        'max_request_size' => '10M',
        'timeout' => 300,
        'php_version' => '8.3',
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage
    |--------------------------------------------------------------------------
    |
    | Storage configuration for the native application. All paths are
    | resolved dynamically per platform.
    |
    */

    'storage' => [
        'driver' => 'local',
        'max_upload_size' => '50M',
    ],

    /*
    |--------------------------------------------------------------------------
    | Sync
    |--------------------------------------------------------------------------
    |
    | Configuration for optional data synchronization between the local
    | SQLite database and a remote Laravel API. Never stores server-side
    | database credentials in the client application.
    |
    */

    'sync' => [
        'enabled' => false,
        'url' => env('LARANATIVE_SYNC_URL', null),
        'interval' => 300, // seconds
        'auth' => [
            'driver' => 'token', // token, sanctum, passport
            'token_name' => 'laranative-sync',
        ],
        'conflict_resolution' => 'server-wins', // server-wins, client-wins, manual
        'models' => [
            // 'App\\Models\\User' => [
            //     'sync' => true,
            //     'direction' => 'both', // push, pull, both
            //     'soft_delete' => true,
            // ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Offline Services
    |--------------------------------------------------------------------------
    |
    | Define the connectivity requirements for each service in your app.
    | Possible values: 'offline', 'online', 'online-required'
    |
    */

    'services' => [
        'auth' => 'offline',
        'crud' => 'offline',
        'routes' => 'offline',
        'validation' => 'offline',
        'file_storage' => 'offline',
        'settings' => 'offline',
        'sync' => 'online',
        'notifications_push' => 'online-required',
        'payment' => 'online-required',
        'email' => 'online-required',
        'external_api' => 'online-required',
        'oauth' => 'online-required',
    ],

    /*
    |--------------------------------------------------------------------------
    | Native Shell
    |--------------------------------------------------------------------------
    |
    | Configuration for the native application shell and WebView.
    |
    */

    'native' => [
        'webview' => [
            'allow_file_access' => true,
            'javascript_enabled' => true,
            'dom_storage' => true,
            'allow_content_access' => true,
        ],
        'window' => [
            'width' => 1280,
            'height' => 800,
            'min_width' => 320,
            'min_height' => 480,
            'resizable' => true,
            'fullscreen' => false,
        ],
        'splash' => [
            'enabled' => true,
            'duration' => 2000, // milliseconds
            'background_color' => '#FFFFFF',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Build
    |--------------------------------------------------------------------------
    |
    | Build process configuration.
    |
    */

    'build' => [
        'output_dir' => 'dist',
        'clean_before_build' => true,
        'compile_assets' => true,
        'optimize_laravel' => true,
        'exclude' => [
            'node_modules',
            '.git',
            '.env',
            '.env.backup',
            'tests',
            'phpunit.xml',
            '.phpunit.cache',
            '.idea',
            '.vscode',
            'storage/logs/*',
            'storage/framework/cache/*',
            'storage/framework/sessions/*',
            'storage/framework/views/*',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Assets
    |--------------------------------------------------------------------------
    |
    | Application icon and splash screen configuration. Provide a single
    | source image and LaraNative will generate platform-specific assets.
    |
    */

    'assets' => [
        'icon' => null, // Path to source icon (1024x1024 PNG recommended)
        'splash' => null, // Path to splash screen image
        'adaptive_icon' => [
            'foreground' => null,
            'background' => null,
            'background_color' => '#FFFFFF',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Security
    |--------------------------------------------------------------------------
    |
    | Security settings for production builds. These validations run
    | during `php artisan laranative:doctor`.
    |
    */

    'security' => [
        'enforce_https_sync' => true,
        'strip_debug_in_release' => true,
        'validate_env_secrets' => true,
        'allowed_env_keys' => [
            'APP_NAME',
            'APP_ENV',
            'APP_URL',
            'APP_LOCALE',
            'APP_TIMEZONE',
            'DB_CONNECTION',
            'DB_DATABASE',
            'LOG_CHANNEL',
            'LOG_LEVEL',
            'SESSION_DRIVER',
            'SESSION_LIFETIME',
            'CACHE_STORE',
        ],
        'forbidden_env_keys' => [
            'DB_HOST',
            'DB_PORT',
            'DB_USERNAME',
            'DB_PASSWORD',
            'REDIS_HOST',
            'REDIS_PASSWORD',
            'MAIL_HOST',
            'MAIL_PASSWORD',
            'AWS_ACCESS_KEY_ID',
            'AWS_SECRET_ACCESS_KEY',
            'STRIPE_SECRET',
            'STRIPE_KEY',
            'PUSHER_APP_SECRET',
        ],
    ],

];
