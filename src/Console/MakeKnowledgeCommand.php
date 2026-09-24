<?php

namespace LimenAi\Console;

use Illuminate\Console\Command;
use LimenAi\Console\Concerns\InteractsWithGeneratorNames;
use LimenAi\Support\ConfigFragmentWriter;

class MakeKnowledgeCommand extends Command
{
    use InteractsWithGeneratorNames;

    protected $signature = 'limen-ai:make:knowledge
                            {name : The knowledge collection key}
                            {--faq : Generate an FAQ collection stub}';

    protected $description = 'Create a knowledge or FAQ collection config fragment';

    public function handle(StubGenerator $generator, ConfigFragmentWriter $writer): int
    {
        $key = $this->snakeKey($this->argument('name'));
        $name = str($key)->headline()->toString();

        if ($this->option('faq')) {
            $payload = [
                'name' => $name,
                'description' => 'FAQ knowledge for '.$name,
                'documents' => [
                    [
                        'type' => 'faq',
                        'question' => 'What can this assistant help with?',
                        'answer' => 'Describe your product support scope here.',
                        'content' => 'Q: What can this assistant help with?'."\n".'A: Describe your product support scope here.',
                        'metadata' => ['source' => 'faq'],
                    ],
                ],
            ];
        } else {
            $payload = [
                'name' => $name,
                'description' => 'Knowledge collection for '.$name,
                'documents' => [
                    [
                        'content' => 'Add product or business knowledge here.',
                        'metadata' => ['source' => 'manual'],
                    ],
                ],
            ];
        }

        $path = $writer->write('knowledge', $key, $payload);

        $this->components->info("Knowledge collection [{$key}] created.");
        $this->line("Path: {$path}");
        $this->newLine();
        $this->line("Attach to an agent in config/limen-ai.php under agents.*.knowledge or runtime:");
        $this->line("LimenAi::knowledge('{$key}', [...]);");

        return self::SUCCESS;
    }
}
