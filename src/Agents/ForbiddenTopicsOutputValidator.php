<?php

namespace LimenAi\Agents;

use LimenAi\Contracts\Agents\AgentDefinition;
use LimenAi\Contracts\Agents\OutputValidator;
use LimenAi\Exceptions\OutputValidationException;

class ForbiddenTopicsOutputValidator implements OutputValidator
{
    public function validate(AgentDefinition $agent, string $content): string
    {
        $persona = $agent->personaConfig();
        $topics = array_merge(
            is_array($persona['forbidden'] ?? null) ? $persona['forbidden'] : [],
            is_array($persona['forbidden_topics'] ?? null) ? $persona['forbidden_topics'] : [],
        );

        $haystack = mb_strtolower($content);

        foreach ($topics as $topic) {
            if (! is_string($topic) || trim($topic) === '') {
                continue;
            }

            if (str_contains($haystack, mb_strtolower($topic))) {
                throw OutputValidationException::forAgent(
                    $agent->key(),
                    'assistant reply referenced a forbidden topic',
                );
            }
        }

        return $content;
    }
}
