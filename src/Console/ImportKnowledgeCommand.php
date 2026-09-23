<?php

namespace LimenAi\Console;

use Illuminate\Console\Command;
use LimenAi\Support\KnowledgeCollectionImporter;
use LimenAi\Support\KnowledgeConfigWriter;

class ImportKnowledgeCommand extends Command
{
    protected $signature = 'limen-ai:import:knowledge
                            {file : Path to a JSON or CSV knowledge file}
                            {--collection=product_help : Collection key to write or update}
                            {--name= : Collection display name}
                            {--description= : Collection description}
                            {--append : Append documents to an existing collection instead of replacing them}';

    protected $description = 'Import FAQ documents from JSON or CSV into config/limen-ai-knowledge.php';

    public function handle(
        KnowledgeCollectionImporter $importer,
        KnowledgeConfigWriter $writer,
    ): int {
        $file = (string) $this->argument('file');
        $collectionKey = (string) $this->option('collection');

        if ($collectionKey === '') {
            $this->components->error('Collection key cannot be empty.');

            return self::FAILURE;
        }

        if (! is_file($file)) {
            $this->components->error("File [{$file}] does not exist.");

            return self::FAILURE;
        }

        try {
            $imported = $importer->importFromFile($file);
        } catch (\Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $configPath = function_exists('config_path')
            ? config_path('limen-ai-knowledge.php')
            : 'config/limen-ai-knowledge.php';

        $collections = $writer->read($configPath);
        $existing = is_array($collections[$collectionKey] ?? null) ? $collections[$collectionKey] : [];

        $documents = $imported['documents'];

        if ($this->option('append') && isset($existing['documents']) && is_array($existing['documents'])) {
            $documents = array_merge($existing['documents'], $documents);
        }

        $collections[$collectionKey] = [
            'name' => (string) ($this->option('name')
                ?: ($existing['name'] ?? $imported['name'] ?? $collectionKey)),
            'description' => (string) ($this->option('description')
                ?: ($existing['description'] ?? $imported['description'] ?? '')),
            'documents' => $documents,
        ];

        try {
            $writer->write($configPath, $collections);
        } catch (\Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $count = count($documents);
        $this->components->info("Imported {$count} document(s) into collection [{$collectionKey}].");
        $this->line("Written to: {$configPath}");
        $this->newLine();
        $this->line('Attach the collection to an agent in config/limen-ai.php, for example:');
        $this->line("  'knowledge' => ['{$collectionKey}']");
        $this->line('Collections from limen-ai-knowledge.php are merged automatically on boot.');

        return self::SUCCESS;
    }
}
