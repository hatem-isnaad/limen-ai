<?php

namespace LimenAi;

use Illuminate\Support\ServiceProvider;
use LimenAi\Agents\ConfigAgentRepository;
use LimenAi\Contracts\Agents\AgentRepository;
use LimenAi\Contracts\Knowledge\KnowledgeRepository;
use LimenAi\Contracts\Skills\SkillRepository;
use LimenAi\Contracts\Tools\ToolRepository;
use LimenAi\Contracts\Workflows\WorkflowRepository;
use LimenAi\Knowledge\ConfigKnowledgeRepository;
use LimenAi\Skills\ConfigSkillRepository;
use LimenAi\Tools\ConfigToolRepository;
use LimenAi\Workflows\ConfigWorkflowRepository;

class LimenAiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/limen-ai.php', 'limen-ai');

        $this->registerRepositories();
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

    protected function registerRepositories(): void
    {
        $repositories = $this->app['config']->get('limen-ai.repositories', []);

        $this->app->singleton(AgentRepository::class, $repositories['agent'] ?? ConfigAgentRepository::class);
        $this->app->singleton(ToolRepository::class, $repositories['tool'] ?? ConfigToolRepository::class);
        $this->app->singleton(SkillRepository::class, $repositories['skill'] ?? ConfigSkillRepository::class);
        $this->app->singleton(WorkflowRepository::class, $repositories['workflow'] ?? ConfigWorkflowRepository::class);
        $this->app->singleton(KnowledgeRepository::class, $repositories['knowledge'] ?? ConfigKnowledgeRepository::class);
    }
}
