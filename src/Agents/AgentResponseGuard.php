<?php

namespace LimenAi\Agents;

use LimenAi\Contracts\Agents\AgentDefinition;

class AgentResponseGuard
{
    public function apply(AgentDefinition $agent, string $content): string
    {
        $output = $agent->outputConfig();
        $maxChars = (int) ($output['max_response_chars'] ?? 0);

        if ($maxChars > 0 && mb_strlen($content) > $maxChars) {
            $content = mb_substr($content, 0, $maxChars - 1).'…';
        }

        return $content;
    }
}
