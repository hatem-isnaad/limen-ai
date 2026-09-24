<?php

namespace LimenAi\Tests\Unit\Providers;

use Illuminate\Support\Facades\Http;
use LimenAi\Providers\Aws\AwsEventStreamMessageReader;
use LimenAi\Providers\Bedrock\BedrockProvider;
use LimenAi\Tests\TestCase;

class BedrockStreamingTest extends TestCase
{
    public function test_event_stream_reader_decodes_text_deltas(): void
    {
        $body = AwsEventStreamMessageReader::frame([
            'contentBlockDelta' => ['delta' => ['text' => 'Hello']],
        ]).AwsEventStreamMessageReader::frame([
            'messageStop' => ['stopReason' => 'end_turn'],
        ]);

        $reader = new AwsEventStreamMessageReader(\GuzzleHttp\Psr7\Utils::streamFor($body));
        $events = iterator_to_array($reader->messages());

        $this->assertSame('Hello', $events[0]['contentBlockDelta']['delta']['text']);
        $this->assertSame('end_turn', $events[1]['messageStop']['stopReason']);
    }

    public function test_bedrock_stream_yields_deltas(): void
    {
        $streamBody = AwsEventStreamMessageReader::frame([
            'contentBlockDelta' => ['delta' => ['text' => 'Stream']],
        ]).AwsEventStreamMessageReader::frame([
            'metadata' => ['usage' => ['inputTokens' => 2, 'outputTokens' => 1]],
        ]).AwsEventStreamMessageReader::frame([
            'messageStop' => ['stopReason' => 'end_turn'],
        ]);

        Http::fake([
            'bedrock-runtime.*' => Http::response($streamBody, 200, [
                'Content-Type' => 'application/vnd.amazon.eventstream',
            ]),
        ]);

        config()->set('limen-ai.providers.bedrock', [
            'driver' => 'bedrock',
            'access_key_id' => 'key',
            'secret_access_key' => 'secret',
            'region' => 'us-east-1',
            'model' => 'anthropic.claude-3-5-haiku-20241022-v1:0',
        ]);

        $provider = new BedrockProvider('bedrock', config('limen-ai.providers.bedrock'));
        $text = '';

        foreach ($provider->streamChat([['role' => 'user', 'content' => 'Hi']]) as $chunk) {
            $text .= $chunk->delta;
        }

        $this->assertSame('Stream', $text);
    }
}
