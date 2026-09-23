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
        'mode' => env('LIMEN_AI_THEME_MODE', 'light'),
        'allow_mode_toggle' => env('LIMEN_AI_THEME_TOGGLE', false),
        'overrides' => [
            'primary' => '#2563EB',
        ],
    ],
],
```

## Sounds & animations

Configure in `config/limen-ai.php` under `ui.sounds` and `ui.animations`, or via env:

| Variable | Default | Purpose |
|----------|---------|---------|
| `LIMEN_AI_UI_SOUNDS_ENABLED` | `true` | Master sound toggle |
| `LIMEN_AI_UI_SOUND_VOLUME` | `0.35` | Volume (0–1) |
| `LIMEN_AI_UI_SOUND_SEND` | `true` | Play on message send |
| `LIMEN_AI_UI_SOUND_RECEIVE` | `true` | Play on assistant reply |
| `LIMEN_AI_UI_SOUND_OPEN` | `true` | Play when widget opens |
| `LIMEN_AI_UI_SOUND_NOTIFICATION` | `true` | Play on approval/errors |
| `LIMEN_AI_UI_ANIMATIONS_ENABLED` | `true` | Master animation toggle |
| `LIMEN_AI_UI_ANIMATION_MS` | `280` | Transition duration |
| `LIMEN_AI_UI_TYPING_INDICATOR` | `true` | Animated typing dots |
| `LIMEN_AI_UI_LAUNCHER_PULSE` | `true` | Launcher pulse ring |
| `LIMEN_AI_UI_UNREAD_BADGE` | `true` | Badge when widget closed |
| `LIMEN_AI_UI_TIMESTAMPS` | `true` | Per-message timestamps |
| `LIMEN_AI_UI_AVATARS` | `true` | Assistant/system avatars |

Sounds use the Web Audio API (no external files). Animations respect `prefers-reduced-motion`.

## Environment Variables

| Variable | Purpose |
|----------|---------|
| `LIMEN_AI_THEME_PRESET` | Preset name (`default`, `arabic`, custom) |
| `LIMEN_AI_THEME_MODE` | `light`, `dark`, or `auto` |
| `LIMEN_AI_THEME_TOGGLE` | Show in-widget light/dark toggle |
| `LIMEN_AI_UI_DIRECTION` | Layout direction: `ltr` or `rtl` (independent of chat language) |
| `LIMEN_AI_UI_POSITION` | Widget position: `bottom-right` or `bottom-left` |

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
| `--limen-ai-shadow` | Panel elevation |
| `--limen-ai-anim-duration` | Animation timing |

Dark/light switching uses `data-mode="light|dark"` on `.limen-ai-chat`. RTL uses `data-direction="rtl"`.

### Theme tokens for copy

| Key | Purpose |
|-----|---------|
| `title` | Header title |
| `subtitle` | Header status line (e.g. "Typically replies in a few seconds") |
| `welcome_message` | First system message |
| `avatar_url` | Custom bot avatar image |
| `position` | Widget position (`bottom-right`, `bottom-left`) |

## Arabic / RTL Preset

Set `LIMEN_AI_THEME_PRESET=arabic` for RTL layout, Arabic placeholders, and a font stack suitable for Arabic script.

**Direction vs language:** Switching chat language (e.g. asking the agent to reply in Arabic) does **not** change layout direction or widget position. Control those explicitly with `LIMEN_AI_UI_DIRECTION` and `LIMEN_AI_UI_POSITION` in your host `.env`.
