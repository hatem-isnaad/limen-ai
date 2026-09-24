<?php

namespace LimenAi\Tests\Unit\Observability;

use LimenAi\Observability\TokenUsage;
use LimenAi\Tests\TestCase;

class TokenUsageTest extends TestCase
{
    public function test_it_normalizes_openai_style_usage(): void
    {
        $normalized = TokenUsage::normalize([
            'prompt_tokens' => 80,
            'completion_tokens' => 48,
            'total_tokens' => 128,
        ]);

        $this->assertSame([
            'input_tokens' => 80,
            'output_tokens' => 48,
            'total_tokens' => 128,
        ], $normalized);
    }

    public function test_it_normalizes_anthropic_style_usage(): void
    {
        $normalized = TokenUsage::normalize([
            'input_tokens' => 18,
            'output_tokens' => 12,
        ]);

        $this->assertSame([
            'input_tokens' => 18,
            'output_tokens' => 12,
            'total_tokens' => 30,
        ], $normalized);
    }

    public function test_it_normalizes_gemini_style_usage(): void
    {
        $normalized = TokenUsage::normalize([
            'promptTokenCount' => 25,
            'candidatesTokenCount' => 15,
            'totalTokenCount' => 40,
        ]);

        $this->assertSame([
            'input_tokens' => 25,
            'output_tokens' => 15,
            'total_tokens' => 40,
        ], $normalized);
    }
}
