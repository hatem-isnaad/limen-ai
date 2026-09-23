<?php

namespace LimenAi\Agents;

use LimenAi\Contracts\Agents\AgentDefinition;
use LimenAi\Contracts\Agents\OutputValidator;

class ChainedOutputValidator implements OutputValidator
{
    /** @param  list<OutputValidator>  $validators */
    public function __construct(
        private readonly array $validators,
    ) {}

    public function validate(AgentDefinition $agent, string $content): string
    {
        foreach ($this->validators as $validator) {
            $content = $validator->validate($agent, $content);
        }

        return $content;
    }
}
