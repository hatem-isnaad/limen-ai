<?php

namespace LimenAi\Agents;

use LimenAi\Contracts\Agents\AgentDefinition;
use LimenAi\Contracts\Agents\OutputValidator;
use LimenAi\Exceptions\OutputValidationException;

class HeuristicOutputValidator implements OutputValidator
{
    /** @var list<string> */
    private array $promptLeakPatterns = [
        '/\[INST\]/i',
        '/<<SYS>>/i',
        '/<\|im_start\|>/i',
        '/# Persona\b/i',
        '/# Instructions\b/i',
    ];

    public function validate(AgentDefinition $agent, string $content): string
    {
        $trimmed = trim($content);

        if ($trimmed === '') {
            throw OutputValidationException::forAgent($agent->key(), 'assistant reply was empty');
        }

        foreach ($this->promptLeakPatterns as $pattern) {
            if (preg_match($pattern, $content) === 1) {
                throw OutputValidationException::forAgent($agent->key(), 'assistant reply appears to leak prompt markers');
            }
        }

        if (($agent->outputConfig()['format'] ?? 'text') === 'text' && $this->looksLikeJsonPayload($trimmed)) {
            throw OutputValidationException::forAgent($agent->key(), 'assistant reply looks like JSON but output format is text');
        }

        $persona = $agent->personaConfig();
        $style = (string) ($persona['response_style'] ?? '');

        if ($style === 'concise') {
            $maxSentences = max(1, (int) config('limen-ai.quality.heuristic_concise_max_sentences', 12));

            if ($this->sentenceCount($trimmed) > $maxSentences) {
                throw OutputValidationException::forAgent(
                    $agent->key(),
                    "assistant reply exceeds {$maxSentences} sentences for concise response style",
                );
            }
        }

        return $content;
    }

    protected function looksLikeJsonPayload(string $content): bool
    {
        if (! str_starts_with($content, '{') && ! str_starts_with($content, '[')) {
            return false;
        }

        json_decode($content);

        return json_last_error() === JSON_ERROR_NONE;
    }

    protected function sentenceCount(string $content): int
    {
        $normalized = preg_replace('/\s+/u', ' ', $content) ?? $content;
        $parts = preg_split('/(?<=[.!?؟])\s+/u', trim($normalized), -1, PREG_SPLIT_NO_EMPTY);

        return max(1, count($parts ?: [trim($normalized)]));
    }
}
