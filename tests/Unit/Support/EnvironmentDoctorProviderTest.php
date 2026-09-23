<?php

namespace LimenAi\Tests\Unit\Support;

use Illuminate\Support\Facades\Http;
use LimenAi\Support\EnvironmentDoctor;
use LimenAi\Tests\TestCase;

class EnvironmentDoctorProviderTest extends TestCase
{
    public function test_it_warns_when_openai_endpoint_is_unreachable(): void
    {
        config()->set('limen-ai.default_agent', 'app_assistant');
        config()->set('limen-ai.agents.app_assistant', [
            'provider' => 'openai',
            'model' => 'gpt-4.1-mini',
        ]);
        config()->set('limen-ai.providers.openai.api_key', 'sk-live-key');
        config()->set('limen-ai.providers.openai.base_url', 'https://api.openai.com/v1');

        Http::fake([
            'api.openai.com/v1/models' => Http::response(null, 401),
        ]);

        $report = app(EnvironmentDoctor::class)->inspect('local');

        $this->assertTrue(collect($report['warnings'])->contains(
            fn (string $warning): bool => str_contains($warning, 'OpenAI endpoint'),
        ));
    }

    public function test_it_warns_when_anthropic_endpoint_is_unreachable(): void
    {
        config()->set('limen-ai.default_agent', 'app_assistant');
        config()->set('limen-ai.agents.app_assistant', [
            'provider' => 'anthropic',
            'model' => 'claude-sonnet-4-20250514',
        ]);
        config()->set('limen-ai.providers.anthropic.api_key', 'sk-ant-test');

        Http::fake([
            'api.anthropic.com/v1/models' => Http::response(null, 401),
        ]);

        $report = app(EnvironmentDoctor::class)->inspect('local');

        $this->assertTrue(collect($report['warnings'])->contains(
            fn (string $warning): bool => str_contains($warning, 'Anthropic API returned HTTP 401'),
        ));
    }

    public function test_it_warns_when_gemini_endpoint_is_unreachable(): void
    {
        config()->set('limen-ai.default_agent', 'app_assistant');
        config()->set('limen-ai.agents.app_assistant', [
            'provider' => 'gemini',
            'model' => 'gemini-2.0-flash',
        ]);
        config()->set('limen-ai.providers.gemini.api_key', 'gemini-test-key');

        Http::fake([
            'generativelanguage.googleapis.com/v1beta/models*' => Http::response(null, 403),
        ]);

        $report = app(EnvironmentDoctor::class)->inspect('local');

        $this->assertTrue(collect($report['warnings'])->contains(
            fn (string $warning): bool => str_contains($warning, 'Gemini API returned HTTP 403'),
        ));
    }
}
