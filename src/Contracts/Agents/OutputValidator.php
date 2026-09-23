<?php

namespace LimenAi\Contracts\Agents;

interface OutputValidator
{
    public function validate(AgentDefinition $agent, string $content): string;
}
