<?php

namespace LimenAi\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use LimenAi\Console\Concerns\InteractsWithGeneratorNames;

class MakeConnectorCommand extends Command
{
    use InteractsWithGeneratorNames;

    protected $signature = 'limen-ai:make:connector
                            {name : The connector key or name}';

    protected $description = 'Create a new Limen AI HTTP connector config stub';

    public function handle(StubGenerator $generator): int
    {
        $key = $this->snakeKey($this->argument('name'));
        $envPrefix = 'LIMEN_'.Str::upper(Str::snake($key));
        $targetPath = config('limen-ai.paths.connectors', app_path('LimenAi/Connectors')).'/'.$key.'.php';

        try {
            $generator->generate('connector-config.stub', [
                'key' => $key,
                'env_prefix' => $envPrefix,
            ], $targetPath);
        } catch (\Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info("Connector config stub [{$key}] created successfully.");
        $this->line("Path: {$targetPath}");
        $this->newLine();
        $this->line('Merge the returned array into config/limen-ai.php under integrations.connectors.');

        return self::SUCCESS;
    }
}
