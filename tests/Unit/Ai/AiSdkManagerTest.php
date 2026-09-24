<?php

namespace LimenAi\Tests\Unit\Ai;

use LimenAi\Ai\Sdk\AiSdkManager;
use LimenAi\Tests\TestCase;

class AiSdkManagerTest extends TestCase
{
    public function test_sdk_generators_return_fake_results_by_default(): void
    {
        $sdk = app(AiSdkManager::class);

        $images = $sdk->image()->generate('A test prompt');
        $this->assertNotEmpty($images[0]['url'] ?? null);

        $speech = $sdk->audio()->generate('Hello');
        $this->assertSame('base64', $speech['encoding']);

        $text = $sdk->transcription()->transcribe('/tmp/sample.wav');
        $this->assertStringContainsString('Transcribed', $text);

        $ranked = $sdk->rerank('query', ['a', 'b']);
        $this->assertSame(0, $ranked[0]['index']);
    }
}
