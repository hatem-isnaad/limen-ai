<?php

namespace LimenAi\Providers\Concerns;

/**
 * Accumulates OpenAI chat-completions stream tool_call deltas by index.
 */
final class OpenAiStreamingToolCallAssembler
{
    /** @var array<int, array{id: string, name: string, arguments: string}> */
    private array $calls = [];

    /**
     * @param  list<array<string, mixed>>  $toolCallDeltas
     */
    public function ingestDeltas(array $toolCallDeltas): void
    {
        foreach ($toolCallDeltas as $delta) {
            if (! is_array($delta)) {
                continue;
            }

            $index = (int) ($delta['index'] ?? 0);

            if (! isset($this->calls[$index])) {
                $this->calls[$index] = ['id' => '', 'name' => '', 'arguments' => ''];
            }

            if (isset($delta['id']) && is_string($delta['id']) && $delta['id'] !== '') {
                $this->calls[$index]['id'] = $delta['id'];
            }

            $function = $delta['function'] ?? null;

            if (! is_array($function)) {
                continue;
            }

            if (isset($function['name']) && is_string($function['name']) && $function['name'] !== '') {
                $this->calls[$index]['name'] = $function['name'];
            }

            if (isset($function['arguments']) && is_string($function['arguments'])) {
                $this->calls[$index]['arguments'] .= $function['arguments'];
            }
        }
    }

    public function hasToolCalls(): bool
    {
        foreach ($this->calls as $call) {
            if ($call['name'] !== '') {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function toOpenAiToolCalls(): array
    {
        ksort($this->calls);

        $toolCalls = [];

        foreach ($this->calls as $call) {
            if ($call['name'] === '') {
                continue;
            }

            $toolCalls[] = [
                'id' => $call['id'] !== '' ? $call['id'] : $call['name'],
                'type' => 'function',
                'function' => [
                    'name' => $call['name'],
                    'arguments' => $call['arguments'],
                ],
            ];
        }

        return $toolCalls;
    }
}
