<?php

namespace LimenAi\Http\Protocols;

use LimenAi\Providers\LlmStreamChunk;

/** AG-UI–style event encoder for CopilotKit-compatible clients. */
final class AgUiStreamEncoder
{
    public function __construct(
        private readonly string $runId,
        private readonly string $threadId,
    ) {}

    /** @return list<string> */
    public function runStarted(): array
    {
        return [$this->event('RUN_STARTED', [
            'runId' => $this->runId,
            'threadId' => $this->threadId,
        ])];
    }

    /** @return list<string> */
    public function encode(LlmStreamChunk $chunk): array
    {
        $lines = [];

        if ($chunk->delta !== '') {
            $lines[] = $this->event('TEXT_MESSAGE_CONTENT', [
                'messageId' => $this->runId,
                'delta' => $chunk->delta,
            ]);
        }

        if (($chunk->meta['type'] ?? null) === 'tool-call') {
            $lines[] = $this->event('TOOL_CALL_START', [
                'toolCallId' => (string) ($chunk->meta['tool_call_id'] ?? ''),
                'toolCallName' => (string) ($chunk->meta['tool_name'] ?? ''),
            ]);
            $lines[] = $this->event('TOOL_CALL_ARGS', [
                'toolCallId' => (string) ($chunk->meta['tool_call_id'] ?? ''),
                'delta' => json_encode($chunk->meta['arguments'] ?? [], JSON_THROW_ON_ERROR),
            ]);
            $lines[] = $this->event('TOOL_CALL_END', [
                'toolCallId' => (string) ($chunk->meta['tool_call_id'] ?? ''),
            ]);
        }

        if (($chunk->meta['type'] ?? null) === 'tool-result') {
            $lines[] = $this->event('TOOL_CALL_RESULT', [
                'toolCallId' => (string) ($chunk->meta['tool_call_id'] ?? ''),
                'content' => $chunk->meta['result'] ?? null,
            ]);
        }

        if ($chunk->done) {
            $payload = ['runId' => $this->runId];

            if (isset($chunk->meta['usage'])) {
                $payload['usage'] = $chunk->meta['usage'];
            }

            $lines[] = $this->event('RUN_FINISHED', $payload);
        }

        return $lines;
    }

    /** @param  array<string, mixed>  $payload */
    private function event(string $type, array $payload): string
    {
        return 'data: '.json_encode(array_merge(['type' => $type], $payload), JSON_THROW_ON_ERROR);
    }
}
