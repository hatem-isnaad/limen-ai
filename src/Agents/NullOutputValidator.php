<?php

namespace LimenAi\Agents;

use LimenAi\Contracts\Agents\AgentDefinition;
use LimenAi\Contracts\Agents\OutputValidator;

class NullOutputValidator implements OutputValidator
{
    public function validate(AgentDefinition $agent, string $content): string
    {
        return $content;
    }
}
