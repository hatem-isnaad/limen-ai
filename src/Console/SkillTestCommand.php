<?php

namespace LimenAi\Console;

use Illuminate\Console\Command;
use LimenAi\Agents\InstructionComposer;
use LimenAi\Contracts\Agents\AgentRepository;
use LimenAi\Contracts\Skills\SkillRepository;

class SkillTestCommand extends Command
{
    protected $signature = 'limen-ai:skill:test
                            {skill : The skill key to inspect}
                            {--agent= : Show full composed instructions for an agent including this skill}';

    protected $description = 'Preview a skill instruction block and optional agent composition';

    public function handle(
        SkillRepository $skills,
        AgentRepository $agents,
        InstructionComposer $instructionComposer,
    ): int {
        $skillKey = (string) $this->argument('skill');
        $skill = $skills->find($skillKey);

        if ($skill === null) {
            $this->components->error("Skill [{$skillKey}] is not registered.");

            return self::FAILURE;
        }

        $this->components->twoColumnDetail('Skill', $skill->key());
        $this->components->twoColumnDetail('Name', $skill->name());
        $this->components->twoColumnDetail('Version', $skill->version());
        $this->newLine();

        $this->components->info('Instructions');
        $this->line($skill->instructions());
        $this->newLine();

        $this->components->info('Allowed tools');
        foreach ($skill->allowedTools() as $toolKey) {
            $this->line("- {$toolKey}");
        }

        $agentKey = (string) $this->option('agent');

        if ($agentKey === '') {
            return self::SUCCESS;
        }

        $agent = $agents->find($agentKey);

        if ($agent === null) {
            $this->components->error("Agent [{$agentKey}] is not registered.");

            return self::FAILURE;
        }

        if (! in_array($skillKey, $agent->skills(), true)) {
            $this->components->warn("Agent [{$agentKey}] does not include skill [{$skillKey}] in its config.");
        }

        $this->newLine();
        $this->components->info("Composed instructions for agent [{$agentKey}]");
        $this->line($instructionComposer->compose($agent, $skills->forAgent($agentKey)));

        return self::SUCCESS;
    }
}
