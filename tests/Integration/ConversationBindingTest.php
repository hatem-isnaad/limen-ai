<?php

namespace LimenAi\Tests\Integration;

use LimenAi\Conversations\ConversationService;
use LimenAi\Conversations\InMemoryConversationRepository;
use LimenAi\Conversations\InMemoryMessageRepository;
use LimenAi\Conversations\NullConversationSummarizer;
use LimenAi\Contracts\Conversations\ConversationRepository;
use LimenAi\Contracts\Conversations\ConversationSummarizer;
use LimenAi\Contracts\Conversations\MessageRepository;
use LimenAi\Tests\TestCase;

class ConversationBindingTest extends TestCase
{
    public function test_conversation_contracts_are_bound(): void
    {
        $this->assertInstanceOf(InMemoryConversationRepository::class, app(ConversationRepository::class));
        $this->assertInstanceOf(InMemoryMessageRepository::class, app(MessageRepository::class));
        $this->assertInstanceOf(NullConversationSummarizer::class, app(ConversationSummarizer::class));
        $this->assertInstanceOf(ConversationService::class, app(ConversationService::class));
    }
}
