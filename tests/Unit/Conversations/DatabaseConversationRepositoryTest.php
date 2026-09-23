<?php

namespace LimenAi\Tests\Unit\Conversations;

use Illuminate\Support\Facades\DB;
use LimenAi\Conversations\ConversationState;
use LimenAi\Conversations\DatabaseConversationRepository;
use LimenAi\Conversations\DatabaseMessageRepository;
use LimenAi\Tests\DatabaseTestCase;

class DatabaseConversationRepositoryTest extends DatabaseTestCase
{
    public function test_it_persists_conversations_across_repository_instances(): void
    {
        $first = new DatabaseConversationRepository(DB::connection());

        $conversationId = $first->create([
            'agent_key' => 'example',
            'user_id' => 1,
            'title' => 'Support chat',
            'metadata' => ['preferred_language' => 'en'],
        ]);

        $second = new DatabaseConversationRepository(DB::connection());
        $conversation = $second->find($conversationId);

        $this->assertNotNull($conversation);
        $this->assertSame('example', $conversation['agent_key']);
        $this->assertSame(1, $conversation['user_id']);
        $this->assertSame('Support chat', $conversation['title']);
        $this->assertSame('en', $conversation['metadata']['preferred_language']);
    }

    public function test_it_lists_conversations_for_user_and_guest(): void
    {
        $repo = new DatabaseConversationRepository(DB::connection());

        $userConversationId = $repo->create([
            'agent_key' => 'example',
            'user_id' => 7,
            'title' => 'User chat',
        ]);

        $guestConversationId = $repo->create([
            'agent_key' => 'example',
            'guest_token' => 'guest-token-1',
            'title' => 'Guest chat',
        ]);

        $this->assertSame(
            [$userConversationId],
            array_column($repo->listForUser(7, 'example'), 'id'),
        );

        $this->assertSame(
            [$guestConversationId],
            array_column($repo->listForGuest('guest-token-1', 'example'), 'id'),
        );
    }

    public function test_it_updates_conversation_state_and_metadata(): void
    {
        $repo = new DatabaseConversationRepository(DB::connection());

        $conversationId = $repo->create([
            'agent_key' => 'example',
            'user_id' => 1,
            'metadata' => ['preferred_language' => 'en'],
        ]);

        $repo->update($conversationId, [
            'title' => 'Updated title',
            'state' => ConversationState::ARCHIVED,
            'metadata' => ['preferred_language' => 'ar'],
        ]);

        $conversation = $repo->find($conversationId);

        $this->assertSame('Updated title', $conversation['title']);
        $this->assertSame(ConversationState::ARCHIVED, $conversation['state']);
        $this->assertSame('ar', $conversation['metadata']['preferred_language']);
    }

    public function test_messages_cascade_when_conversation_is_deleted(): void
    {
        $conversations = new DatabaseConversationRepository(DB::connection());
        $messages = new DatabaseMessageRepository(DB::connection());

        $conversationId = $conversations->create([
            'agent_key' => 'example',
            'user_id' => 1,
        ]);

        $messages->create($conversationId, [
            'role' => 'user',
            'content' => 'Hello',
        ]);

        DB::table('limen_ai_conversations')->where('id', $conversationId)->delete();

        $this->assertNull($conversations->find($conversationId));
        $this->assertSame([], $messages->forConversation($conversationId));
    }
}
