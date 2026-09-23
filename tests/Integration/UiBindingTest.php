<?php

namespace LimenAi\Tests\Integration;

use Illuminate\Support\Facades\Route;
use LimenAi\Http\Services\ConversationAccessGuard;
use LimenAi\Tests\TestCase;

class UiBindingTest extends TestCase
{
    public function test_ui_routes_and_access_guard_are_registered(): void
    {
        config()->set('limen-ai.ui.enabled', true);

        $this->assertInstanceOf(ConversationAccessGuard::class, app(ConversationAccessGuard::class));
        $this->assertTrue(Route::has('limen-ai.agents.show'));
        $this->assertTrue(Route::has('limen-ai.conversations.index'));
        $this->assertTrue(Route::has('limen-ai.conversations.store'));
        $this->assertTrue(Route::has('limen-ai.guest.session'));
        $this->assertTrue(Route::has('limen-ai.messages.store'));
        $this->assertTrue(Route::has('limen-ai.runs.show'));
    }
}
