<?php

namespace LimenAi\Console;

use Illuminate\Console\Command;
use LimenAi\Console\Concerns\InteractsWithGeneratorNames;
use LimenAi\Support\ConfigFragmentWriter;

class MakeToolCommand extends Command
{
    use InteractsWithGeneratorNames;

    protected $signature = 'limen-ai:make:tool
                            {name : The tool class name}
                            {--key= : Config key for the tool}
                            {--register : Write config/limen-ai/tools/{key}.php automatically}
                            {--test : Generate a feature test stub}';

    protected $description = 'Create a new Limen AI tool class';

    public function handle(StubGenerator $generator, ConfigFragmentWriter $writer): int
    {
        $studly = $this->studlyName($this->argument('name'));
        $class = str_ends_with($studly, 'Tool') ? $studly : $studly.'Tool';
        $key = $this->option('key') ?: $this->snakeKey($class);
        $namespace = (string) config('limen-ai.paths.tool_namespace');

        if ($namespace === '') {
            $namespace = 'App'.'\\Ai\\Tools';
        }
        $fqn = $namespace.'\\'.$class;
        $targetPath = config('limen-ai.paths.tools', app_path('Ai/Tools')).'/'.$class.'.php';

        try {
            $generator->generate('tool.stub', [
                'namespace' => $namespace,
                'class' => $class,
                'key' => $key,
            ], $targetPath);
        } catch (\Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info("Tool [{$class}] created successfully.");
        $this->line("Path: {$targetPath}");

        $configPayload = $this->toolConfigArray($key, $fqn);

        if ($this->option('register')) {
            $configPath = $writer->write('tools', $key, $configPayload);
            $this->components->info("Registered tool config at {$configPath}");
        } else {
            $this->newLine();
            $this->line('Add to config/limen-ai/tools/'.$key.'.php or use --register:');
            $this->line($this->toolConfigSnippet($key, $fqn));
        }

        if ($this->option('test')) {
            $testPath = base_path('tests/Feature/LimenAi/Tools/'.$class.'Test.php');
            $generator->generate('tool-test.stub', [
                'namespace' => 'Tests\\Feature\\LimenAi\\Tools',
                'class' => $class,
                'key' => $key,
            ], $testPath);

            $this->components->info("Test [{$class}Test] created.");
            $this->line("Path: {$testPath}");
        }

        return self::SUCCESS;
    }

    /** @return array<string, mixed> */
    protected function toolConfigArray(string $key, string $class): array
    {
        return [
            'name' => str($key)->headline()->toString(),
            'description' => 'Describe what this tool does.',
            'class' => $class,
            'input_schema' => [
                'message' => ['type' => 'string', 'required' => true],
            ],
            'authorization' => [
                'abilities' => [],
            ],
            'confirmation' => false,
            'timeout' => 10,
            'version' => '1.0.0',
        ];
    }

    protected function toolConfigSnippet(string $key, string $class): string
    {
        return <<<PHP
    '{$key}' => [
        'name' => '{$this->studlyName($key)}',
        'description' => 'Describe what this tool does.',
        'class' => {$class}::class,
        'input_schema' => [
            'message' => ['type' => 'string', 'required' => true],
        ],
        'authorization' => [
            'abilities' => [],
        ],
        'confirmation' => false,
        'timeout' => 10,
        'version' => '1.0.0',
    ],
PHP;
    }
}
