<?php

namespace LimenAi\Console;

use Illuminate\Console\Command;
use LimenAi\Console\Concerns\InteractsWithGeneratorNames;

class MakeToolCommand extends Command
{
    use InteractsWithGeneratorNames;

    protected $signature = 'limen-ai:make:tool
                            {name : The tool class name}
                            {--key= : Config key for the tool}
                            {--test : Generate a feature test stub}';

    protected $description = 'Create a new Limen AI tool class';

    public function handle(StubGenerator $generator): int
    {
        $studly = $this->studlyName($this->argument('name'));
        $class = str_ends_with($studly, 'Tool') ? $studly : $studly.'Tool';
        $key = $this->option('key') ?: $this->snakeKey($class);
        $namespace = 'App\\LimenAi\\Tools';
        $targetPath = config('limen-ai.paths.tools', app_path('LimenAi/Tools')).'/'.$class.'.php';

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
        $this->newLine();
        $this->line('Add to config/limen-ai.php under tools:');
        $this->line($this->toolConfigSnippet($key, $namespace.'\\'.$class));

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
        'confirmation' => false,
        'timeout' => 10,
        'version' => '1.0.0',
    ],
PHP;
    }
}
