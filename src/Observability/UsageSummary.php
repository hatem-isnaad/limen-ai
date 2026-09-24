<?php

namespace LimenAi\Observability;

final class UsageSummary
{
    /**
     * @param  list<array<string, mixed>>  $records
     * @return array{
     *     llm_calls: int,
     *     input_tokens: int,
     *     output_tokens: int,
     *     total_tokens: int,
     *     avg_input_tokens: float,
     *     avg_output_tokens: float,
     *     avg_total_tokens: float,
     *     tool_calls: int,
     *     total_tool_duration_ms: int,
     *     avg_tool_duration_ms: float|null
     * }
     */
    public static function fromRecords(array $records): array
    {
        $llmCalls = 0;
        $inputTokens = 0;
        $outputTokens = 0;
        $totalTokens = 0;
        $toolCalls = 0;
        $toolDurationMs = 0;

        foreach ($records as $record) {
            if (($record['type'] ?? null) === 'llm') {
                $llmCalls++;
                $inputTokens += (int) ($record['input_tokens'] ?? 0);
                $outputTokens += (int) ($record['output_tokens'] ?? 0);
                $totalTokens += (int) ($record['total_tokens'] ?? 0);

                continue;
            }

            if (($record['type'] ?? null) === 'tool') {
                $toolCalls++;
                $toolDurationMs += (int) ($record['duration_ms'] ?? 0);
            }
        }

        return [
            'llm_calls' => $llmCalls,
            'input_tokens' => $inputTokens,
            'output_tokens' => $outputTokens,
            'total_tokens' => $totalTokens,
            'avg_input_tokens' => self::average($inputTokens, $llmCalls),
            'avg_output_tokens' => self::average($outputTokens, $llmCalls),
            'avg_total_tokens' => self::average($totalTokens, $llmCalls),
            'tool_calls' => $toolCalls,
            'total_tool_duration_ms' => $toolDurationMs,
            'avg_tool_duration_ms' => $toolCalls > 0 ? round($toolDurationMs / $toolCalls, 2) : null,
        ];
    }

    protected static function average(int $total, int $count): float
    {
        if ($count === 0) {
            return 0.0;
        }

        return round($total / $count, 2);
    }
}
