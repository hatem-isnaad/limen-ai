<?php

namespace LimenAi\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class InstallCommand extends Command
{
    protected $signature = 'limen-ai:install
                            {--force : Overwrite published files}
                            {--with-ui : Publish chat UI assets}
                            {--with-example : Publish host service provider example}';

    protected $description = 'Install Limen AI: publish config, stubs, env guide, and definition folders';

    public function handle(Filesystem $files): int
    {
        $this->components->info('Installing Limen AI...');

        $this->call('vendor:publish', [
            '--tag' => 'limen-ai-config',
            '--force' => $this->option('force'),
        ]);

        $this->call('vendor:publish', [
            '--tag' => 'limen-ai-stubs',
            '--force' => $this->option('force'),
        ]);

        if ($this->option('with-ui')) {
            $this->call('vendor:publish', [
                '--tag' => 'limen-ai-ui',
                '--force' => $this->option('force'),
            ]);
        }

        foreach (['agents', 'tools', 'skills', 'workflows', 'knowledge'] as $section) {
            $directory = config_path('limen-ai/'.$section);

            if (! $files->isDirectory($directory)) {
                $files->makeDirectory($directory, 0755, true);
            }
        }

        $envExample = base_path('.env.limen-ai.example');
        $stubPath = dirname(__DIR__, 2).'/stubs/env.limen-ai.example';

        if ($files->exists($stubPath) && (! $files->exists($envExample) || $this->option('force'))) {
            $files->copy($stubPath, $envExample);
            $this->components->twoColumnDetail('Env guide', '.env.limen-ai.example');
        }

        if ($this->option('with-example')) {
            $target = app_path('Providers/LimenAiHostServiceProvider.php');
            $providerStub = dirname(__DIR__, 2).'/stubs/host-service-provider.stub';

            if ($files->exists($providerStub) && (! $files->exists($target) || $this->option('force'))) {
                $files->copy($providerStub, $target);
                $this->components->twoColumnDetail('Host provider', 'app/Providers/LimenAiHostServiceProvider.php');
                $this->components->warn('Register LimenAiHostServiceProvider in bootstrap/providers.php.');
            }
        }

        $this->newLine();
        $this->components->info('Next steps');
        $this->line('1. php artisan migrate');
        $this->line('2. Copy vars from .env.limen-ai.example into .env');
        $this->line('3. php artisan limen-ai:make:tool MyTool --register');
        $this->line('4. Register FAQ/KB in AppServiceProvider using LimenAi::faq(...)');
        $this->line('5. php artisan limen-ai:doctor');

        return self::SUCCESS;
    }
}
