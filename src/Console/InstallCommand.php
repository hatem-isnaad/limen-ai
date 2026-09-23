<?php

namespace LimenAi\Console;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'limen-ai:install
                            {--force : Overwrite existing published files}';

    protected $description = 'Publish Limen AI config, env example, views, assets, and stubs';

    public function handle(): int
    {
        $this->components->info('Publishing Limen AI package assets...');

        $tags = [
            'limen-ai-config',
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

        $this->newLine();
        $this->components->info('Limen AI installed successfully.');
        $this->line('Next steps:');
        $this->line('  1. Copy variables from .env.limen-ai.example into your .env');
        $this->line('  2. php artisan migrate');
        $this->line('  3. php artisan limen-ai:doctor');
        $this->line('  4. php artisan limen-ai:validate');

        return self::SUCCESS;
    }
}
