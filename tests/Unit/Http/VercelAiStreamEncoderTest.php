<?php

namespace LimenAi\Tests\Unit\Http;

use LimenAi\Http\Protocols\VercelAiStreamEncoder;
use LimenAi\Providers\LlmStreamChunk;
use LimenAi\Tests\TestCase;

class VercelAiStreamEncoderTest extends TestCase
{
    public function test_encodes_text_delta_and_finish_with_usage(): void
    {
        $encoder = new VercelAiStreamEncoder('msg-1');

        $start = $encoder->start()[0];
        $this->assertStringContainsString('"type":"start"', $start);

        $delta = $encoder->encode(new LlmStreamChunk('Hi', false))[0];
        $this->assertStringContainsString('"type":"text-delta"', $delta);
        $this->assertStringContainsString('"delta":"Hi"', $delta);

        $finish = $encoder->encode(new LlmStreamChunk('', true, [], null, [
            'run_id' => 'run-1',
            'usage' => ['total_tokens' => 10],
        ]))[0];

        $this->assertStringContainsString('"type":"finish"', $finish);
        $this->assertStringContainsString('"total_tokens":10', $finish);
    }
}
