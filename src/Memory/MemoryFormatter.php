<?php

namespace LimenAi\Memory;

class MemoryFormatter
{
    /**
     * @param  list<array<string, mixed>>  $entries
     * @return list<array<string, mixed>>
     */
    public function toAgentMessages(array $entries): array
    {
        if ($entries === []) {
            return [];
        }

        $lines = [];

        foreach ($entries as $entry) {
            $key = (string) ($entry['key'] ?? 'memory');
            $value = $this->stringifyValue($entry['value'] ?? null);
            $lines[] = "- {$key}: {$value}";
        }

        return [[
            'role' => 'system',
            'content' => "Relevant memory:\n".implode("\n", $lines),
        ]];
    }

    protected function stringifyValue(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_scalar($value) || $value === null) {
            return (string) $value;
        }

        return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }
}
