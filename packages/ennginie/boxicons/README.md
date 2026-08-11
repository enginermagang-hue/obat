# ennginie/boxicons

[![Latest Version](https://img.shields.io/github/v/tag/enginermagang-hue/boxicons)](https://github.com/enginermagang-hue/boxicons)
[![License](https://img.shields.io/badge/license-MIT-blue)](https://github.com/enginermagang-hue/boxicons)

Boxicons icon package for Laravel Filament with PHP Enum support. Provides **~3.800+ SVG icons** across three sets (regular, solid, logos), integrated via [Blade Icons](https://github.com/blade-ui-kit/blade-icons) and Filament's `ScalableIcon` enum.

## Features

- Type-safe PHP `BackedEnum` prevents typos and enables IDE autocompletion
- Blade-ready — Use icons directly in Blade views via `<x-icon>` or `@svg()`
- Filament-native — Compatible with navigation icons, action icons, table columns, and form prefixes
- ~3.800+ icons — Regular (outline), Solid (filled), and Logos (brand) sets
- Laravel auto-discovery — Service provider auto-registers all icon sets

## Installation

> **Requires:** PHP ^8.2, Laravel 11.x–13.x, Filament ^5.0

### 1. Configure VCS repository

Add to your project's `composer.json`:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/enginermagang-hue/boxicons.git"
        }
    ],
    "require": {
        "ennginie/boxicons": "^1.0"
    }
}
```

Or using CLI:

```bash
composer config repositories.ennginie/boxicons vcs https://github.com/enginermagang-hue/boxicons.git
composer require ennginie/boxicons "^1.0"
```

### 2. Auto-discovery

The `BoxiconsServiceProvider` is auto-discovered by Laravel. No manual registration needed.

## Quick Start

### Filament Navigation Icon

```php
use Ennginie\Boxicons\Boxicon;

protected static ?string $navigationIcon = Boxicon::Warehouse->value;
```

### Filament Action Icon

```php
Action::make('export')
    ->icon(Boxicon::ArrowToBottom)
```

### Form Prefix Icon

```php
Select::make('role')
    ->prefixIcon(Boxicon::User)
```

### Table Icon Column

```php
IconColumn::make('status')
    ->icon(fn (string $state): string => match ($state) {
        'aktif' => Boxicon::CheckCircle->value,
        'nonaktif' => Boxicon::XCircle->value,
    })
```

### Infolist Icon Entry

```php
IconEntry::make('is_verified')
    ->icon(Boxicon::BadgeCheck)
```

### Blade View

```blade
{{-- Regular --}}
<x-icon name="bx-user" class="w-5 h-5" />
@svg('bx-user', 'w-5 h-5')

{{-- Solid --}}
<x-icon name="bxs-inbox" class="w-6 h-6 text-gray-500" />
@svg('bxs-inbox', 'w-6 h-6 text-gray-500')

{{-- Logos --}}
<x-icon name="bxl-github" class="w-8 h-8" />

{{-- With custom attributes --}}
<x-icon name="bx-check-circle" class="w-4 h-4 inline-block" style="color: #22c55e;" />
```

## Icon Sets

| Set | Enum Prefix | Blade Prefix | Example Enum | Example Blade |
|-----|-------------|--------------|--------------|---------------|
| Regular (outline) | *(none)* | `bx-` | `Boxicon::User` | `bx-user` |
| Solid (filled) | `Solid` | `bxs-` | `Boxicon::SolidInbox` | `bxs-inbox` |
| Logos (brands) | `Logo` | `bxl-` | `Boxicon::LogoGithub` | `bxl-github` |

## Enum Usage

The package provides a PHP `BackedEnum` (`\Ennginie\Boxicons\Boxicon`) that implements Filament's `ScalableIcon` contract:

```php
use Ennginie\Boxicons\Boxicon;

// Regular
Boxicon::Alarm;       // value: 'alarm'       -> Blade: 'bx-alarm'
Boxicon::User;        // value: 'user'        -> Blade: 'bx-user'
Boxicon::Trash;       // value: 'trash'       -> Blade: 'bx-trash'

// Solid (prefixed with 's-')
Boxicon::SolidAlarm;  // value: 's-alarm'     -> Blade: 'bxs-alarm'
Boxicon::SolidInbox;  // value: 's-inbox'     -> Blade: 'bxs-inbox'

// Logos (prefixed with 'l-')
Boxicon::LogoGithub;  // value: 'l-github'    -> Blade: 'bxl-github'
```

## Finding Icon Names

1. **Browse the Enum** — open `src/Boxicon.php` and search for the icon name (e.g., `case Trash`).
2. **Browse SVGs** — explore `resources/svg/{regular,solid,logos}/`.
3. **Official site** — visit [boxicons.com](https://boxicons.com) and convert the icon name:
   - `bx bx-trash` -> Enum: `Boxicon::Trash`, Blade: `bx-trash`
   - `bxs bxs-inbox` -> Enum: `Boxicon::SolidInbox`, Blade: `bxs-inbox`
   - `bxl bxl-github` -> Enum: `Boxicon::LogoGithub`, Blade: `bxl-github`

## License

This package is open-sourced software licensed under the [MIT license](https://github.com/enginermagang-hue/boxicons).
