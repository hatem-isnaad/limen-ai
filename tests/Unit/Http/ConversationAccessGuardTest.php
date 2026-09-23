<?php

namespace LimenAi\Tests\Unit\Http;

use Illuminate\Auth\GenericUser;
use LimenAi\Contracts\Conversations\ConversationRepository;
use LimenAi\Http\Services\ConversationAccessGuard;
use LimenAi\Tests\TestCase;

class ConversationAccessGuardTest extends TestCase
{
    public function test_it_allows_matching_user_and_denies_others(): void
    {
        $conversationId = app(ConversationRepository::class)->create([
            'agent_key' => 'example',
            'user_id' => 1,
        ]);

        $guard = app(ConversationAccessGuard::class);

        $this->assertTrue($guard->canAccess(new GenericUser(['id' => 1]), $conversationId));
        $this->assertFalse($guard->canAccess(new GenericUser(['id' => 2]), $conversationId));
        $this->assertFalse($guard->canAccess(null, $conversationId));
    }
}
