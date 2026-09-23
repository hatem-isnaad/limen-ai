@props([
    'conversationId' => null,
    'agent' => config('limen-ai.default_agent', 'example'),
    'theme' => [],
])

@php($resolvedTheme = app(\LimenAi\Ui\ThemeResolver::class)->resolve(is_array($theme) ? $theme : []))

<div
    {{ $attributes->class(['limen-ai-widget']) }}
    data-limen-ai-widget
    data-open="false"
    data-position="{{ $resolvedTheme->get('position', 'bottom-right') }}"
    data-direction="{{ $resolvedTheme->direction() }}"
    style="{{ $resolvedTheme->cssVariables() }};"
>
    <div class="limen-ai-widget__panel">
        <x-limen-ai::chatbot :conversation-id="$conversationId" :agent="$agent" :theme="$theme" />
    </div>
    <button type="button" class="limen-ai-widget__launcher" data-limen-ai-launcher aria-label="Open chat">
        AI
    </button>
</div>

@stack('limen-ai-assets')
