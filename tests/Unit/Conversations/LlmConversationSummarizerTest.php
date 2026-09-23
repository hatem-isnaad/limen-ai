<?php

namespace LimenAi\Tests\Unit\Conversations;

use LimenAi\Conversations\LlmConversationSummarizer;
use LimenAi\Contracts\Conversations\ConversationRepository;
use LimenAi\Providers\Fake\FakeLlmProvider;
use LimenAi\Providers\LlmResponseData;
use LimenAi\Runtime\RunContextData;
use LimenAi\Tests\TestCase;

class LlmConversationSummarizerTest extends TestCase
{
    public function test_it_returns_null_below_threshold(): void
    {
        config()->set('limen-ai.conversations.summary_threshold', 5);

        $summarizer = app(LlmConversationSummarizer::class);

        $this->assertNull($summarizer->summarize('conv-1', [
            ['role' => 'user', 'content' => 'Hello'],
            ['role' => 'assistant', 'content' => 'Hi'],
        ]));
    }

    public function test_it_summarizes_and_caches_conversation_metadata(): void
    {
        config()->set('limen-ai.conversations.summary_threshold', 4);
        config()->set('limen-ai.conversations.summary_keep_recent', 2);
        config()->set('limen-ai.conversations.summary_refresh_messages', 8);

        app(FakeLlmProvider::class)->setDefaultResponse(LlmResponseData::fromArray([
            'content' => '- User asked about shipment 12345',
            'finish_reason' => 'stop',
        ]));

        $conversations = app(ConversationRepository::class);
        $conversations->create([
            'id' => 'conv-summary',
            'agent_key' => 'example',
            'user_id' => 1,
        ]);

        $messages = [
            ['role' => 'user', 'content' => 'One'],
            ['role' => 'assistant', 'content' => 'Two'],
            ['role' => 'user', 'content' => 'Three'],
            ['role' => 'assistant', 'content' => 'Four'],
            ['role' => 'user', 'content' => 'Five'],
        ];

        $summarizer = app(LlmConversationSummarizer::class);
        $summary = $summarizer->summarize('conv-summary', $messages);

        $this->assertSame('- User asked about shipment 12345', $summary);

        $conversation = $conversations->find('conv-summary');
        $this->assertSame('- User asked about shipment 12345', $conversation['metadata']['conversation_summary']);
        $this->assertSame(5, $conversation['metadata']['summary_message_count']);

        app(FakeLlmProvider::class)->setDefaultResponse(LlmResponseData::fromArray([
            'content' => 'Should not be called again yet',
            'finish_reason' => 'stop',
        ]));

        $cached = $summarizer->summarize('conv-summary', $messages);
        $this->assertSame('- User asked about shipment 12345', $cached);
    }
}
