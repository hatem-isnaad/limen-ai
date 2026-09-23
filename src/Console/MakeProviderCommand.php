<?php

namespace LimenAi\Console;

use Illuminate\Console\Command;
use LimenAi\Console\Concerns\InteractsWithGeneratorNames;

class MakeProviderCommand extends Command
{
    use InteractsWithGeneratorNames;

    protected $signature = 'limen-ai:make:provider
                            {name : The provider class name}';

    protected $description = 'Create a custom Limen AI LLM provider adapter';

    public function handle(StubGenerator $generator): int
    {
        $studly = $this->studlyName($this->argument('name'));
        $class = str_ends_with($studly, 'Provider') ? $studly : $studly.'Provider';
        $namespace = 'App\\LimenAi\\Providers';
        $targetPath = config('limen-ai.paths.providers', app_path('LimenAi/Providers')).'/'.$class.'.php';

        try {
            $generator->generate('custom-llm-provider.stub', [
                'namespace' => $namespace,
                'class' => $class,
            ], $targetPath);
        } catch (\Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $driverKey = $this->snakeKey($class);
        $this->components->info("Provider [{$class}] created successfully.");
        $this->line("Path: {$targetPath}");
        $this->newLine();
        $this->line('Register the driver in config/limen-ai.php under providers.drivers:');
        $this->line($this->driverConfigSnippet($driverKey, $namespace.'\\'.$class));

        return self::SUCCESS;
    }

    protected function driverConfigSnippet(string $driverKey, string $class): string
    {
        return <<<PHP
    '{$driverKey}' => [
        'class' => {$class}::class,
    ],
PHP;
    }
}
