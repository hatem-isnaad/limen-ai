<?php

namespace LimenAi\Http\Protocols;

use LimenAi\Providers\LlmStreamChunk;

/** Encodes Limen stream chunks for Vercel AI SDK UI message streams. */
final class VercelAiStreamEncoder
{
    public function __construct(
        private readonly string $messageId,
    ) {}

    /** @return list<string> SSE `data:` lines (without trailing double newline). */
    public function encode(LlmStreamChunk $chunk): array
    {
        $lines = [];

        if ($chunk->delta !== '') {
            $lines[] = $this->line([
                'type' => 'text-delta',
                'id' => $this->messageId,
                'delta' => $chunk->delta,
            ]);
        }

        if (($chunk->meta['type'] ?? null) === 'tool-call') {
            $lines[] = $this->line([
                'type' => 'tool-call',
                'toolCallId' => (string) ($chunk->meta['tool_call_id'] ?? ''),
                'toolName' => (string) ($chunk->meta['tool_name'] ?? ''),
                'args' => $chunk->meta['arguments'] ?? [],
            ]);
        }

        if (($chunk->meta['type'] ?? null) === 'tool-result') {
            $lines[] = $this->line([
                'type' => 'tool-result',
                'toolCallId' => (string) ($chunk->meta['tool_call_id'] ?? ''),
                'result' => $chunk->meta['result'] ?? null,
            ]);
        }

        if ($chunk->done) {
            $finish = [
                'type' => 'finish',
                'finishReason' => $chunk->finishReason ?? 'stop',
            ];

            if (isset($chunk->meta['run_id'])) {
                $finish['runId'] = $chunk->meta['run_id'];
            }

            if (isset($chunk->meta['usage'])) {
                $finish['usage'] = $chunk->meta['usage'];
            }

            $lines[] = $this->line($finish);
        }

        return $lines;
    }

    /** @return list<string> */
    public function start(): array
    {
        return [
            $this->line([
                'type' => 'start',
                'messageId' => $this->messageId,
            ]),
        ];
    }

    /** @param  array<string, mixed>  $payload */
    private function line(array $payload): string
    {
        return 'data: '.json_encode($payload, JSON_THROW_ON_ERROR);
    }
}
