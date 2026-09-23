<?php

namespace LimenAi\Security;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use LimenAi\Contracts\Agents\AgentDefinition;
use LimenAi\Contracts\Agents\OutputModerator;

class BasicOutputModerator implements OutputModerator
{
    public function __construct(
        private readonly ConfigRepository $config,
    ) {}

    public function moderate(AgentDefinition $agent, string $content): string
    {
        if ($content === '') {
            return $content;
        }

        $patterns = $this->config->get('limen-ai.security.output_moderation.patterns', []);

        if (! is_array($patterns) || $patterns === []) {
            return $content;
        }

        foreach ($patterns as $pattern) {
            if (! is_string($pattern) || $pattern === '') {
                continue;
            }

            $content = (string) preg_replace($pattern, '[redacted]', $content);
        }

        return $content;
    }
}
