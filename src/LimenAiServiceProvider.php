<?php

namespace LimenAi;

use Illuminate\Support\ServiceProvider;

class LimenAiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/limen-ai.php', 'limen-ai');

        // Phase 02+: bind repository and runtime contracts here.
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/limen-ai.php' => config_path('limen-ai.php'),
            ], 'limen-ai-config');

            // Phase 19+: register Artisan commands.
        }

        $this->loadTranslationsFrom(__DIR__.'/../lang', 'limen-ai');
    }
}
