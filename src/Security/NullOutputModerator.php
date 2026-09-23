<?php

namespace LimenAi\Security;

use LimenAi\Contracts\Agents\AgentDefinition;
use LimenAi\Contracts\Agents\OutputModerator;

class NullOutputModerator implements OutputModerator
{
    public function moderate(AgentDefinition $agent, string $content): string
    {
        return $content;
    }
}
