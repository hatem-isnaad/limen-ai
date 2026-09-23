<?php

namespace LimenAi\Agents;

use LimenAi\Contracts\Agents\AgentDefinition;
use LimenAi\Contracts\Skills\SkillDefinition;

class InstructionComposer
{
    public function __construct(
        private readonly AgentPersonaComposer $personaComposer,
    ) {}

    /**
     * @param  list<SkillDefinition>  $skills
     */
    public function compose(AgentDefinition $agent, array $skills): string
    {
        $sections = [];

        $persona = trim($this->personaComposer->composeStatic($agent));

        if ($persona !== '') {
            $sections[] = "# Persona\n".$persona;
        }

        $instructions = trim($agent->instructions());

        if ($instructions !== '') {
            $sections[] = "# Instructions\n".$instructions;
        }

        foreach ($skills as $skill) {
            $skillInstructions = trim($skill->instructions());

            if ($skillInstructions !== '') {
                $sections[] = "## Skill: {$skill->name()}\n{$skillInstructions}";
            }
        }

        return trim(implode("\n\n", $sections));
    }
}
