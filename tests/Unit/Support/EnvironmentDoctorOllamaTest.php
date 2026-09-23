<?php

namespace LimenAi\Tests\Unit\Support;

use Illuminate\Support\Facades\Http;
use LimenAi\Support\EnvironmentDoctor;
use LimenAi\Tests\TestCase;

class EnvironmentDoctorOllamaTest extends TestCase
{
    public function test_it_warns_when_ollama_endpoint_is_unreachable(): void
    {
        config()->set('limen-ai.default_agent', 'app_assistant');
        config()->set('limen-ai.agents.app_assistant', [
            'provider' => 'openai',
            'model' => 'qwen3:8b',
        ]);
        config()->set('limen-ai.providers.openai.api_key', 'ollama');
        config()->set('limen-ai.providers.openai.base_url', 'http://localhost:11434/v1');

        Http::fake([
            'localhost:11434/v1/models' => Http::response(null, 500),
        ]);

        $report = app(EnvironmentDoctor::class)->inspect('local');

        $this->assertTrue(collect($report['warnings'])->contains(
            fn (string $warning): bool => str_contains($warning, 'Ollama endpoint'),
        ));
    }

    public function test_it_warns_when_configured_model_is_not_installed_on_ollama(): void
    {
        config()->set('limen-ai.default_agent', 'app_assistant');
        config()->set('limen-ai.agents.app_assistant', [
            'provider' => 'openai',
            'model' => 'qwen3:8b',
        ]);
        config()->set('limen-ai.providers.openai.api_key', 'ollama');
        config()->set('limen-ai.providers.openai.base_url', 'http://localhost:11434/v1');

        Http::fake([
            'localhost:11434/v1/models' => Http::response([
                'data' => [
                    ['id' => 'llama3.1:8b'],
                ],
            ], 200),
        ]);

        $report = app(EnvironmentDoctor::class)->inspect('local');

        $this->assertTrue(collect($report['warnings'])->contains(
            fn (string $warning): bool => str_contains($warning, 'qwen3:8b') && str_contains($warning, 'ollama pull'),
        ));
    }

    public function test_it_does_not_warn_when_ollama_lists_the_configured_model(): void
    {
        config()->set('limen-ai.default_agent', 'app_assistant');
        config()->set('limen-ai.agents.app_assistant', [
            'provider' => 'openai',
            'model' => 'qwen3:8b',
        ]);
        config()->set('limen-ai.providers.openai.api_key', 'ollama');
        config()->set('limen-ai.providers.openai.base_url', 'http://localhost:11434/v1');

        Http::fake([
            'localhost:11434/v1/models' => Http::response([
                'data' => [
                    ['id' => 'qwen3:8b'],
                ],
            ], 200),
        ]);

        $report = app(EnvironmentDoctor::class)->inspect('local');

        $this->assertFalse(collect($report['warnings'])->contains(
            fn (string $warning): bool => str_contains(strtolower($warning), 'ollama'),
        ));
    }

    public function test_it_skips_ollama_probe_for_non_ollama_openai_setup(): void
    {
        config()->set('limen-ai.default_agent', 'app_assistant');
        config()->set('limen-ai.agents.app_assistant', [
            'provider' => 'openai',
            'model' => 'gpt-4.1-mini',
        ]);
        config()->set('limen-ai.providers.openai.api_key', 'sk-live-key');
        config()->set('limen-ai.providers.openai.base_url', 'https://api.openai.com/v1');

        Http::fake([
            'api.openai.com/v1/models' => Http::response([
                'data' => [
                    ['id' => 'gpt-4.1-mini'],
                ],
            ], 200),
        ]);

        $report = app(EnvironmentDoctor::class)->inspect('local');

        $this->assertFalse(collect($report['warnings'])->contains(
            fn (string $warning): bool => str_contains(strtolower($warning), 'ollama'),
        ));
    }
}
