<?php

namespace LimenAi\Console;

use Illuminate\Console\Command;
use LimenAi\Console\Concerns\InteractsWithGeneratorNames;

class MakeAgentCommand extends Command
{
    use InteractsWithGeneratorNames;

    /** @var array<int, string> */
    protected $aliases = ['make:agent'];

    protected $signature = 'limen-ai:make:agent
                            {name : The agent name}
                            {--tools=* : Tool class names or config tool keys}
                            {--config : Generate a legacy config stub instead of a PHP agent class}
                            {--structured : Generate agent with HasStructuredOutput}';

    protected $description = 'Create a new Limen AI agent class (Laravel AI SDK style)';

    public function handle(StubGenerator $generator): int
    {
        $key = $this->snakeKey($this->argument('name'));
        $title = $this->studlyName($key);

        if ($this->option('config')) {
            return $this->generateConfigStub($generator, $key, $title);
        }

        return $this->generateClassStub($generator, $key, $title);
    }

    protected function generateConfigStub(StubGenerator $generator, string $key, string $title): int
    {
        $tools = $this->option('tools') ?: ['example_echo'];
        $toolsExport = "[\n            '".implode("',\n            '", $tools)."',\n        ]";
        $targetPath = config('limen-ai.paths.agent_config', config_path('limen-ai/agents')).'/'.$key.'.php';

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

        $this->components->info("Legacy agent config stub [{$key}] created.");
        $this->line("Path: {$targetPath}");

        return self::SUCCESS;
    }

    protected function generateClassStub(StubGenerator $generator, string $key, string $title): int
    {
        $namespace = (string) config('limen-ai.paths.agent_namespace');

        if ($namespace === '') {
            $namespace = 'App'.'\\Ai\\Agents';
        }
        $directory = (string) config('limen-ai.paths.agent_classes', app_path('Ai/Agents'));
        $tools = $this->option('tools') ?: ['\\LimenAi\\Tools\\BuiltIn\\ExampleEchoTool::class'];
        $toolsExport = implode(",\n            ", array_map(
            fn (string $tool): string => str_contains($tool, '\\') ? $tool : "'{$tool}'",
            $tools,
        ));

        $targetPath = $directory.'/'.$title.'.php';

        try {
            $stub = $this->option('structured') ? 'agent-class-structured.stub' : 'agent-class.stub';

            $generator->generate($stub, [
                'namespace' => $namespace,
                'class' => $title,
                'key' => $key,
                'tools' => $toolsExport,
            ], $targetPath);
        } catch (\Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info("Agent class [{$title}] created successfully.");
        $this->line("Path: {$targetPath}");
        $this->newLine();
        $this->line("Register the agent in config/limen-ai.php under agent_classes:");
        $this->line("    '{$key}' => \\{$namespace}\\{$title}::class,");

        return self::SUCCESS;
    }
}
