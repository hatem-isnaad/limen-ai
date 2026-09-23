<?php

namespace LimenAi\Agents;

use LimenAi\Contracts\Agents\AgentDefinition;
use LimenAi\Contracts\Skills\SkillDefinition;
use LimenAi\Contracts\Tools\ToolDefinition;

class InstructionComposer
{
    public function __construct(private readonly AgentPersonaComposer $personaComposer) {}

    /**
     * @param  list<SkillDefinition>  $skills
     * @param  list<ToolDefinition>  $activeTools
     */
    public function compose(AgentDefinition $agent, array $skills, array $activeTools = []): string
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

        if ($activeTools !== []) {
            $toolKeys = implode(', ', array_map(fn ($tool) => $tool->key(), $activeTools));
            $sections[] = "# Active tools this turn\n{$toolKeys}";
        }

        return trim(implode("\n\n", $sections));
    }
}
