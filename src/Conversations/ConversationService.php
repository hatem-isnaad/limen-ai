<?php

namespace LimenAi\Conversations;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Container\Container;
use LimenAi\Agents\ClassAgentDefinition;
use LimenAi\Ai\Contracts\Conversational;
use LimenAi\Ai\Messages\Message;
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
        private readonly ConfigRepository $config,
        private readonly Dispatcher $events,
        private readonly AgentRepository $agents,
        private readonly Container $container,
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
        $limit = (int) $this->config->get('limen-ai.conversations.history_limit', 50);
        $stored = $this->messages->forConversation($conversationId, $limit);

        $agentMessages = $this->formatter->toAgentMessages($stored);
        $agentMessages = $this->mergeConversationalHistory($agentKey, $agentMessages);

        $summary = $this->summarizer->summarize($conversationId, $stored);

        if ($summary !== null && $summary !== '') {
            return array_merge(
                [['role' => 'system', 'content' => "Conversation summary:\n".$summary]],
                $agentMessages,
            );
        }

        return $agentMessages;
    }

    /**
     * @param  list<array<string, mixed>>  $storedMessages
     * @return list<array<string, mixed>>
     */
    protected function mergeConversationalHistory(?string $agentKey, array $storedMessages): array
    {
        if ($agentKey === null) {
            return $storedMessages;
        }

        $definition = $this->agents->find($agentKey);

        if (! $definition instanceof ClassAgentDefinition) {
            return $storedMessages;
        }

        $instance = $this->container->make($definition->className());

        if (! $instance instanceof Conversational) {
            return $storedMessages;
        }

        $custom = [];

        foreach ($instance->messages() as $message) {
            if ($message instanceof Message) {
                $custom[] = $message->toArray();

                continue;
            }

            $custom[] = (array) $message;
        }

        return array_merge($custom, $storedMessages);
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
     * @return list<string> Created message IDs in the same order
     */
    public function appendAgentMessages(string $conversationId, array $newMessages): array
    {
        $ids = [];

        foreach ($newMessages as $message) {
            $ids[] = $this->appendAgentMessage($conversationId, $message);
        }

        return $ids;
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
}
