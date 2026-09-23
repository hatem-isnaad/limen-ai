<?php

namespace LimenAi\Tests\Unit\Conversations;

use Illuminate\Support\Facades\Event;
use LimenAi\Contracts\Conversations\ConversationRepository;
use LimenAi\Conversations\ConversationService;
use LimenAi\Conversations\ConversationState;
use LimenAi\Events\ConversationUpdated;
use LimenAi\Events\MessageCreated;
use LimenAi\Runtime\RunContextData;
use LimenAi\Tests\TestCase;

class ConversationServiceTest extends TestCase
{
    public function test_it_creates_conversation_on_first_ensure(): void
    {
        Event::fake([ConversationUpdated::class]);

        $service = app(ConversationService::class);

        $service->ensure('conv-new', 'example', RunContextData::make(['user_id' => 42]));

        $conversation = app(ConversationRepository::class)->find('conv-new');

        $this->assertNotNull($conversation);
        $this->assertSame('example', $conversation['agent_key']);
        $this->assertSame(42, $conversation['user_id']);
        $this->assertSame(ConversationState::ACTIVE, $conversation['state']);

        Event::assertDispatched(ConversationUpdated::class);
    }

    public function test_it_does_not_duplicate_conversation_on_repeated_ensure(): void
    {
        Event::fake([ConversationUpdated::class]);

        $service = app(ConversationService::class);
        $context = RunContextData::make(['user_id' => 1]);

        $service->ensure('conv-dup', 'example', $context);
        $service->ensure('conv-dup', 'example', $context);

        Event::assertDispatched(ConversationUpdated::class, 1);
    }

    public function test_it_persists_and_loads_message_history(): void
    {
        Event::fake([MessageCreated::class]);

        $service = app(ConversationService::class);
        $context = RunContextData::make(['user_id' => 1]);

        $service->ensure('conv-history', 'example', $context);
        $service->appendUserMessage('conv-history', 'First question');
        $service->appendAgentMessage('conv-history', [
            'role' => 'assistant',
            'content' => 'First answer',
        ]);

        $history = $service->historyForAgent('conv-history');

        $this->assertCount(2, $history);
        $this->assertSame('user', $history[0]['role']);
        $this->assertSame('First question', $history[0]['content']);
        $this->assertSame('assistant', $history[1]['role']);
        $this->assertSame('First answer', $history[1]['content']);

        Event::assertDispatched(MessageCreated::class, 2);
    }

    public function test_it_marks_conversation_waiting_for_approval(): void
    {
        Event::fake([ConversationUpdated::class]);

        $service = app(ConversationService::class);
        $service->ensure('conv-approval', 'example', RunContextData::make(['user_id' => 1]));

        $service->markWaitingApproval('conv-approval');

        $conversation = app(ConversationRepository::class)->find('conv-approval');

        $this->assertSame(ConversationState::WAITING_APPROVAL, $conversation['state']);
        Event::assertDispatched(ConversationUpdated::class, fn (ConversationUpdated $event): bool => $event->state === ConversationState::WAITING_APPROVAL);
    }
}
