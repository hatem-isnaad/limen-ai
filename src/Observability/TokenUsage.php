<?php

namespace LimenAi\Observability;

final class TokenUsage
{
    /**
     * @param  array<string, mixed>  $usage
     * @return array{input_tokens: int, output_tokens: int, total_tokens: int}
     */
    public static function normalize(array $usage): array
    {
        $input = (int) ($usage['input_tokens']
            ?? $usage['prompt_tokens']
            ?? $usage['promptTokenCount']
            ?? 0);

        $output = (int) ($usage['output_tokens']
            ?? $usage['completion_tokens']
            ?? $usage['candidatesTokenCount']
            ?? 0);

        $total = (int) ($usage['total_tokens']
            ?? $usage['totalTokenCount']
            ?? ($input + $output));

        return [
            'input_tokens' => max(0, $input),
            'output_tokens' => max(0, $output),
            'total_tokens' => max(0, $total),
        ];
    }
}
