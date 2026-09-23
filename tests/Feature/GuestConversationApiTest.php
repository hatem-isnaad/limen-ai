<?php

namespace LimenAi\Tests\Feature;

use LimenAi\Authorization\CacheGuestSessionValidator;
use LimenAi\Contracts\Conversations\ConversationRepository;
use LimenAi\Contracts\Conversations\MessageRepository;
use LimenAi\Tests\TestCase;

class GuestConversationApiTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('limen-ai.ui.middleware', []);
        $app['config']->set('limen-ai.ui.auth_middleware', false);
        $app['config']->set('limen-ai.ui.guest.enabled', true);
        $app['config']->set('limen-ai.agents.example.authorization.guest_allowed', true);
        $app['config']->set('limen-ai.authorization.guest.validator', CacheGuestSessionValidator::class);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->app['auth']->logout();
    }

    public function test_it_registers_a_guest_session_and_lists_conversations(): void
    {
        $session = $this->postJson('/limen-ai/guest/session', [
            'profile' => [
                'name' => 'Guest User',
                'email' => 'guest@example.com',
                'phone' => '+15550000000',
            ],
        ])->assertCreated()->json();

        $token = $session['guest_token'];

        $conversationId = $this->postJson('/limen-ai/conversations', [
            'agent' => 'example',
        ], [
            'X-Limen-Guest-Token' => $token,
        ])->assertCreated()->json('conversation.id');

        app(MessageRepository::class)->create($conversationId, [
            'role' => 'user',
            'content' => 'Hello from guest',
        ]);

        $this->getJson('/limen-ai/conversations?agent=example', [
            'X-Limen-Guest-Token' => $token,
        ])
            ->assertOk()
            ->assertJsonPath('conversations.0.id', $conversationId)
            ->assertJsonPath('conversations.0.preview', 'Hello from guest');
    }

    public function test_it_denies_guest_access_without_matching_token(): void
    {
        $session = $this->postJson('/limen-ai/guest/session', [
            'profile' => [
                'name' => 'Guest User',
                'email' => 'guest@example.com',
            ],
        ])->assertCreated()->json();

        $conversationId = $this->postJson('/limen-ai/conversations', [
            'agent' => 'example',
        ], [
            'X-Limen-Guest-Token' => $session['guest_token'],
        ])->json('conversation.id');

        $this->getJson("/limen-ai/conversations/{$conversationId}", [
            'X-Limen-Guest-Token' => 'wrong-token',
        ])->assertForbidden();
    }

    public function test_it_creates_guest_conversation_from_profile_payload(): void
    {
        $response = $this->postJson('/limen-ai/conversations', [
            'agent' => 'example',
            'profile' => [
                'name' => 'Walk-in Guest',
                'email' => 'walkin@example.com',
            ],
        ])->assertCreated();

        $conversationId = $response->json('conversation.id');
        $guestToken = $response->json('guest_token');

        $this->assertNotEmpty($guestToken);
        $this->assertSame('Chat — Walk-in Guest', $response->json('conversation.title'));

        $conversation = app(ConversationRepository::class)->find($conversationId);
        $this->assertSame($guestToken, $conversation['guest_token']);
        $this->assertSame('Walk-in Guest', $conversation['metadata']['guest_profile']['name']);
    }
}
