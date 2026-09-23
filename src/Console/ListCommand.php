<?php

namespace LimenAi\Console;

use Illuminate\Console\Command;
use LimenAi\Contracts\Agents\AgentRepository;
use LimenAi\Contracts\Skills\SkillRepository;
use LimenAi\Contracts\Tools\ToolRepository;
use LimenAi\Contracts\Workflows\WorkflowRepository;

class ListCommand extends Command
{
    protected $signature = 'limen-ai:list';

    protected $description = 'List registered Limen AI agents, tools, skills, and workflows';

    public function handle(
        AgentRepository $agents,
        ToolRepository $tools,
        SkillRepository $skills,
        WorkflowRepository $workflows,
    ): int {
        $this->components->info('Agents');
        foreach ($agents->all() as $agent) {
            $this->line("- {$agent->key()}: {$agent->name()}");
        }

        $this->newLine();
        $this->components->info('Tools');
        foreach ($tools->all() as $tool) {
            $this->line("- {$tool->key()}: {$tool->name()}");
        }

        $this->newLine();
        $this->components->info('Skills');
        foreach ($skills->all() as $skill) {
            $this->line("- {$skill->key()}: {$skill->name()}");
        }

        $this->newLine();
        $this->components->info('Workflows');
        foreach ($workflows->all() as $workflow) {
            $this->line("- {$workflow->key()}: {$workflow->name()}");
        }

        return self::SUCCESS;
    }
}
