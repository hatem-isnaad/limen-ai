<?php

namespace LimenAi\Console;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'limen-ai:install
                            {--force : Overwrite existing published files}
                            {--migrate : Run Limen AI database migrations after publishing}';

    protected $description = 'Publish Limen AI config, env example, views, assets, and stubs';

    public function handle(): int
    {
        $this->components->info('Publishing Limen AI package assets...');

        $tags = [
            'limen-ai-config',
            'limen-ai-knowledge',
            'limen-ai-env',
            'limen-ai-stubs',
            'limen-ai-ui',
        ];

        foreach ($tags as $tag) {
            $this->call('vendor:publish', array_filter([
                '--tag' => $tag,
                '--force' => $this->option('force') ?: null,
            ]));
        }

        if ($this->option('migrate')) {
            $this->components->info('Running Limen AI migrations...');
            $this->call('migrate', ['--force' => true]);
        }

        $this->newLine();
        $this->components->info('Limen AI installed successfully.');
        $this->line('Next steps:');
        $this->line('  1. Copy variables from .env.limen-ai.example into your .env');
        $this->line('  2. php artisan migrate'.($this->option('migrate') ? '  (already run with --migrate)' : ''));
        $this->line('  3. php artisan limen-ai:import:knowledge storage/faq.csv --collection=product_help  (optional)');
        $this->line('  4. php artisan limen-ai:doctor  (persistence auto-detects database after migrate)');
        $this->line('  5. php artisan limen-ai:validate');
        $this->line('  6. Enable LIMEN_AI_SEMANTIC_VALIDATION=true for LLM judge scoring (optional; works with Ollama)');
        $this->line('  7. <x-limen-ai::widget />  — guest widgets auto-apply rate limiting (no middleware publish needed)');

        return self::SUCCESS;
    }
}
