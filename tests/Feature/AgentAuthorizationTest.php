<?php

namespace LimenAi\Tests\Feature;

use Illuminate\Auth\GenericUser;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use LimenAi\Contracts\Runtime\AgentRuntime;
use LimenAi\Exceptions\AgentAuthorizationException;
use LimenAi\Exceptions\RunContextAuthorizationException;
use LimenAi\Exceptions\UnauthenticatedException;
use LimenAi\Providers\Fake\FakeLlmProvider;
use LimenAi\Providers\LlmResponseData;
use LimenAi\Runtime\RunContextData;
use LimenAi\Tests\TestCase;

class AgentAuthorizationTest extends TestCase
{
    public function test_it_blocks_unauthenticated_agent_runs(): void
    {
        auth()->logout();

        app(FakeLlmProvider::class)->setDefaultResponse(LlmResponseData::fromArray([
            'content' => 'Should not run.',
            'finish_reason' => 'stop',
        ]));

        $this->expectException(UnauthenticatedException::class);

        app(AgentRuntime::class)->run(
            'example',
            'conv-auth',
            'Hello',
            RunContextData::make(['user_id' => 1]),
        );
    }

    public function test_it_blocks_agent_runs_when_gate_denies_ability(): void
    {
        $this->actingAs(new GenericUser(['id' => 1]));

        config()->set('limen-ai.agents.example.authorization.abilities', ['agents.example']);

        Gate::define('agents.example', fn (): bool => false);

        $this->expectException(AgentAuthorizationException::class);

        app(AgentRuntime::class)->run(
            'example',
            'conv-auth',
            'Hello',
            RunContextData::make(['user_id' => 1]),
        );
    }

    public function test_it_blocks_spoofed_context_user_ids(): void
    {
        $this->actingAs(new GenericUser(['id' => 1]));

        app(FakeLlmProvider::class)->setDefaultResponse(LlmResponseData::fromArray([
            'content' => 'Should not run.',
            'finish_reason' => 'stop',
        ]));

        $this->expectException(RunContextAuthorizationException::class);

        app(AgentRuntime::class)->run(
            'example',
            'conv-auth',
            'Hello',
            RunContextData::make(['user_id' => 999]),
        );
    }

    public function test_it_allows_guest_agent_runs_with_valid_guest_token(): void
    {
        auth()->logout();

        config()->set('limen-ai.agents.example.authorization.guest_allowed', true);

        Cache::put('limen-ai:guest:guest-session-1', [
            'agent' => 'example',
            'profile' => ['name' => 'Guest'],
        ], now()->addHour());

        app(FakeLlmProvider::class)->setDefaultResponse(LlmResponseData::fromArray([
            'content' => 'Guest reply.',
            'finish_reason' => 'stop',
        ]));

        $runId = app(AgentRuntime::class)->run(
            'example',
            'conv-guest',
            'Hello guest',
            RunContextData::make(['guest_token' => 'guest-session-1']),
        );

        $this->assertNotEmpty($runId);
    }
}
