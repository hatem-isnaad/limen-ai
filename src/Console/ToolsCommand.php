<?php

namespace LimenAi\Console;

use Illuminate\Console\Command;
use LimenAi\Console\Concerns\ListsRegisteredComponents;
use LimenAi\Contracts\Tools\ToolRepository;

class ToolsCommand extends Command
{
    use ListsRegisteredComponents;

    protected $signature = 'limen-ai:tools';

    protected $description = 'List registered Limen AI tools';

    public function handle(ToolRepository $tools): int
    {
        foreach ($tools->all() as $tool) {
            $this->renderToolLine($tool);
        }

        return self::SUCCESS;
    }
}
