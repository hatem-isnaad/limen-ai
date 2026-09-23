<?php

namespace LimenAi\Console;

use Illuminate\Console\Command;
use LimenAi\Console\Concerns\InteractsWithGeneratorNames;

class MakeWorkflowCommand extends Command
{
    use InteractsWithGeneratorNames;

    protected $signature = 'limen-ai:make:workflow
                            {name : The workflow key or name}
                            {--agent=example : Agent key for the first step}
                            {--start= : Start step key (defaults to workflow key)}';

    protected $description = 'Create a new Limen AI workflow config stub';

    public function handle(StubGenerator $generator): int
    {
        $key = $this->snakeKey($this->argument('name'));
        $title = $this->studlyName($key);
        $startStep = $this->option('start') ?: $key;
        $agent = (string) $this->option('agent');
        $targetPath = config('limen-ai.paths.workflows', app_path('LimenAi/Workflows')).'/'.$key.'.php';

        try {
            $generator->generate('workflow-config.stub', [
                'key' => $key,
                'title' => $title,
                'start_step' => $startStep,
                'agent' => $agent,
            ], $targetPath);
        } catch (\Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info("Workflow config stub [{$key}] created successfully.");
        $this->line("Path: {$targetPath}");
        $this->newLine();
        $this->line('Merge the returned array into config/limen-ai.php under workflows.');

        return self::SUCCESS;
    }
}
