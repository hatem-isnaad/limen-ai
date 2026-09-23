<?php

namespace LimenAi\Runtime;

use LimenAi\Contracts\Providers\LlmResponse;

class ToolCallParser
{
    /**
     * @return list<array{ id: string, name: string, arguments: array<string, mixed> }>
     */
    public function parse(LlmResponse $response): array
    {
        $parsed = [];

        foreach ($response->toolCalls() as $toolCall) {
            if (! is_array($toolCall)) {
                continue;
            }

            $function = $toolCall['function'] ?? null;

            if (! is_array($function)) {
                continue;
            }

            $name = (string) ($function['name'] ?? '');

            if ($name === '') {
                continue;
            }

            $parsed[] = [
                'id' => (string) ($toolCall['id'] ?? $name),
                'name' => $name,
                'arguments' => $this->parseArguments($function['arguments'] ?? []),
            ];
        }

        return $parsed;
    }

    /** @return array<string, mixed> */
    protected function parseArguments(mixed $arguments): array
    {
        if (is_array($arguments)) {
            return $arguments;
        }

        if (! is_string($arguments) || trim($arguments) === '') {
            return [];
        }

        $decoded = json_decode($arguments, true);

        return is_array($decoded) ? $decoded : [];
    }
}
