<?php

namespace LimenAi\Agents;

use JsonException;
use LimenAi\Contracts\Agents\AgentDefinition;
use LimenAi\Contracts\Agents\OutputValidator;
use LimenAi\Exceptions\OutputValidationException;

class StructuredOutputValidator implements OutputValidator
{
    public function validate(AgentDefinition $agent, string $content): string
    {
        if (($agent->outputConfig()['format'] ?? 'text') !== 'json') {
            return $content;
        }

        $trimmed = trim($content);

        if ($trimmed === '') {
            throw OutputValidationException::forAgent($agent->key(), 'expected JSON output but received an empty response');
        }

        try {
            json_decode($trimmed, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw OutputValidationException::forAgent(
                $agent->key(),
                'expected valid JSON output ('.$exception->getMessage().')',
            );
        }

        return $content;
    }
}
