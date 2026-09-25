# LaraNative

**Package and distribute existing Laravel applications as native, offline-first applications.**

[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)
[![PHP Version](https://img.shields.io/badge/PHP-8.1+-8892BF.svg)](https://php.net)
[![Laravel](https://img.shields.io/badge/Laravel-10.x%20|%2011.x%20|%2012.x%20|%2013.x-FF2D20.svg)](https://laravel.com)

---

## What is LaraNative?

LaraNative is a developer framework and CLI that allows an **existing Laravel application** to be packaged and distributed as a native, offline-first application for multiple platforms — without rewriting your application.

```
Existing Laravel Application
         +
    LaraNative Runtime
         +
      Local SQLite
         +
    Native WebView Shell
         ↓
   Platform Application (APK, EXE, AppImage, .app, IPA)
```

## Why LaraNative?

- **No Rewrite Required** — Your existing Laravel routes, controllers, models, views, authentication, validation, and business logic work as-is.
- **Offline-First** — Applications work without internet by default. SQLite replaces MySQL for local storage.
- **Multi-Platform** — Target Android, Windows, Linux, macOS, and iOS from a single codebase.
- **Secure** — Production database credentials, API secrets, and server keys are never bundled into client applications.

## Supported Platforms

| Platform | Status | Artifact |
|----------|--------|----------|
| Android  | ✅ Implemented | APK, AAB |
| Windows  | 🔲 Planned | EXE, MSIX |
| Linux    | 🔲 Planned | AppImage, .deb |
| macOS    | 🔲 Planned | .app, .dmg |
| iOS      | 🔲 Planned | .ipa, .xcodeproj |

## Installation

```bash
composer require tymiqly/lara-native
```

## Quick Start

```bash
# 1. Install LaraNative into your Laravel project
php artisan laranative:install

# 2. Configure your application
#    Edit config/laranative.php

# 3. Set up the development environment
php artisan laranative:setup

# 4. Check your environment
php artisan laranative:doctor

# 5. Start development mode
php artisan laranative:dev

# 6. Build for a platform
php artisan laranative:build android
php artisan laranative:build android --release
```

## Architecture

LaraNative uses a layered architecture:

```
┌─────────────────────────────┐
│    Native Application       │
│  (WebView + App Shell)      │
├─────────────────────────────┤
│    Local PHP Server         │
│  (Embedded PHP Runtime)     │
├─────────────────────────────┤
│    Laravel Application      │
│  (Your existing code)       │
├─────────────────────────────┤
│    SQLite Database           │
│  (Offline-first storage)    │
└─────────────────────────────┘
```

### Key Components

- **Runtime Layer** — Embedded PHP runtime that serves Laravel locally on the device.
- **Platform Builders** — Generates real platform-specific projects (Gradle for Android, Xcode for iOS, etc.).
- **Build Pipeline** — Validates, prepares, and packages your Laravel application step by step.
- **Storage Abstraction** — Platform-aware file paths that respect each OS's conventions.
- **SQLite Manager** — Automatic database configuration and migration management.
- **Security Validator** — Prevents secrets from leaking into client builds.
- **Connectivity Manager** — Classifies services as offline/online/online-required.

## CLI Commands

| Command | Description |
|---------|-------------|
| `laranative:install` | Install LaraNative into your Laravel project |
| `laranative:setup` | Set up the development environment |
| `laranative:doctor` | Diagnose environment and report issues |
| `laranative:status` | Show project status |
| `laranative:dev` | Start the development server |
| `laranative:build <platform>` | Build for a target platform |
| `laranative:clean` | Clean build artifacts |
| `laranative:logs` | View runtime logs |

### Build Options

```bash
php artisan laranative:build android --debug      # Debug build (default)
php artisan laranative:build android --release     # Release build
php artisan laranative:build android --arch=arm64  # Specific architecture
php artisan laranative:build android --output=./out # Custom output directory
php artisan laranative:build android --skip-build  # Generate project only
php artisan laranative:build android --no-sign     # Skip signing
php artisan laranative:clean --platform=android    # Clean specific platform
```

## Configuration

All configuration is in `config/laranative.php`:

```php
return [
    'name' => 'My Application',
    'id' => 'com.example.myapp',
    'version' => '1.0.0',

    'platforms' => [
        'android' => [
            'min_sdk' => 24,
            'target_sdk' => 35,
        ],
    ],

    'database' => [
        'connection' => 'sqlite',
        'auto_migrate' => true,
    ],

    'sync' => [
        'enabled' => false,
        'url' => env('LARANATIVE_SYNC_URL'),
    ],

    'services' => [
        'auth' => 'offline',
        'crud' => 'offline',
        'payment' => 'online-required',
    ],
];
```

## Offline-First

LaraNative applications are offline by default. The following work without internet:

- ✅ Authentication (local sessions)
- ✅ CRUD operations (SQLite)
- ✅ Laravel routes, controllers, models
- ✅ Validation and business logic
- ✅ File storage
- ✅ Application settings

Services that inherently require internet are explicitly marked:

- ❌ Payment gateways (`online-required`)
- ❌ External OAuth (`online-required`)
- ❌ Email APIs (`online-required`)
- ❌ Remote APIs (`online-required`)

## Security

LaraNative takes security seriously:

- **Never** bundles MySQL/Postgres credentials in client apps
- **Never** exposes server secrets or API keys
- **Validates** production builds for exposed secrets via `laranative:doctor`
- **Enforces** HTTPS for sync communication
- **Warns** when `APP_DEBUG=true` in release builds

## Limitations

- PHP runtime must be bundled per platform (increases app size)
- Not all Laravel features are applicable offline (queues, mail, etc.)
- iOS builds require macOS + Xcode
- Large Eloquent operations may be slower on SQLite vs MySQL
- Hot reload in dev mode is limited to page refresh

## Development Setup

```bash
# Clone the repository
git clone https://github.com/tymiqly/lara-native.git
cd lara-native

# Install dependencies
composer install

# Run tests
composer test
```

## Requirements

- PHP 8.1+
- Laravel 10.x, 11.x, 12.x, or 13.x
- SQLite PHP extensions (`pdo_sqlite`, `sqlite3`)

### Platform-Specific Requirements

| Platform | Requirements |
|----------|-------------|
| Android | JDK 17+, Android SDK 34+ |
| iOS | macOS, Xcode 15+ |
| Windows | Windows 10+ |
| Linux | Linux host |
| macOS | macOS host |

## License

LaraNative is open-sourced software licensed under the [MIT license](LICENSE).

## Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## Security

If you discover a security vulnerability, please see [SECURITY.md](SECURITY.md).
