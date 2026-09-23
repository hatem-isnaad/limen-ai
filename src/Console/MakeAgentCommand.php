<?php

namespace LimenAi\Console;

use Illuminate\Console\Command;
use LimenAi\Console\Concerns\InteractsWithGeneratorNames;

class MakeAgentCommand extends Command
{
    use InteractsWithGeneratorNames;

    protected $signature = 'limen-ai:make:agent
                            {name : The agent key or name}
                            {--tools=* : Tool keys available to the agent}';

    protected $description = 'Create a new Limen AI agent config stub';

    public function handle(StubGenerator $generator): int
    {
        $key = $this->snakeKey($this->argument('name'));
        $title = $this->studlyName($key);
        $tools = $this->option('tools') ?: ['example_echo'];
        $toolsExport = "[\n            '".implode("',\n            '", $tools)."',\n        ]";
        $targetPath = config('limen-ai.paths.agents', app_path('LimenAi/Agents')).'/'.$key.'.php';

        try {
            $generator->generate('agent-config.stub', [
                'key' => $key,
                'title' => $title,
                'tools' => $toolsExport,
            ], $targetPath);
        } catch (\Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info("Agent config stub [{$key}] created successfully.");
        $this->line("Path: {$targetPath}");
        $this->newLine();
        $this->line('Merge the returned array into config/limen-ai.php under agents, or require the file from your config.');

        return self::SUCCESS;
    }
}
