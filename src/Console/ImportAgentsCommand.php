<?php

namespace LimenAi\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use LimenAi\Models\AgentDefinitionModel;
use LimenAi\Support\DefinitionLoader;

class ImportAgentsCommand extends Command
{
    protected $signature = 'limen-ai:agents:import-config
                            {--force : Overwrite existing database rows with matching keys}';

    protected $description = 'Import config-defined agents into the database agent definitions table';

    public function handle(DefinitionLoader $definitions): int
    {
        $table = (string) config('limen-ai.agent_storage.database.table', 'limen_ai_agent_definitions');
        $connection = config('limen-ai.agent_storage.database.connection');

        if (! Schema::connection($connection)->hasTable($table)) {
            $this->components->error("Table [{$table}] does not exist. Run migrations first.");

            return self::FAILURE;
        }

        $imported = 0;
        $skipped = 0;

        foreach ($definitions->agents() as $key => $definition) {
            if (! is_array($definition) || ! is_string($key)) {
                continue;
            }

            if (isset($definition['class']) && is_string($definition['class'])) {
                $this->components->warn("Skipping class-based agent [{$key}] — import class reference manually if needed.");

                continue;
            }

            $exists = AgentDefinitionModel::query()->where('key', $key)->exists();

            if ($exists && ! $this->option('force')) {
                $skipped++;

                continue;
            }

            AgentDefinitionModel::query()->updateOrCreate(
                ['key' => $key],
                $this->mapToModelAttributes($key, $definition),
            );

            $imported++;
        }

        $this->components->info("Imported {$imported} agent definition(s). Skipped {$skipped} existing row(s).");

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $definition
     * @return array<string, mixed>
     */
    protected function mapToModelAttributes(string $key, array $definition): array
    {
        return [
            'key' => $key,
            'name' => (string) ($definition['name'] ?? $key),
            'description' => isset($definition['description']) ? (string) $definition['description'] : null,
            'model' => (string) ($definition['model'] ?? ''),
            'provider' => (string) ($definition['provider'] ?? config('limen-ai.providers.default', 'fake')),
            'instructions' => (string) ($definition['instructions'] ?? ''),
            'skills' => array_values($definition['skills'] ?? []),
            'tools' => array_values($definition['tools'] ?? []),
            'knowledge' => array_values($definition['knowledge'] ?? []),
            'memory' => $definition['memory'] ?? [],
            'authorization' => $definition['authorization'] ?? [],
            'output' => $definition['output'] ?? [],
            'limits' => $definition['limits'] ?? [],
            'version' => (string) ($definition['version'] ?? '1.0.0'),
            'enabled' => (bool) ($definition['enabled'] ?? true),
        ];
    }
}
