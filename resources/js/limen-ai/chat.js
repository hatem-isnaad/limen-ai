(function () {
    class LimenAiChat {
        constructor(root) {
            this.root = root;
            this.apiBase = root.dataset.apiBase || '/limen-ai';
            this.agent = root.dataset.agent || 'example';
            this.conversationId = root.dataset.conversationId || null;
            this.channelPrefix = root.dataset.channelPrefix || 'limen-ai.conversation';
            this.messagesEl = root.querySelector('[data-limen-ai-messages]');
            this.statusEl = root.querySelector('[data-limen-ai-status]');
            this.inputEl = root.querySelector('[data-limen-ai-input]');
            this.sendButton = root.querySelector('[data-limen-ai-send]');
            this.approvalEl = root.querySelector('[data-limen-ai-approval]');
            this.pendingRunId = null;
            this.pendingApprovalId = null;
            this.echoChannel = null;

            this.bindEvents();
            this.initialize();
        }

        bindEvents() {
            if (this.sendButton) {
                this.sendButton.addEventListener('click', () => this.sendMessage());
            }

            if (this.inputEl) {
                this.inputEl.addEventListener('keydown', (event) => {
                    if (event.key === 'Enter' && !event.shiftKey) {
                        event.preventDefault();
                        this.sendMessage();
                    }
                });
            }

            if (this.approvalEl) {
                this.approvalEl.querySelector('[data-limen-ai-approve]')?.addEventListener('click', () => this.resolveApproval(true));
                this.approvalEl.querySelector('[data-limen-ai-reject]')?.addEventListener('click', () => this.resolveApproval(false));
            }
        }

        async initialize() {
            if (!this.conversationId) {
                await this.createConversation();
            } else {
                await this.loadConversation();
            }

            this.subscribeToRealtime();
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

            response.messages.forEach((message) => this.appendMessage(message.role, message.content));

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

            this.echoChannel.listen('AgentStarted', (payload) => this.setStatus('Agent is thinking...'));
            this.echoChannel.listen('AgentCompleted', (payload) => {
                this.pendingRunId = null;
                this.setStatus('');
                this.setComposerDisabled(false);
                if (payload.final_message) {
                    this.appendMessage('assistant', payload.final_message);
                }
            });
            this.echoChannel.listen('AgentFailed', (payload) => {
                this.pendingRunId = null;
                this.setStatus(payload.error || 'Agent failed.');
                this.setComposerDisabled(false);
            });
            this.echoChannel.listen('MessageCreated', (payload) => {
                if (payload.role === 'assistant') {
                    this.loadConversation();
                }
            });
            this.echoChannel.listen('ConversationUpdated', (payload) => {
                if (payload.state === 'waiting_approval') {
                    this.setStatus('Approval required.');
                }
            });
            this.echoChannel.listen('ApprovalRequested', (payload) => {
                this.pendingApprovalId = payload.approval_id;
                this.showApproval(payload.tool_key || 'action');
            });
        }

        async sendMessage() {
            const message = this.inputEl?.value?.trim();
            if (!message || !this.conversationId) {
                return;
            }

            this.appendMessage('user', message);
            this.inputEl.value = '';
            this.setComposerDisabled(true);
            this.setStatus('Sending...');

            const response = await this.request('POST', `${this.apiBase}/conversations/${this.conversationId}/messages`, {
                message,
            });

            this.pendingRunId = response.run_id || null;

            if (response.queued) {
                this.setStatus('Queued...');
                return;
            }

            if (this.pendingRunId && !window.Echo) {
                await this.pollRun(this.pendingRunId);
            } else if (!window.Echo) {
                await this.loadConversation();
                this.setComposerDisabled(false);
                this.setStatus('');
            } else {
                this.setStatus('Agent is thinking...');
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

                    if (run.status === 'completed' && run.final_message) {
                        this.appendMessage('assistant', run.final_message);
                        this.setStatus('');
                    } else if (run.status === 'waiting_approval') {
                        this.setStatus('Approval required.');
                    } else if (run.error) {
                        this.setStatus(run.error);
                    } else {
                        this.setStatus('');
                    }

                    return;
                }

                attempts += 1;
                await new Promise((resolve) => setTimeout(resolve, 500));
            }

            this.setComposerDisabled(false);
            this.setStatus('Timed out waiting for agent response.');
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
        }

        hideApproval() {
            if (this.approvalEl) {
                this.approvalEl.hidden = true;
            }
        }

        appendMessage(role, content) {
            if (!this.messagesEl) {
                return;
            }

            const item = document.createElement('div');
            item.className = `limen-ai-chat__message limen-ai-chat__message--${role}`;
            item.textContent = content;
            this.messagesEl.appendChild(item);
            this.messagesEl.scrollTop = this.messagesEl.scrollHeight;
        }

        clearMessages() {
            if (this.messagesEl) {
                this.messagesEl.innerHTML = '';
            }
        }

        setStatus(text) {
            if (this.statusEl) {
                this.statusEl.textContent = text;
            }
        }

        setComposerDisabled(disabled) {
            if (this.inputEl) {
                this.inputEl.disabled = disabled;
            }

            if (this.sendButton) {
                this.sendButton.disabled = disabled;
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
                this.setStatus(message);
                this.setComposerDisabled(false);
                throw new Error(message);
            }

            return data;
        }
    }

    function mountWidget(root) {
        const launcher = root.querySelector('[data-limen-ai-launcher]');
        launcher?.addEventListener('click', () => {
            root.dataset.open = 'true';
        });
    }

    document.querySelectorAll('[data-limen-ai-chat]').forEach((root) => {
        new LimenAiChat(root);
    });

    document.querySelectorAll('[data-limen-ai-widget]').forEach((root) => {
        mountWidget(root);
    });
})();
