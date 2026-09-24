<?php

namespace LimenAi\Contracts\Observability;

interface UsageReader
{
    /** @return list<array<string, mixed>> */
    public function recordsForRun(string $runId): array;

    /**
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
    public function summarizeForRun(string $runId): array;

    /**
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
    public function summarize(): array;
}
