@props([
    'conversationId' => null,
    'agent' => config('limen-ai.default_agent', 'example'),
])

@php($theme = config('limen-ai.ui.theme', []))

<div
    {{ $attributes->class(['limen-ai-widget']) }}
    data-limen-ai-widget
    data-open="false"
    data-position="{{ $theme['position'] ?? 'bottom-right' }}"
    style="--limen-ai-primary: {{ $theme['primary'] ?? '#4F46E5' }};"
>
    <div class="limen-ai-widget__panel">
        <x-limen-ai::chatbot :conversation-id="$conversationId" :agent="$agent" />
    </div>
    <button type="button" class="limen-ai-widget__launcher" data-limen-ai-launcher aria-label="Open chat">
        AI
    </button>
</div>

@stack('limen-ai-assets')
