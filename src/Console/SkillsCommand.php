<?php

namespace LimenAi\Console;

use Illuminate\Console\Command;
use LimenAi\Console\Concerns\ListsRegisteredComponents;
use LimenAi\Contracts\Skills\SkillRepository;

class SkillsCommand extends Command
{
    use ListsRegisteredComponents;

    protected $signature = 'limen-ai:skills';

    protected $description = 'List registered Limen AI skills';

    public function handle(SkillRepository $skills): int
    {
        foreach ($skills->all() as $skill) {
            $this->renderSkillLine($skill);
        }

        return self::SUCCESS;
    }
}
