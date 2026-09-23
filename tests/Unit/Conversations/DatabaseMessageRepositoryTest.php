<?php

namespace LimenAi\Tests\Unit\Conversations;

use Illuminate\Support\Facades\DB;
use LimenAi\Conversations\DatabaseConversationRepository;
use LimenAi\Conversations\DatabaseMessageRepository;
use LimenAi\Tests\DatabaseTestCase;

class DatabaseMessageRepositoryTest extends DatabaseTestCase
{
    public function test_it_persists_messages_and_respects_history_limit(): void
    {
        $conversations = new DatabaseConversationRepository(DB::connection());
        $messages = new DatabaseMessageRepository(DB::connection());

        $conversationId = $conversations->create([
            'agent_key' => 'example',
            'user_id' => 1,
        ]);

        foreach (['One', 'Two', 'Three'] as $content) {
            $messages->create($conversationId, [
                'role' => 'user',
                'content' => $content,
            ]);
        }

        $limited = $messages->forConversation($conversationId, 2);

        $this->assertCount(2, $limited);
        $this->assertSame('Two', $limited[0]['content']);
        $this->assertSame('Three', $limited[1]['content']);
    }

    public function test_it_persists_messages_across_repository_instances(): void
    {
        $conversations = new DatabaseConversationRepository(DB::connection());
        $conversationId = $conversations->create([
            'agent_key' => 'example',
            'user_id' => 1,
        ]);

        $first = new DatabaseMessageRepository(DB::connection());
        $messageId = $first->create($conversationId, [
            'role' => 'assistant',
            'content' => 'Persisted reply',
            'metadata' => ['source' => 'test'],
        ]);

        $second = new DatabaseMessageRepository(DB::connection());
        $loaded = $second->forConversation($conversationId);

        $this->assertCount(1, $loaded);
        $this->assertSame($messageId, $loaded[0]['id']);
        $this->assertSame('Persisted reply', $loaded[0]['content']);
        $this->assertSame('test', $loaded[0]['metadata']['source']);
    }
}
