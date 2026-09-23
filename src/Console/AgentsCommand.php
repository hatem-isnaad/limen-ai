<?php

namespace LimenAi\Console;

use Illuminate\Console\Command;
use LimenAi\Console\Concerns\ListsRegisteredComponents;
use LimenAi\Contracts\Agents\AgentRepository;

class AgentsCommand extends Command
{
    use ListsRegisteredComponents;

    protected $signature = 'limen-ai:agents';

    protected $description = 'List registered Limen AI agents';

    public function handle(AgentRepository $agents): int
    {
        foreach ($agents->all() as $agent) {
            $this->renderAgentLine($agent);
        }

        return self::SUCCESS;
    }
}
