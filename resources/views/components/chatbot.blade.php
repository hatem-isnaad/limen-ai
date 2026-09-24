@props([
    'conversationId' => null,
    'agent' => config('limen-ai.default_agent', 'example'),
    'theme' => [],
])

@php($resolvedTheme = app(\LimenAi\Ui\ThemeResolver::class)->resolve(is_array($theme) ? $theme : []))

<div
    {{ $attributes->class(['limen-ai-chat']) }}
    data-limen-ai-chat
    data-agent="{{ $agent }}"
    data-conversation-id="{{ $conversationId }}"
    data-api-base="{{ url(config('limen-ai.ui.route_prefix', 'limen-ai')) }}"
    data-channel-prefix="{{ config('limen-ai.broadcasting.channel_prefix', 'limen-ai.conversation') }}"
    @include('limen-ai::partials.theme-attributes', ['theme' => $resolvedTheme])
>
    <div class="limen-ai-chat__header">
        <span class="limen-ai-chat__title">{{ $resolvedTheme->get('title', 'Limen AI Assistant') }}</span>
        @if ($resolvedTheme->allowsModeToggle())
            <button type="button" class="limen-ai-chat__mode-toggle" data-limen-ai-mode-toggle aria-label="Toggle theme">
                ◐
            </button>
        @endif
    </div>
    <div class="limen-ai-chat__messages" data-limen-ai-messages></div>
    <div class="limen-ai-chat__status" data-limen-ai-status aria-live="polite"></div>
    <div class="limen-ai-chat__approval" data-limen-ai-approval hidden>
        <div data-limen-ai-approval-label>{{ $resolvedTheme->direction() === 'rtl' ? 'الموافقة مطلوبة.' : 'Approval required.' }}</div>
        <div class="limen-ai-chat__approval-actions">
            <button type="button" class="limen-ai-chat__button" data-limen-ai-approve>{{ $resolvedTheme->direction() === 'rtl' ? 'موافقة' : 'Approve' }}</button>
            <button type="button" class="limen-ai-chat__button limen-ai-chat__button--secondary" data-limen-ai-reject>{{ $resolvedTheme->direction() === 'rtl' ? 'رفض' : 'Reject' }}</button>
        </div>
    </div>
    <div class="limen-ai-chat__composer">
        <textarea
            class="limen-ai-chat__input"
            data-limen-ai-input
            rows="2"
            placeholder="{{ $resolvedTheme->direction() === 'rtl' ? 'اكتب رسالة...' : 'Type a message...' }}"
            aria-label="{{ $resolvedTheme->direction() === 'rtl' ? 'رسالة' : 'Message' }}"
        ></textarea>
        <button type="button" class="limen-ai-chat__button" data-limen-ai-send>{{ $resolvedTheme->direction() === 'rtl' ? 'إرسال' : 'Send' }}</button>
    </div>
</div>

@once
    @push('limen-ai-assets')
        <style>{!! \LimenAi\Support\UiAssets::css() !!}</style>
        <script>{!! \LimenAi\Support\UiAssets::js() !!}</script>
    @endpush
@endonce
