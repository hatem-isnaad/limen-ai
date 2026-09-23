<?php

namespace LimenAi\Console\Concerns;

use LimenAi\Contracts\Agents\AgentDefinition;
use LimenAi\Contracts\Skills\SkillDefinition;
use LimenAi\Contracts\Tools\ToolDefinition;
use LimenAi\Contracts\Workflows\WorkflowDefinition;

trait ListsRegisteredComponents
{
    protected function renderAgentLine(AgentDefinition $agent): void
    {
        $this->line("- {$agent->key()}: {$agent->name()}");
    }

    protected function renderToolLine(ToolDefinition $tool): void
    {
        $this->line("- {$tool->key()}: {$tool->name()}");
    }

    protected function renderSkillLine(SkillDefinition $skill): void
    {
        $this->line("- {$skill->key()}: {$skill->name()}");
    }

    protected function renderWorkflowLine(WorkflowDefinition $workflow): void
    {
        $this->line("- {$workflow->key()}: {$workflow->name()}");
    }
}
