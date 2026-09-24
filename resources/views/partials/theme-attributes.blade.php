@php
    /** @var \LimenAi\Ui\ResolvedTheme $theme */
@endphp

data-direction="{{ $theme->direction() }}"
data-mode="{{ $theme->mode() === 'auto' ? 'light' : $theme->mode() }}"
data-configured-mode="{{ $theme->mode() }}"
data-allow-mode-toggle="{{ $theme->allowsModeToggle() ? 'true' : 'false' }}"
data-welcome-message="{{ $theme->get('welcome_message', '') }}"
style="{{ $theme->layoutStyle() }}"
