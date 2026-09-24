<?php

namespace LimenAi\Tests\Unit\Http;

use LimenAi\Http\Protocols\AgUiStreamEncoder;
use LimenAi\Providers\LlmStreamChunk;
use LimenAi\Tests\TestCase;

class AgUiStreamEncoderTest extends TestCase
{
    public function test_encodes_run_lifecycle(): void
    {
        $encoder = new AgUiStreamEncoder('run-1', 'thread-1');

        $this->assertStringContainsString('RUN_STARTED', $encoder->runStarted()[0]);

        $delta = $encoder->encode(new LlmStreamChunk('Hi', false))[0];
        $this->assertStringContainsString('TEXT_MESSAGE_CONTENT', $delta);

        $finish = $encoder->encode(new LlmStreamChunk('', true, [], null, [
            'usage' => ['total_tokens' => 5],
        ]))[0];

        $this->assertStringContainsString('RUN_FINISHED', $finish);
        $this->assertStringContainsString('"total_tokens":5', $finish);
    }
}
