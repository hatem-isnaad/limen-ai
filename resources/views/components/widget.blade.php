@props([
    'conversationId' => null,
    'agent' => config('limen-ai.default_agent', 'example'),
    'theme' => [],
])

@php
    $resolvedTheme = app(\LimenAi\Ui\ThemeResolver::class)->resolve(is_array($theme) ? $theme : []);
    $launcherLabel = config('limen-ai.ui.widget.launcher_label') ?: ($resolvedTheme->direction() === 'rtl' ? 'محادثة' : 'Chat');
@endphp

<div
    {{ $attributes->class(['limen-ai-widget']) }}
    data-limen-ai-widget
    data-open="false"
    data-position="{{ $resolvedTheme->get('position', 'bottom-right') }}"
    data-direction="{{ $resolvedTheme->direction() }}"
    style="{{ $resolvedTheme->cssVariables() }};"
    @include('limen-ai::partials.ui-config')
>
    <div class="limen-ai-widget__panel" data-limen-ai-panel>
        <x-limen-ai::chatbot
            variant="embedded"
            :conversation-id="$conversationId"
            :agent="$agent"
            :theme="$theme"
        />
    </div>
    <button type="button" class="limen-ai-widget__launcher" data-limen-ai-launcher aria-label="{{ $launcherLabel }}">
        <span class="limen-ai-widget__launcher-icon" aria-hidden="true">
            <svg class="limen-ai-widget__icon-open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75">
                <path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4Z"/>
            </svg>
            <svg class="limen-ai-widget__icon-close" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75">
                <path d="M18 6 6 18M6 6l12 12"/>
            </svg>
        </span>
        @if ($launcherLabel)
            <span class="limen-ai-widget__launcher-text">{{ $launcherLabel }}</span>
        @endif
        <span class="limen-ai-widget__badge" data-limen-ai-unread hidden>0</span>
    </button>
</div>

@stack('limen-ai-assets')
