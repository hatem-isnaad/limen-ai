<?php

namespace LimenAi\Contracts\Agents;

interface OutputModerator
{
    public function moderate(AgentDefinition $agent, string $content): string;
}
