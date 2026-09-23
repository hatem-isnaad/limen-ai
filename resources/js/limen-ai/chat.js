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

            this.applyUiPreferences();
            this.bindEvents();
            this.initializeTheme();
            this.initialize();
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

        async initialize() {
            if (!this.conversationId) {
                await this.createConversation();
            } else {
                await this.loadConversation();
            }

            this.subscribeToRealtime();
            this.autoResizeInput();
        }

        async createConversation() {
            const response = await this.request('POST', `${this.apiBase}/conversations`, {
                agent: this.agent,
            });

            this.conversationId = response.conversation.id;
            this.root.dataset.conversationId = this.conversationId;
            this.renderWelcome();
            this.subscribeToRealtime();
        }

        async loadConversation() {
            const response = await this.request('GET', `${this.apiBase}/conversations/${this.conversationId}`);
            this.clearMessages();

            response.messages.forEach((message) => this.appendMessage(message.role, message.content, { animate: false }));

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
            if (!window.Echo || !this.conversationId || this.echoChannel) {
                return;
            }

            const channelName = `${this.channelPrefix}.${this.conversationId}`;
            this.echoChannel = window.Echo.private(channelName);

            this.echoChannel.listen('AgentStarted', () => this.setThinking(true, 'Agent is thinking...'));
            this.echoChannel.listen('AgentCompleted', (payload) => {
                this.pendingRunId = null;
                this.setThinking(false);
                this.setComposerDisabled(false);
                if (payload.final_message) {
                    this.appendMessage('assistant', payload.final_message);
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
            this.echoChannel.listen('MessageCreated', (payload) => {
                if (payload.role === 'assistant') {
                    this.loadConversation();
                }
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
            const message = this.inputEl?.value?.trim();
            if (!message || !this.conversationId) {
                return;
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
            });

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
                        this.appendMessage('assistant', run.final_message);
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
                this.subtitleEl.textContent = statusText || 'Typing...';
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

    function mountWidget(root) {
        const launcher = root.querySelector('[data-limen-ai-launcher]');
        const chat = root.querySelector('[data-limen-ai-chat]');
        const subtitle = chat?.querySelector('[data-limen-ai-subtitle]');

        if (subtitle && !chat.dataset.subtitleOriginal) {
            chat.dataset.subtitleOriginal = subtitle.textContent || '';
        }

        launcher?.addEventListener('click', () => {
            const isOpen = root.dataset.open === 'true';
            root.dataset.open = isOpen ? 'false' : 'true';

            if (!isOpen) {
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

    document.querySelectorAll('[data-limen-ai-chat]').forEach((root) => {
        new LimenAiChat(root);
    });

    document.querySelectorAll('[data-limen-ai-widget]').forEach((root) => {
        mountWidget(root);
    });
})();
