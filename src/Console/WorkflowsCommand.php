<?php

namespace LimenAi\Console;

use Illuminate\Console\Command;
use LimenAi\Console\Concerns\ListsRegisteredComponents;
use LimenAi\Contracts\Workflows\WorkflowRepository;

class WorkflowsCommand extends Command
{
    use ListsRegisteredComponents;

    protected $signature = 'limen-ai:workflows';

    protected $description = 'List registered Limen AI workflows';

    public function handle(WorkflowRepository $workflows): int
    {
        foreach ($workflows->all() as $workflow) {
            $this->renderWorkflowLine($workflow);
        }

        return self::SUCCESS;
    }
}
