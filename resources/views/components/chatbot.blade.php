@props([
    'conversationId' => null,
    'agent' => config('limen-ai.default_agent', 'example'),
    'theme' => [],
    'variant' => 'standalone',
])

@php
    $resolvedTheme = app(\LimenAi\Ui\ThemeResolver::class)->resolve(is_array($theme) ? $theme : []);
    $isRtl = $resolvedTheme->direction() === 'rtl';
    $isEmbedded = $variant === 'embedded';
    $placeholder = $isRtl ? 'اكتب رسالتك...' : 'Type your message...';
    $sendLabel = $isRtl ? 'إرسال' : 'Send';
    $subtitle = $resolvedTheme->get('subtitle', $isRtl ? 'عادةً ما يرد خلال ثوانٍ' : 'Typically replies in a few seconds');
    $avatarUrl = $resolvedTheme->get('avatar_url');
@endphp

<div
    {{ $attributes->class(['limen-ai-chat', $isEmbedded ? 'limen-ai-chat--embedded' : '']) }}
    data-limen-ai-chat
    data-variant="{{ $variant }}"
    data-agent="{{ $agent }}"
    data-conversation-id="{{ $conversationId }}"
    data-api-base="{{ url(config('limen-ai.ui.route_prefix', 'limen-ai')) }}"
    data-channel-prefix="{{ config('limen-ai.broadcasting.channel_prefix', 'limen-ai.conversation') }}"
    @include('limen-ai::partials.theme-attributes', ['theme' => $resolvedTheme])
    @include('limen-ai::partials.ui-config')
>
    <header class="limen-ai-chat__header">
        <div class="limen-ai-chat__header-main">
            @if ($avatarUrl)
                <img class="limen-ai-chat__avatar limen-ai-chat__avatar--img" src="{{ $avatarUrl }}" alt="" />
            @else
                <span class="limen-ai-chat__avatar" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75">
                        <path d="M12 3a5 5 0 0 1 5 5v1a4 4 0 0 1 4 4v1.5a1.5 1.5 0 0 1-1.5 1.5H17a3 3 0 0 1-6 0H4.5A1.5 1.5 0 0 1 3 17.5V13a4 4 0 0 1 4-4V8a5 5 0 0 1 5-5Z"/>
                        <circle cx="9" cy="12" r="1" fill="currentColor" stroke="none"/>
                        <circle cx="15" cy="12" r="1" fill="currentColor" stroke="none"/>
                    </svg>
                </span>
            @endif
            <div class="limen-ai-chat__header-text">
                <div class="limen-ai-chat__title-row">
                    <span class="limen-ai-chat__title">{{ $resolvedTheme->get('title', 'Limen AI Assistant') }}</span>
                    <span class="limen-ai-chat__presence" data-limen-ai-presence title="{{ $isRtl ? 'متصل' : 'Online' }}"></span>
                </div>
                @if ($subtitle)
                    <span class="limen-ai-chat__subtitle" data-limen-ai-subtitle>{{ $subtitle }}</span>
                @endif
            </div>
        </div>
        <div class="limen-ai-chat__header-actions">
            @if ($resolvedTheme->allowsModeToggle())
                <button type="button" class="limen-ai-chat__icon-btn" data-limen-ai-mode-toggle aria-label="{{ $isRtl ? 'تبديل المظهر' : 'Toggle theme' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M12 3a9 9 0 1 0 9 9 4.5 4.5 0 0 1-5-5 4.5 4.5 0 0 1-5-5Z"/></svg>
                </button>
            @endif
            @if ($isEmbedded && config('limen-ai.ui.widget.show_header_controls', true))
                <button type="button" class="limen-ai-chat__icon-btn" data-limen-ai-minimize aria-label="{{ $isRtl ? 'تصغير' : 'Minimize' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M5 12h14"/></svg>
                </button>
                <button type="button" class="limen-ai-chat__icon-btn" data-limen-ai-close aria-label="{{ $isRtl ? 'إغلاق' : 'Close' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </button>
            @endif
        </div>
    </header>

    <div class="limen-ai-chat__body">
        <div class="limen-ai-chat__messages" data-limen-ai-messages role="log" aria-live="polite" aria-relevant="additions"></div>
        <div class="limen-ai-chat__typing" data-limen-ai-typing hidden aria-hidden="true">
            <span class="limen-ai-chat__typing-dots"><span></span><span></span><span></span></span>
            <span class="limen-ai-chat__typing-label">{{ $isRtl ? 'يكتب...' : 'Typing...' }}</span>
        </div>
    </div>

    <div class="limen-ai-chat__status" data-limen-ai-status aria-live="polite"></div>

    <div class="limen-ai-chat__approval" data-limen-ai-approval hidden>
        <div class="limen-ai-chat__approval-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/></svg>
        </div>
        <div class="limen-ai-chat__approval-copy">
            <strong data-limen-ai-approval-label>{{ $isRtl ? 'الموافقة مطلوبة' : 'Approval required' }}</strong>
            <p>{{ $isRtl ? 'يحتاج الوكيل موافقتك قبل تنفيذ هذا الإجراء.' : 'The agent needs your approval before running this action.' }}</p>
        </div>
        <div class="limen-ai-chat__approval-actions">
            <button type="button" class="limen-ai-chat__button limen-ai-chat__button--primary" data-limen-ai-approve>{{ $isRtl ? 'موافقة' : 'Approve' }}</button>
            <button type="button" class="limen-ai-chat__button limen-ai-chat__button--ghost" data-limen-ai-reject>{{ $isRtl ? 'رفض' : 'Reject' }}</button>
        </div>
    </div>

    <footer class="limen-ai-chat__composer">
        <div class="limen-ai-chat__composer-inner">
            <textarea
                class="limen-ai-chat__input"
                data-limen-ai-input
                rows="1"
                placeholder="{{ $placeholder }}"
                aria-label="{{ $isRtl ? 'رسالة' : 'Message' }}"
                maxlength="{{ config('limen-ai.ui.composer.max_length', 4000) }}"
            ></textarea>
            <button type="button" class="limen-ai-chat__send" data-limen-ai-send aria-label="{{ $sendLabel }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="m22 2-7 20-4-9-9-4 20-7Z"/><path d="M22 2 11 13"/></svg>
            </button>
        </div>
        <div class="limen-ai-chat__composer-meta" data-limen-ai-char-count hidden></div>
    </footer>
</div>

@once
    @push('limen-ai-assets')
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
        <style>{!! \LimenAi\Support\UiAssets::css() !!}</style>
        <script>{!! \LimenAi\Support\UiAssets::js() !!}</script>
    @endpush
@endonce
