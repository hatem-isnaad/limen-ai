<?php

namespace LimenAi\Agents;

use LimenAi\Contracts\Agents\AgentDefinition;
use LimenAi\Contracts\Agents\OutputModerator;
use LimenAi\Contracts\Agents\OutputValidator;

class AgentResponseGuard
{
    public function __construct(
        private readonly OutputValidator $outputValidator,
        private readonly OutputModerator $outputModerator,
    ) {}

    public function apply(AgentDefinition $agent, string $content): string
    {
        $content = $this->outputValidator->validate($agent, $content);
        $content = $this->outputModerator->moderate($agent, $content);

        $output = $agent->outputConfig();
        $maxChars = (int) ($output['max_response_chars'] ?? 0);

        if ($maxChars > 0 && mb_strlen($content) > $maxChars) {
            $content = mb_substr($content, 0, $maxChars - 1).'…';
        }

        return $content;
    }
}
