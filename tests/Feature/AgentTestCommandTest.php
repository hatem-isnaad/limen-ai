<?php

namespace LimenAi\Tests\Feature;

use LimenAi\Providers\Fake\FakeLlmProvider;
use LimenAi\Providers\LlmResponseData;
use LimenAi\Tests\TestCase;

class AgentTestCommandTest extends TestCase
{
    public function test_it_authenticates_cli_user_before_running_agent(): void
    {
        app(FakeLlmProvider::class)->setDefaultResponse(LlmResponseData::fromArray([
            'content' => 'CLI agent response.',
            'finish_reason' => 'stop',
        ]));

        $this->artisan('limen-ai:agent:test', [
            'agent' => 'example',
            '--message' => 'Hello',
            '--user' => 1,
        ])
            ->expectsOutputToContain('CLI agent response.')
            ->assertSuccessful();
    }

    public function test_it_fails_when_expected_substring_is_missing(): void
    {
        app(\LimenAi\Providers\Fake\FakeLlmProvider::class)->setDefaultResponse(\LimenAi\Providers\LlmResponseData::fromArray([
            'content' => 'Unexpected response.',
            'finish_reason' => 'stop',
        ]));

        $this->artisan('limen-ai:agent:test', [
            'agent' => 'example',
            '--message' => 'Hello',
            '--expect-contains' => 'Expected phrase',
        ])->assertFailed();
    }

    public function test_it_fails_when_forbidden_substring_is_present(): void
    {
        app(FakeLlmProvider::class)->setDefaultResponse(LlmResponseData::fromArray([
            'content' => 'This contains a secret token.',
            'finish_reason' => 'stop',
        ]));

        $this->artisan('limen-ai:agent:test', [
            'agent' => 'example',
            '--message' => 'Hello',
            '--expect-not-contains' => 'secret',
        ])->assertFailed();
    }

    public function test_it_fails_when_min_length_is_not_met(): void
    {
        app(FakeLlmProvider::class)->setDefaultResponse(LlmResponseData::fromArray([
            'content' => 'Hi',
            'finish_reason' => 'stop',
        ]));

        $this->artisan('limen-ai:agent:test', [
            'agent' => 'example',
            '--message' => 'Hello',
            '--min-length' => 20,
        ])->assertFailed();
    }

    public function test_it_passes_when_expected_substring_is_present(): void
    {
        app(\LimenAi\Providers\Fake\FakeLlmProvider::class)->setDefaultResponse(\LimenAi\Providers\LlmResponseData::fromArray([
            'content' => 'Hello from Limen AI.',
            'finish_reason' => 'stop',
        ]));

        $this->artisan('limen-ai:agent:test', [
            'agent' => 'example',
            '--message' => 'Hello',
            '--expect-contains' => 'Limen AI',
        ])->assertSuccessful();
    }
}
