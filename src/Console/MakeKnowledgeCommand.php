<?php

namespace LimenAi\Console;

use Illuminate\Console\Command;
use LimenAi\Console\Concerns\InteractsWithGeneratorNames;

class MakeKnowledgeCommand extends Command
{
    use InteractsWithGeneratorNames;

    protected $signature = 'limen-ai:make:knowledge
                            {name : The knowledge retriever class name}';

    protected $description = 'Create a custom Limen AI knowledge retriever';

    public function handle(StubGenerator $generator): int
    {
        $studly = $this->studlyName($this->argument('name'));
        $class = str_ends_with($studly, 'KnowledgeRetriever') ? $studly : $studly.'KnowledgeRetriever';
        $namespace = 'App\\LimenAi\\Knowledge';
        $targetPath = config('limen-ai.paths.knowledge', app_path('LimenAi/Knowledge')).'/'.$class.'.php';

        try {
            $generator->generate('knowledge-handler.stub', [
                'namespace' => $namespace,
                'class' => $class,
            ], $targetPath);
        } catch (\Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info("Knowledge retriever [{$class}] created successfully.");
        $this->line("Path: {$targetPath}");
        $this->newLine();
        $this->line('Register the class in config/limen-ai.php under knowledge.retriever when ready.');

        return self::SUCCESS;
    }
}
