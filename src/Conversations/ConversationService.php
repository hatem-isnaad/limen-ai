<?php

namespace LimenAi\Conversations;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Events\Dispatcher;
use LimenAi\Contracts\Agents\AgentRepository;
use LimenAi\Contracts\Conversations\ConversationRepository;
use LimenAi\Contracts\Conversations\ConversationSummarizer;
use LimenAi\Contracts\Conversations\MessageRepository;
use LimenAi\Contracts\Runtime\RunContext;
use LimenAi\Events\ConversationUpdated;
use LimenAi\Events\MessageCreated;

class ConversationService
{
    public function __construct(
        private readonly ConversationRepository $conversations,
        private readonly MessageRepository $messages,
        private readonly MessageFormatter $formatter,
        private readonly ConversationSummarizer $summarizer,
        private readonly AgentRepository $agents,
        private readonly ConfigRepository $config,
        private readonly Dispatcher $events,
    ) {}

    public function ensure(string $conversationId, string $agentKey, RunContext $context): void
    {
        if ($this->conversations->find($conversationId) !== null) {
            return;
        }

        $this->conversations->create([
            'id' => $conversationId,
            'agent_key' => $agentKey,
            'user_id' => $context->userId(),
            'guest_token' => $context->guestToken(),
            'metadata' => $context->metadata(),
            'state' => ConversationState::ACTIVE,
        ]);

        $this->events->dispatch(new ConversationUpdated($conversationId, ConversationState::ACTIVE));
    }

    /** @return list<array<string, mixed>> */
    public function historyForAgent(string $conversationId, ?string $agentKey = null): array
    {
        $limit = $this->resolveHistoryLimit($agentKey);
        $stored = $this->messages->forConversation($conversationId, $limit);

        $summary = $this->summarizer->summarize($conversationId, $stored);

        if ($summary !== null && $summary !== '') {
            $messagesForAgent = $this->messagesWithSummaryApplied($stored);

            return array_merge(
                [['role' => 'system', 'content' => "Conversation summary:\n".$summary]],
                $this->formatter->toAgentMessages($messagesForAgent),
            );
        }

        return $this->formatter->toAgentMessages($stored);
    }

    public function appendUserMessage(string $conversationId, string $content): string
    {
        return $this->appendAgentMessage($conversationId, [
            'role' => 'user',
            'content' => $content,
        ]);
    }

    /**
     * @param  array<string, mixed>  $agentMessage
     */
    public function appendAgentMessage(string $conversationId, array $agentMessage): string
    {
        $payload = $this->formatter->fromAgentMessage($agentMessage);
        $messageId = $this->messages->create($conversationId, $payload);

        $this->conversations->update($conversationId, [
            'state' => ConversationState::ACTIVE,
        ]);

        $this->events->dispatch(new MessageCreated($messageId, $conversationId, (string) $payload['role']));

        return $messageId;
    }

    /**
     * @param  list<array<string, mixed>>  $newMessages
     */
    public function appendAgentMessages(string $conversationId, array $newMessages): void
    {
        foreach ($newMessages as $message) {
            $this->appendAgentMessage($conversationId, $message);
        }
    }

    public function markWaitingApproval(string $conversationId): void
    {
        $this->conversations->update($conversationId, [
            'state' => ConversationState::WAITING_APPROVAL,
        ]);

        $this->events->dispatch(new ConversationUpdated($conversationId, ConversationState::WAITING_APPROVAL));
    }

    public function markActive(string $conversationId): void
    {
        $this->conversations->update($conversationId, [
            'state' => ConversationState::ACTIVE,
        ]);

        $this->events->dispatch(new ConversationUpdated($conversationId, ConversationState::ACTIVE));
    }

    /** @return list<array<string, mixed>> */
    public function storedMessages(string $conversationId): array
    {
        return $this->messages->forConversation($conversationId);
    }

    protected function resolveHistoryLimit(?string $agentKey): int
    {
        if ($agentKey !== null) {
            $agent = $this->agents->find($agentKey);

            if ($agent !== null && isset($agent->limits()['max_history_messages'])) {
                return max(1, (int) $agent->limits()['max_history_messages']);
            }
        }

        return max(1, (int) $this->config->get('limen-ai.conversations.history_limit', 50));
    }

    /**
     * @param  list<array<string, mixed>>  $stored
     * @return list<array<string, mixed>>
     */
    protected function messagesWithSummaryApplied(array $stored): array
    {
        $keepRecent = max(1, (int) $this->config->get('limen-ai.conversations.summary_keep_recent', 12));

        if (count($stored) <= $keepRecent) {
            return $stored;
        }

        return array_slice($stored, -$keepRecent);
    }
}
