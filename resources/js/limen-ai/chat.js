(function () {
    class LimenAiSoundPlayer {
        constructor(config) {
            this.enabled = config?.enabled !== false;
            this.volume = typeof config?.volume === 'number' ? config.volume : 0.35;
            this.onSend = config?.on_send !== false;
            this.onReceive = config?.on_receive !== false;
            this.onOpen = config?.on_open !== false;
            this.onNotification = config?.on_notification !== false;
            this.context = null;
        }

        ensureContext() {
            if (!this.enabled || typeof window === 'undefined') {
                return null;
            }

            const AudioContext = window.AudioContext || window.webkitAudioContext;
            if (!AudioContext) {
                return null;
            }

            if (!this.context) {
                this.context = new AudioContext();
            }

            if (this.context.state === 'suspended') {
                this.context.resume().catch(() => {});
            }

            return this.context;
        }

        play(type) {
            const map = {
                send: this.onSend,
                receive: this.onReceive,
                open: this.onOpen,
                notification: this.onNotification,
            };

            if (!map[type]) {
                return;
            }

            const ctx = this.ensureContext();
            if (!ctx) {
                return;
            }

            const tones = {
                send: { frequency: 520, duration: 0.06 },
                receive: { frequency: 640, duration: 0.09 },
                open: { frequency: 440, duration: 0.12 },
                notification: { frequency: 760, duration: 0.14 },
            };

            const tone = tones[type] || tones.notification;
            const oscillator = ctx.createOscillator();
            const gain = ctx.createGain();

            oscillator.type = 'sine';
            oscillator.frequency.value = tone.frequency;
            gain.gain.value = this.volume;

            oscillator.connect(gain);
            gain.connect(ctx.destination);

            const now = ctx.currentTime;
            gain.gain.setValueAtTime(0, now);
            gain.gain.linearRampToValueAtTime(this.volume, now + 0.01);
            gain.gain.exponentialRampToValueAtTime(0.001, now + tone.duration);

            oscillator.start(now);
            oscillator.stop(now + tone.duration + 0.02);
        }
    }

    class LimenAiChat {
        constructor(root) {
            this.root = root;
            this.config = this.parseConfig(root.dataset.uiConfig);
            this.apiBase = root.dataset.apiBase || '/limen-ai';
            this.agent = root.dataset.agent || 'example';
            this.conversationId = root.dataset.conversationId || null;
            this.channelPrefix = root.dataset.channelPrefix || 'limen-ai.conversation';
            this.messagesEl = root.querySelector('[data-limen-ai-messages]');
            this.statusEl = root.querySelector('[data-limen-ai-status]');
            this.inputEl = root.querySelector('[data-limen-ai-input]');
            this.sendButton = root.querySelector('[data-limen-ai-send]');
            this.approvalEl = root.querySelector('[data-limen-ai-approval]');
            this.typingEl = root.querySelector('[data-limen-ai-typing]');
            this.charCountEl = root.querySelector('[data-limen-ai-char-count]');
            this.subtitleEl = root.querySelector('[data-limen-ai-subtitle]');
            this.pendingRunId = null;
            this.pendingApprovalId = null;
            this.echoChannel = null;
            this.isThinking = false;
            this.unreadCount = 0;
            this.widgetRoot = root.closest('[data-limen-ai-widget]');
            this.sounds = new LimenAiSoundPlayer(this.config.sounds || {});
            this.authenticated = root.dataset.authenticated === 'true';
            this.userId = root.dataset.userId || null;
            this.historyEnabled = root.dataset.historyEnabled === 'true' || this.config.history?.enabled !== false;
            this.guestEnabled = root.dataset.guestEnabled === 'true' || this.config.guest?.enabled === true;
            this.guestToken = null;
            this.historyEl = root.querySelector('[data-limen-ai-history]');
            this.historyBackdropEl = root.querySelector('[data-limen-ai-history-backdrop]');
            this.historyListEl = root.querySelector('[data-limen-ai-history-list]');
            this.historyEmptyEl = root.querySelector('[data-limen-ai-history-empty]');
            this.guestEl = root.querySelector('[data-limen-ai-guest]');
            this.guestFormEl = root.querySelector('[data-limen-ai-guest-form]');
            this.guestErrorEl = root.querySelector('[data-limen-ai-guest-error]');
            this.conversations = [];
            this.initialized = false;
            this.initPromise = null;
            this.locale = this.currentLocale();

            this.applyUiPreferences();
            this.applyUiLanguage(this.locale, { persist: false });
            this.bindEvents();
            this.initializeTheme();

            if (this.shouldDeferInitialization()) {
                return;
            }

            this.initPromise = this.initialize();
        }

        parseConfig(raw) {
            if (!raw) {
                return {};
            }

            try {
                return JSON.parse(raw);
            } catch (error) {
                return {};
            }
        }

        applyUiPreferences() {
            const animations = this.config.animations || {};
            const duration = animations.duration_ms || 280;

            this.root.style.setProperty('--limen-ai-anim-duration', `${animations.enabled === false ? 0 : duration}ms`);
            this.root.dataset.animations = animations.enabled === false ? 'false' : 'true';

            if (this.widgetRoot) {
                this.widgetRoot.style.setProperty('--limen-ai-anim-duration', `${animations.enabled === false ? 0 : duration}ms`);
                this.widgetRoot.dataset.animations = animations.enabled === false ? 'false' : 'true';
                this.widgetRoot.dataset.launcherPulse = animations.launcher_pulse === false ? 'false' : 'true';
            }

            if (this.config.composer?.show_char_count && this.charCountEl) {
                this.charCountEl.hidden = false;
            }
        }

        bindEvents() {
            this.sendButton?.addEventListener('click', () => this.sendMessage());

            this.inputEl?.addEventListener('keydown', (event) => {
                if (event.key === 'Enter' && !event.shiftKey) {
                    event.preventDefault();
                    this.sendMessage();
                }
            });

            this.inputEl?.addEventListener('input', () => {
                this.autoResizeInput();
                this.updateCharCount();
            });

            this.approvalEl?.querySelector('[data-limen-ai-approve]')?.addEventListener('click', () => this.resolveApproval(true));
            this.approvalEl?.querySelector('[data-limen-ai-reject]')?.addEventListener('click', () => this.resolveApproval(false));

            this.root.querySelector('[data-limen-ai-mode-toggle]')?.addEventListener('click', () => {
                const next = this.root.dataset.mode === 'dark' ? 'light' : 'dark';
                window.localStorage.setItem('limen-ai-theme-mode', next);
                this.applyThemeMode(next);
            });

            this.root.querySelector('[data-limen-ai-close]')?.addEventListener('click', () => this.closeWidget());
            this.root.querySelector('[data-limen-ai-minimize]')?.addEventListener('click', () => this.closeWidget());

            if (this.config.widget?.close_on_escape !== false) {
                document.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape' && this.widgetRoot?.dataset.open === 'true') {
                        this.closeWidget();
                    }
                });
            }

            this.root.querySelector('[data-limen-ai-history-toggle]')?.addEventListener('click', () => {
                this.toggleHistoryPanel();
            });

            this.root.querySelector('[data-limen-ai-history-close]')?.addEventListener('click', () => {
                this.closeHistoryPanel();
            });

            this.historyBackdropEl?.addEventListener('click', () => {
                this.closeHistoryPanel();
            });

            this.root.querySelector('[data-limen-ai-new-conversation]')?.addEventListener('click', () => {
                this.startNewConversation();
            });

            this.guestFormEl?.addEventListener('submit', (event) => {
                event.preventDefault();
                this.submitGuestForm();
            });
        }

        initializeTheme() {
            const configuredMode = this.root.dataset.configuredMode || this.root.dataset.mode || 'light';
            const storageKey = 'limen-ai-theme-mode';
            let mode = configuredMode;

            if (configuredMode === 'auto') {
                mode = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            } else if (this.root.dataset.allowModeToggle === 'true') {
                mode = window.localStorage.getItem(storageKey) || configuredMode;
            }

            this.applyThemeMode(mode === 'auto' ? 'light' : mode);

            if (configuredMode === 'auto') {
                window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (event) => {
                    this.applyThemeMode(event.matches ? 'dark' : 'light');
                });
            }
        }

        applyThemeMode(mode) {
            this.root.dataset.mode = mode;
            if (this.widgetRoot) {
                this.widgetRoot.dataset.mode = mode;
            }
        }

        resumeEnabled() {
            return this.config.history?.resume_last_conversation !== false;
        }

        shouldDeferInitialization() {
            if (this.root.dataset.conversationId) {
                return false;
            }

            if (!this.widgetRoot || this.root.dataset.variant !== 'embedded') {
                return false;
            }

            return this.config.history?.defer_until_open !== false;
        }

        sessionScopeKey() {
            if (this.authenticated && this.userId) {
                return `user:${this.userId}`;
            }

            if (this.guestToken) {
                return `guest:${this.guestToken}`;
            }

            return 'anonymous';
        }

        guestStorageKey() {
            return this.config.guest?.storage_key || 'limen-ai-guest-token';
        }

        conversationStorageKey() {
            const base = this.config.history?.storage_key
                || this.config.guest?.conversation_storage_key
                || 'limen-ai-active-conversation';

            return `${base}:${this.agent}:${this.sessionScopeKey()}`;
        }

        localeStorageKey() {
            return this.config.i18n?.storage_key || 'limen-ai-locale';
        }

        normalizeLocale(locale) {
            if (!locale || typeof locale !== 'string') {
                return null;
            }

            const primary = locale.toLowerCase().split('-')[0];
            const supported = this.config.i18n?.supported || ['en', 'ar'];

            return supported.includes(primary) ? primary : null;
        }

        currentLocale() {
            const stored = window.localStorage.getItem(this.localeStorageKey());
            const fromStorage = this.normalizeLocale(stored);
            if (fromStorage) {
                return fromStorage;
            }

            const fromDocument = this.normalizeLocale(document.documentElement.lang);
            if (fromDocument) {
                return fromDocument;
            }

            return this.normalizeLocale(this.config.i18n?.default_locale) || 'en';
        }

        detectLocaleFromMessage(message) {
            if (!message) {
                return null;
            }

            const lower = message.toLowerCase();

            if (/\b(talk|speak|reply|respond|write|chat)\s+(to\s+me\s+)?in\s+(arabic|english)\b/.test(lower)) {
                return lower.includes('arabic') ? 'ar' : 'en';
            }

            if (/\b(use|switch\s+to)\s+(arabic|english)\b/.test(lower)) {
                return lower.includes('arabic') ? 'ar' : 'en';
            }

            if (/(?:بالعربية|بالعربي|عربي|تحدث\s+بالعربية|تكلم\s+عربي)/u.test(message)) {
                return 'ar';
            }

            if (/(?:بالانجليزية|بالإنجليزية|انجليزي|إنجليزي|in\s+english)/iu.test(message)) {
                return 'en';
            }

            if (/[\u0600-\u06FF\u0750-\u077F\u08A0-\u08FF]/.test(message)) {
                return 'ar';
            }

            if (/\b[a-z]{2,}\b/i.test(message)) {
                return 'en';
            }

            return null;
        }

        i18nLabel(key) {
            const labels = this.config.i18n?.labels?.[this.locale]
                || this.config.i18n?.labels?.en
                || {};

            return labels[key] || '';
        }

        applyUiLanguage(locale, options = {}) {
            const normalized = this.normalizeLocale(locale) || 'en';
            const shouldPersist = options.persist !== false;

            this.locale = normalized;

            if (shouldPersist) {
                window.localStorage.setItem(this.localeStorageKey(), normalized);
            }

            if (this.inputEl) {
                const placeholder = this.i18nLabel('placeholder');
                if (placeholder) {
                    this.inputEl.placeholder = placeholder;
                }
            }

            const typingLabel = this.root.querySelector('.limen-ai-chat__typing-label');
            if (typingLabel) {
                const label = this.i18nLabel('typing');
                if (label) {
                    typingLabel.textContent = label;
                }
            }

            const historyTitle = this.root.querySelector('.limen-ai-chat__history-heading strong');
            if (historyTitle) {
                const label = this.i18nLabel('history');
                if (label) {
                    historyTitle.textContent = label;
                }
            }

            const newChatLabel = this.root.querySelector('[data-limen-ai-new-conversation] span');
            if (newChatLabel) {
                const label = this.i18nLabel('new_chat');
                if (label) {
                    newChatLabel.textContent = label;
                }
            }

            const guestTitle = this.root.querySelector('.limen-ai-chat__guest-title');
            if (guestTitle) {
                const label = this.i18nLabel('guest_title');
                if (label) {
                    guestTitle.textContent = label;
                }
            }

            const guestCopy = this.root.querySelector('.limen-ai-chat__guest-copy');
            if (guestCopy) {
                const label = this.i18nLabel('guest_copy');
                if (label) {
                    guestCopy.textContent = label;
                }
            }

            const guestSubmit = this.guestFormEl?.querySelector('button[type="submit"]');
            if (guestSubmit) {
                const label = this.i18nLabel('guest_continue');
                if (label) {
                    guestSubmit.textContent = label;
                }
            }
        }

        restoreConversationIdFromStorage() {
            if (!this.resumeEnabled() || this.root.dataset.conversationId) {
                return null;
            }

            return window.localStorage.getItem(this.conversationStorageKey());
        }

        async ensureReady() {
            if (this.initialized) {
                return;
            }

            if (!this.initPromise) {
                this.initPromise = this.initialize();
            }

            await this.initPromise;
        }

        restoreGuestToken() {
            if (this.authenticated || !this.guestEnabled) {
                return;
            }

            const stored = window.localStorage.getItem(this.guestStorageKey());
            if (stored) {
                this.guestToken = stored;
            }
        }

        persistGuestToken(token) {
            if (!token) {
                return;
            }

            this.guestToken = token;
            window.localStorage.setItem(this.guestStorageKey(), token);
        }

        persistConversationId(conversationId) {
            if (!conversationId) {
                return;
            }

            window.localStorage.setItem(this.conversationStorageKey(), conversationId);
        }

        clearPersistedConversationId() {
            window.localStorage.removeItem(this.conversationStorageKey());
        }

        async initialize() {
            this.restoreGuestToken();

            if (!this.authenticated && this.guestEnabled && !this.guestToken) {
                this.showGuestForm();
                return;
            }

            await this.bootstrapConversation();
        }

        async bootstrapConversation() {
            if (!this.conversationId) {
                const storedConversationId = this.restoreConversationIdFromStorage();
                if (storedConversationId) {
                    this.conversationId = storedConversationId;
                    this.root.dataset.conversationId = storedConversationId;
                }
            }

            if (this.historyEnabled) {
                await this.loadHistoryList();
            }

            if (!this.conversationId) {
                await this.createConversation();
            } else {
                try {
                    await this.loadConversation();
                } catch (error) {
                    this.conversationId = null;
                    this.clearPersistedConversationId();
                    await this.createConversation();
                }
            }

            this.subscribeToRealtime();
            this.autoResizeInput();
            this.initialized = true;
        }

        showGuestForm() {
            if (!this.guestEl) {
                return;
            }

            this.guestEl.hidden = false;
            this.setComposerDisabled(true);
        }

        hideGuestForm() {
            if (this.guestEl) {
                this.guestEl.hidden = true;
            }

            if (this.guestErrorEl) {
                this.guestErrorEl.hidden = true;
                this.guestErrorEl.textContent = '';
            }

            this.setComposerDisabled(false);
        }

        async submitGuestForm() {
            if (!this.guestFormEl) {
                return;
            }

            const profile = {};
            this.guestFormEl.querySelectorAll('input[name]').forEach((input) => {
                profile[input.name] = input.value.trim();
            });

            try {
                const response = await this.request('POST', `${this.apiBase}/guest/session`, {
                    profile,
                });

                this.persistGuestToken(response.guest_token);
                this.hideGuestForm();
                await this.bootstrapConversation();
                this.initialized = true;
            } catch (error) {
                if (this.guestErrorEl) {
                    this.guestErrorEl.hidden = false;
                    this.guestErrorEl.textContent = error.message || 'Unable to start guest session.';
                }
            }
        }

        async createConversation() {
            const response = await this.request('POST', `${this.apiBase}/conversations`, {
                agent: this.agent,
            });

            this.conversationId = response.conversation.id;
            this.root.dataset.conversationId = this.conversationId;
            this.persistConversationId(this.conversationId);

            if (response.guest_token) {
                this.persistGuestToken(response.guest_token);
            }

            this.clearMessages();
            this.renderWelcome();

            if (this.historyEnabled) {
                await this.loadHistoryList();
            }
        }

        async loadHistoryList() {
            if (!this.historyEnabled) {
                return;
            }

            try {
                const response = await this.request('GET', `${this.apiBase}/conversations?agent=${encodeURIComponent(this.agent)}`);
                this.conversations = response.conversations || [];
                this.renderHistoryList();
            } catch (error) {
                this.conversations = [];
                this.renderHistoryList();
            }
        }

        renderHistoryList() {
            if (!this.historyListEl) {
                return;
            }

            this.historyListEl.innerHTML = '';

            if (this.historyEmptyEl) {
                this.historyEmptyEl.hidden = this.conversations.length > 0;
            }

            this.conversations.forEach((conversation) => {
                const item = document.createElement('button');
                item.type = 'button';
                item.className = 'limen-ai-chat__history-item';
                item.dataset.conversationId = conversation.id;
                item.setAttribute('role', 'listitem');

                if (conversation.id === this.conversationId) {
                    item.classList.add('is-active');
                }

                const title = document.createElement('span');
                title.className = 'limen-ai-chat__history-title';
                title.textContent = conversation.title || 'Conversation';

                const preview = document.createElement('span');
                preview.className = 'limen-ai-chat__history-preview';
                preview.textContent = conversation.preview || '';

                item.appendChild(title);
                if (this.config.history?.show_preview !== false && conversation.preview) {
                    item.appendChild(preview);
                }

                item.addEventListener('click', () => {
                    this.openConversation(conversation.id);
                });

                this.historyListEl.appendChild(item);
            });
        }

        setHistoryOpen(open) {
            this.root.dataset.historyOpen = open ? 'true' : 'false';

            if (this.historyEl) {
                this.historyEl.hidden = !open;
            }

            if (this.historyBackdropEl) {
                this.historyBackdropEl.hidden = !open;
            }

            this.root.querySelector('[data-limen-ai-history-toggle]')?.classList.toggle('is-active', open);

            if (open) {
                this.loadHistoryList();
            }
        }

        toggleHistoryPanel() {
            if (!this.historyEl) {
                return;
            }

            this.setHistoryOpen(this.root.dataset.historyOpen !== 'true');
        }

        closeHistoryPanel() {
            this.setHistoryOpen(false);
        }

        async openConversation(conversationId) {
            if (!conversationId || conversationId === this.conversationId) {
                this.closeHistoryPanel();
                return;
            }

            this.leaveRealtimeChannel();
            this.conversationId = conversationId;
            this.root.dataset.conversationId = conversationId;
            this.persistConversationId(conversationId);
            await this.loadConversation();
            this.subscribeToRealtime();
            this.renderHistoryList();
            this.closeHistoryPanel();
            this.resetUnreadBadge();
        }

        async startNewConversation() {
            this.leaveRealtimeChannel();
            this.conversationId = null;
            this.root.dataset.conversationId = '';
            this.clearPersistedConversationId();
            await this.createConversation();
            this.subscribeToRealtime();
            this.closeHistoryPanel();
        }

        leaveRealtimeChannel() {
            if (!this.echoChannel || !window.Echo) {
                this.echoChannel = null;
                return;
            }

            const channelName = `${this.channelPrefix}.${this.conversationId}`;
            window.Echo.leave(channelName);
            this.echoChannel = null;
        }

        async loadConversation() {
            const response = await this.request('GET', `${this.apiBase}/conversations/${this.conversationId}`);
            this.clearMessages();

            const preferredLanguage = response.conversation?.preferred_language;
            if (preferredLanguage) {
                this.applyUiLanguage(preferredLanguage);
            }

            response.messages.forEach((message) => {
                if (!['user', 'assistant', 'system'].includes(message.role)) {
                    return;
                }

                this.appendMessage(message.role, message.content, { animate: false });
            });

            if (response.messages.length === 0) {
                this.renderWelcome();
            }
        }

        renderWelcome() {
            const welcome = this.root.dataset.welcomeMessage;
            if (welcome) {
                this.appendMessage('system', welcome);
            }
        }

        subscribeToRealtime() {
            if (!window.Echo || !this.conversationId) {
                return;
            }

            if (this.echoChannel) {
                return;
            }

            const channelName = `${this.channelPrefix}.${this.conversationId}`;
            this.echoChannel = window.Echo.private(channelName);

            this.echoChannel.listen('AgentStarted', () => this.setThinking(true, 'Agent is thinking...'));
            this.echoChannel.listen('AgentCompleted', async (payload) => {
                this.pendingRunId = null;
                this.setThinking(false);
                this.setComposerDisabled(false);

                await this.loadConversation();

                if (payload.final_message) {
                    this.sounds.play('receive');
                    this.incrementUnreadIfClosed();
                }
            });
            this.echoChannel.listen('AgentFailed', (payload) => {
                this.pendingRunId = null;
                this.setThinking(false, payload.error || 'Agent failed.');
                this.setComposerDisabled(false);
                this.sounds.play('notification');
            });
            this.echoChannel.listen('ConversationUpdated', (payload) => {
                if (payload.state === 'waiting_approval') {
                    this.setThinking(false, 'Approval required.');
                }
            });
            this.echoChannel.listen('ApprovalRequested', (payload) => {
                this.pendingApprovalId = payload.approval_id;
                this.showApproval(payload.tool_key || 'action');
                this.sounds.play('notification');
            });
        }

        async sendMessage() {
            await this.ensureReady();

            const message = this.inputEl?.value?.trim();
            if (!message || !this.conversationId) {
                return;
            }

            this.persistConversationId(this.conversationId);

            const detectedLocale = this.detectLocaleFromMessage(message);
            if (detectedLocale) {
                this.applyUiLanguage(detectedLocale);
            }

            this.appendMessage('user', message);
            this.inputEl.value = '';
            this.autoResizeInput();
            this.updateCharCount();
            this.setComposerDisabled(true);
            this.setThinking(true, 'Sending...');
            this.sounds.play('send');

            const response = await this.request('POST', `${this.apiBase}/conversations/${this.conversationId}/messages`, {
                message,
                locale: this.locale,
            });

            if (response.locale) {
                this.applyUiLanguage(response.locale);
            }

            this.pendingRunId = response.run_id || null;

            if (response.queued) {
                this.setThinking(true, 'Queued...');
                return;
            }

            if (this.pendingRunId && !window.Echo) {
                await this.pollRun(this.pendingRunId);
            } else if (!window.Echo) {
                await this.loadConversation();
                this.setComposerDisabled(false);
                this.setThinking(false);
            } else {
                this.setThinking(true, 'Agent is thinking...');
            }
        }

        async pollRun(runId) {
            let attempts = 0;

            while (attempts < 30) {
                const response = await this.request('GET', `${this.apiBase}/runs/${runId}`);
                const run = response.run;

                if (run.terminal) {
                    this.pendingRunId = null;
                    this.setComposerDisabled(false);
                    this.setThinking(false);

                    if (run.status === 'completed' && run.final_message) {
                        await this.loadConversation();
                        this.sounds.play('receive');
                        this.incrementUnreadIfClosed();
                    } else if (run.status === 'waiting_approval') {
                        this.setStatus('Approval required.');
                    } else if (run.error) {
                        this.setStatus(run.error);
                        this.sounds.play('notification');
                    } else {
                        this.setStatus('');
                    }

                    return;
                }

                attempts += 1;
                await new Promise((resolve) => setTimeout(resolve, 500));
            }

            this.setComposerDisabled(false);
            this.setThinking(false, 'Timed out waiting for agent response.');
            this.sounds.play('notification');
        }

        async resolveApproval(approve) {
            if (!this.pendingApprovalId) {
                return;
            }

            const action = approve ? 'approve' : 'reject';
            await this.request('POST', `${this.apiBase}/approvals/${this.pendingApprovalId}/${action}`);
            this.hideApproval();
            this.pendingApprovalId = null;
            this.setStatus(approve ? 'Resuming...' : 'Approval rejected.');
            this.setThinking(approve, 'Resuming...');
        }

        showApproval(toolKey) {
            if (!this.approvalEl) {
                return;
            }

            this.approvalEl.hidden = false;
            const label = this.approvalEl.querySelector('[data-limen-ai-approval-label]');
            if (label) {
                label.textContent = `Approve ${toolKey}?`;
            }

            this.setComposerDisabled(true);
            this.setThinking(false);
        }

        hideApproval() {
            if (this.approvalEl) {
                this.approvalEl.hidden = true;
            }
        }

        appendMessage(role, content, options = {}) {
            if (!this.messagesEl) {
                return;
            }

            const animate = options.animate !== false && this.config.animations?.message_entrance !== false;
            const showAvatars = this.config.messages?.show_avatars !== false;
            const showTimestamps = this.config.messages?.show_timestamps !== false;

            const row = document.createElement('div');
            row.className = `limen-ai-chat__message-row limen-ai-chat__message-row--${role}`;
            if (!animate) {
                row.style.animation = 'none';
            }

            if (showAvatars && role !== 'user') {
                const avatar = document.createElement('span');
                avatar.className = 'limen-ai-chat__msg-avatar';
                avatar.setAttribute('aria-hidden', 'true');
                avatar.innerHTML = role === 'system'
                    ? '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M12 6v6l4 2"/></svg>'
                    : '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M12 3a5 5 0 0 1 5 5v1a4 4 0 0 1 4 4v1.5a1.5 1.5 0 0 1-1.5 1.5H17a3 3 0 0 1-6 0H4.5A1.5 1.5 0 0 1 3 17.5V13a4 4 0 0 1 4-4V8a5 5 0 0 1 5-5Z"/></svg>';
                row.appendChild(avatar);
            }

            const bubble = document.createElement('div');
            bubble.className = 'limen-ai-chat__message-bubble';

            const message = document.createElement('div');
            message.className = `limen-ai-chat__message limen-ai-chat__message--${role}`;
            message.textContent = content;
            bubble.appendChild(message);

            if (showTimestamps) {
                const meta = document.createElement('div');
                meta.className = 'limen-ai-chat__message-meta';
                meta.textContent = this.formatTimestamp(new Date());
                bubble.appendChild(meta);
            }

            row.appendChild(bubble);
            this.messagesEl.appendChild(row);
            this.messagesEl.scrollTop = this.messagesEl.scrollHeight;
        }

        formatTimestamp(date) {
            try {
                return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            } catch (error) {
                return '';
            }
        }

        clearMessages() {
            if (this.messagesEl) {
                this.messagesEl.innerHTML = '';
            }
        }

        setStatus(text) {
            if (this.statusEl) {
                this.statusEl.textContent = text || '';
            }
        }

        setThinking(active, statusText = '') {
            this.isThinking = active;

            if (this.config.animations?.typing_indicator !== false && this.typingEl) {
                this.typingEl.hidden = !active;
            }

            if (active && this.subtitleEl) {
                this.subtitleEl.textContent = statusText || this.i18nLabel('typing') || 'Typing...';
            } else if (this.subtitleEl) {
                const original = this.root.dataset.subtitleOriginal;
                if (original) {
                    this.subtitleEl.textContent = original;
                }
            }

            this.setStatus(active ? '' : statusText);
        }

        setComposerDisabled(disabled) {
            if (this.inputEl) {
                this.inputEl.disabled = disabled;
            }

            if (this.sendButton) {
                this.sendButton.disabled = disabled;
            }
        }

        autoResizeInput() {
            if (!this.inputEl) {
                return;
            }

            const maxRows = this.config.composer?.max_rows || 4;
            this.inputEl.style.height = 'auto';
            const lineHeight = 22;
            const maxHeight = lineHeight * maxRows;
            this.inputEl.style.height = `${Math.min(this.inputEl.scrollHeight, maxHeight)}px`;
        }

        updateCharCount() {
            if (!this.charCountEl || this.config.composer?.show_char_count !== true) {
                return;
            }

            const max = this.config.composer?.max_length || 4000;
            const length = this.inputEl?.value?.length || 0;
            this.charCountEl.textContent = `${length} / ${max}`;
        }

        closeWidget() {
            if (!this.widgetRoot) {
                return;
            }

            this.widgetRoot.dataset.open = 'false';
            this.resetUnreadBadge();
        }

        incrementUnreadIfClosed() {
            if (!this.widgetRoot || this.widgetRoot.dataset.open === 'true') {
                return;
            }

            if (this.config.widget?.show_unread_badge === false) {
                return;
            }

            const count = Number(this.widgetRoot.dataset.unread || 0) + 1;
            this.widgetRoot.dataset.unread = String(count);
            const badge = this.widgetRoot.querySelector('[data-limen-ai-unread]');
            if (!badge) {
                return;
            }

            badge.hidden = false;
            badge.textContent = String(count);
            this.sounds.play('notification');
        }

        resetUnreadBadge() {
            if (this.widgetRoot) {
                this.widgetRoot.dataset.unread = '0';
            }

            const badge = this.widgetRoot?.querySelector('[data-limen-ai-unread]');
            if (badge) {
                badge.hidden = true;
                badge.textContent = '0';
            }
        }

        async request(method, url, body) {
            const options = {
                method,
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            };

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            if (csrfToken) {
                options.headers['X-CSRF-TOKEN'] = csrfToken;
            }

            if (this.guestToken) {
                options.headers['X-Limen-Guest-Token'] = this.guestToken;
            }

            if (this.locale) {
                options.headers['X-Limen-Locale'] = this.locale;
            }

            if (body !== undefined) {
                options.body = JSON.stringify(body);
            }

            const response = await fetch(url, options);
            const data = await response.json().catch(() => ({}));

            if (!response.ok) {
                const message = data.message || `Request failed (${response.status})`;
                this.setThinking(false, message);
                this.setComposerDisabled(false);
                this.sounds.play('notification');
                throw new Error(message);
            }

            return data;
        }
    }

    function mountWidget(root, chat) {
        const launcher = root.querySelector('[data-limen-ai-launcher]');
        const chatRoot = root.querySelector('[data-limen-ai-chat]');
        const subtitle = chatRoot?.querySelector('[data-limen-ai-subtitle]');

        if (subtitle && !chatRoot.dataset.subtitleOriginal) {
            chatRoot.dataset.subtitleOriginal = subtitle.textContent || '';
        }

        launcher?.addEventListener('click', () => {
            const isOpen = root.dataset.open === 'true';
            root.dataset.open = isOpen ? 'false' : 'true';

            if (!isOpen) {
                chat?.ensureReady().catch(() => {});

                let soundConfig = {};
                try {
                    soundConfig = JSON.parse(root.dataset.uiConfig || '{}').sounds || {};
                } catch (error) {
                    soundConfig = {};
                }

                new LimenAiSoundPlayer(soundConfig).play('open');
                root.dataset.unread = '0';
                const badge = root.querySelector('[data-limen-ai-unread]');
                if (badge) {
                    badge.hidden = true;
                    badge.textContent = '0';
                }
            }
        });
    }

    document.querySelectorAll('[data-limen-ai-widget]').forEach((widgetRoot) => {
        const chatRoot = widgetRoot.querySelector('[data-limen-ai-chat]');
        const chat = chatRoot ? new LimenAiChat(chatRoot) : null;
        mountWidget(widgetRoot, chat);
    });

    document.querySelectorAll('[data-limen-ai-chat]').forEach((root) => {
        if (root.closest('[data-limen-ai-widget]')) {
            return;
        }

        new LimenAiChat(root);
    });
})();
