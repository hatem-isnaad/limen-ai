@props([
    'conversationId' => null,
    'agent' => config('limen-ai.default_agent', 'example'),
])

@php($theme = config('limen-ai.ui.theme', []))

<div
    {{ $attributes->class(['limen-ai-chat']) }}
    data-limen-ai-chat
    data-agent="{{ $agent }}"
    data-conversation-id="{{ $conversationId }}"
    data-api-base="{{ url(config('limen-ai.ui.route_prefix', 'limen-ai')) }}"
    data-channel-prefix="{{ config('limen-ai.broadcasting.channel_prefix', 'limen-ai.conversation') }}"
    data-welcome-message="{{ $theme['welcome_message'] ?? '' }}"
    data-direction="{{ $theme['direction'] ?? 'ltr' }}"
    style="
        --limen-ai-primary: {{ $theme['primary'] ?? '#4F46E5' }};
        --limen-ai-background: {{ $theme['background'] ?? '#FFFFFF' }};
        --limen-ai-text: {{ $theme['text'] ?? '#111827' }};
        --limen-ai-radius: {{ $theme['radius'] ?? '12px' }};
    "
>
    <div class="limen-ai-chat__header">{{ $theme['title'] ?? 'Limen AI Assistant' }}</div>
    <div class="limen-ai-chat__messages" data-limen-ai-messages></div>
    <div class="limen-ai-chat__status" data-limen-ai-status aria-live="polite"></div>
    <div class="limen-ai-chat__approval" data-limen-ai-approval hidden>
        <div data-limen-ai-approval-label>Approval required.</div>
        <div class="limen-ai-chat__approval-actions">
            <button type="button" class="limen-ai-chat__button" data-limen-ai-approve>Approve</button>
            <button type="button" class="limen-ai-chat__button limen-ai-chat__button--secondary" data-limen-ai-reject>Reject</button>
        </div>
    </div>
    <div class="limen-ai-chat__composer">
        <textarea class="limen-ai-chat__input" data-limen-ai-input rows="2" placeholder="Type a message..." aria-label="Message"></textarea>
        <button type="button" class="limen-ai-chat__button" data-limen-ai-send>Send</button>
    </div>
</div>

@once
    @push('limen-ai-assets')
        <style>{!! \LimenAi\Support\UiAssets::css() !!}</style>
        <script>{!! \LimenAi\Support\UiAssets::js() !!}</script>
    @endpush
@endonce
