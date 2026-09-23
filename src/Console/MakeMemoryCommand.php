<?php

namespace LimenAi\Console;

use Illuminate\Console\Command;
use LimenAi\Console\Concerns\InteractsWithGeneratorNames;

class MakeMemoryCommand extends Command
{
    use InteractsWithGeneratorNames;

    protected $signature = 'limen-ai:make:memory
                            {name : The memory store class name}';

    protected $description = 'Create a custom Limen AI memory store implementation';

    public function handle(StubGenerator $generator): int
    {
        $studly = $this->studlyName($this->argument('name'));
        $class = str_ends_with($studly, 'MemoryStore') ? $studly : $studly.'MemoryStore';
        $namespace = 'App\\LimenAi\\Memory';
        $targetPath = config('limen-ai.paths.memory', app_path('LimenAi/Memory')).'/'.$class.'.php';

        try {
            $generator->generate('memory-store.stub', [
                'namespace' => $namespace,
                'class' => $class,
            ], $targetPath);
        } catch (\Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info("Memory store [{$class}] created successfully.");
        $this->line("Path: {$targetPath}");
        $this->newLine();
        $this->line('Point config/limen-ai.php memory.store to the new class when ready.');

        return self::SUCCESS;
    }
}
