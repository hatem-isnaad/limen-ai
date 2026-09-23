<?php

namespace LimenAi\Tests\Unit\Conversations;

use LimenAi\Contracts\Conversations\ConversationSummarizer;
use LimenAi\Conversations\ConversationService;
use LimenAi\Runtime\RunContextData;
use LimenAi\Tests\TestCase;

class ConversationServiceSummaryTest extends TestCase
{
    public function test_it_keeps_only_recent_messages_when_summary_is_present(): void
    {
        config()->set('limen-ai.conversations.summary_keep_recent', 2);

        $this->app->instance(ConversationSummarizer::class, new class implements ConversationSummarizer
        {
            public function summarize(string $conversationId, array $messages): ?string
            {
                return 'Older context summary';
            }
        });

        $service = app(ConversationService::class);
        $service->ensure('conv-slice', 'example', RunContextData::make(['user_id' => 1]));

        $service->appendUserMessage('conv-slice', 'Message one');
        $service->appendAgentMessage('conv-slice', ['role' => 'assistant', 'content' => 'Reply one']);
        $service->appendUserMessage('conv-slice', 'Message two');
        $service->appendAgentMessage('conv-slice', ['role' => 'assistant', 'content' => 'Reply two']);

        $history = $service->historyForAgent('conv-slice');

        $this->assertCount(3, $history);
        $this->assertSame('system', $history[0]['role']);
        $this->assertStringContainsString('Older context summary', $history[0]['content']);
        $this->assertSame('user', $history[1]['role']);
        $this->assertSame('Message two', $history[1]['content']);
        $this->assertSame('assistant', $history[2]['role']);
        $this->assertSame('Reply two', $history[2]['content']);
    }
}
