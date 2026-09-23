<?php

namespace LimenAi\Agents;

use LimenAi\Contracts\Agents\AgentDefinition;
use LimenAi\Contracts\Skills\SkillDefinition;

class InstructionComposer
{
    /**
     * @param  list<SkillDefinition>  $skills
     */
    public function compose(AgentDefinition $agent, array $skills): string
    {
        $sections = [trim($agent->instructions())];

        foreach ($skills as $skill) {
            $instructions = trim($skill->instructions());

            if ($instructions !== '') {
                $sections[] = "## Skill: {$skill->name()}\n{$instructions}";
            }
        }

        return trim(implode("\n\n", array_filter($sections)));
    }
}
