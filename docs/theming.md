# Limen AI — Theming Guide

## Overview

Limen AI themes combine **palettes** (light/dark colors), **presets** (layout/locale defaults), and **overrides** (host app customizations). Components read resolved tokens via `ThemeResolver`.

## Configuration

```php
// config/limen-ai.php
'ui' => [
    'palettes' => [
        'light' => [ /* color tokens */ ],
        'dark' => [ /* color tokens */ ],
    ],
    'presets' => [
        'default' => [ /* radius, title, direction */ ],
        'arabic' => [ /* RTL + Arabic copy */ ],
    ],
    'theme' => [
        'preset' => env('LIMEN_AI_THEME_PRESET', 'default'),
        'mode' => env('LIMEN_AI_THEME_MODE', 'light'), // light|dark|auto
        'allow_mode_toggle' => env('LIMEN_AI_THEME_TOGGLE', false),
        'overrides' => [
            'primary' => '#2563EB',
        ],
    ],
],
```

## Environment Variables

| Variable | Purpose |
|----------|---------|
| `LIMEN_AI_THEME_PRESET` | Preset name (`default`, `arabic`, custom) |
| `LIMEN_AI_THEME_MODE` | `light`, `dark`, or `auto` |
| `LIMEN_AI_THEME_TOGGLE` | Show in-widget light/dark toggle |

## Blade Usage

```blade
<x-limen-ai::chatbot />

<x-limen-ai::chatbot :theme="['preset' => 'arabic']" />

<x-limen-ai::chatbot :theme="['mode' => 'dark', 'primary' => '#2563EB']" />

<x-limen-ai::widget :theme="['preset' => 'arabic', 'mode' => 'auto']" />
```

## Publish & Override

Publish UI assets and views:

```bash
php artisan vendor:publish --tag=limen-ai-ui
```

Override palette/preset values in `config/limen-ai.php` under `ui.theme.overrides` or add new entries to `ui.presets`.

## CSS Variables

Components expose tokens as CSS variables:

| Variable | Purpose |
|----------|---------|
| `--limen-ai-primary` | Buttons, accents |
| `--limen-ai-background` | Panel background |
| `--limen-ai-text` | Body text |
| `--limen-ai-surface` | Header/input surfaces |
| `--limen-ai-border` | Borders |
| `--limen-ai-muted` | Status text |
| `--limen-ai-radius` | Corner radius |

Dark/light switching uses `data-mode="light|dark"` on `.limen-ai-chat`. RTL uses `data-direction="rtl"`.

## Arabic / RTL Preset

Set `LIMEN_AI_THEME_PRESET=arabic` for RTL layout, Arabic placeholders, and a font stack suitable for Arabic script.
