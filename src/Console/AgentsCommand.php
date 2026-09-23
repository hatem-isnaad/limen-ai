<?php

namespace LimenAi\Console;

use Illuminate\Console\Command;
use LimenAi\Agents\AgentProfilePresenter;
use LimenAi\Console\Concerns\ListsRegisteredComponents;
use LimenAi\Contracts\Agents\AgentRepository;

class AgentsCommand extends Command
{
    use ListsRegisteredComponents;

    protected $signature = 'limen-ai:agents';

    protected $description = 'List registered Limen AI agents';

    public function handle(AgentRepository $agents, AgentProfilePresenter $profiles): int
    {
        foreach ($agents->all() as $agent) {
            $profile = $profiles->present($agent);
            $persona = $profile['persona'];

            $this->line(sprintf(
                '- %s: %s | model=%s (%s) | tone=%s | gender=%s | region=%s | language=%s',
                $agent->key(),
                $agent->name(),
                $agent->model(),
                $agent->provider(),
                $persona['tone'],
                $persona['gender'],
                $persona['region'],
                $persona['language'],
            ));
        }

        return self::SUCCESS;
    }
}
