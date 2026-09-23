<?php

namespace LimenAi;

use Illuminate\Support\ServiceProvider;
use LimenAi\Agents\AgentValidator;
use LimenAi\Agents\ConfigAgentRepository;
use LimenAi\Agents\DefaultAgentResolver;
use LimenAi\Agents\InstructionComposer;
use LimenAi\Authorization\CacheGuestSessionValidator;
use LimenAi\Authorization\DatabaseApprovalRepository;
use LimenAi\Authorization\InMemoryApprovalRepository;
use LimenAi\Authorization\LaravelAuthorizationService;
use LimenAi\Authorization\NullGuestSessionValidator;
use LimenAi\Console\ValidateCommand;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Broadcast;
use LimenAi\Broadcasting\AgentEventBroadcaster;
use LimenAi\Http\Services\ConversationAccessGuard;
use LimenAi\Broadcasting\NullBroadcaster;
use LimenAi\Broadcasting\PusherBroadcaster;
use LimenAi\Contracts\Agents\AgentRepository;
use LimenAi\Contracts\Agents\AgentResolver;
use LimenAi\Contracts\Broadcasting\RealtimeBroadcaster;
use LimenAi\Contracts\Authorization\ApprovalRepository;
use LimenAi\Contracts\Authorization\AuthorizationService;
use LimenAi\Contracts\Authorization\GuestSessionValidator;
use LimenAi\Contracts\Conversations\ConversationRepository;
use LimenAi\Contracts\Conversations\ConversationSummarizer;
use LimenAi\Contracts\Conversations\MessageRepository;
use LimenAi\Contracts\Integrations\HttpConnectorRepository;
use LimenAi\Contracts\Integrations\HttpToolExecutor;
use LimenAi\Contracts\Knowledge\AgentKnowledgeRetriever;
use LimenAi\Contracts\Security\ContentSanitizer;
use LimenAi\Contracts\Security\SecretResolver;
use LimenAi\Contracts\Security\UrlValidator;
use LimenAi\Contracts\Knowledge\KnowledgeRepository;
use LimenAi\Contracts\Knowledge\KnowledgeRetriever;
use LimenAi\Contracts\Knowledge\VectorStore;
use LimenAi\Contracts\Memory\MemoryRetriever;
use LimenAi\Contracts\Memory\MemoryStore;
use LimenAi\Contracts\Observability\AuditLogger;
use LimenAi\Contracts\Providers\EmbeddingProvider;
use LimenAi\Contracts\Providers\LlmProvider;
use LimenAi\Contracts\Skills\SkillRepository;
use LimenAi\Contracts\Tools\IdempotencyGuard;
use LimenAi\Contracts\Tools\ToolExecutor;
use LimenAi\Contracts\Runtime\AgentRunDispatcher;
use LimenAi\Contracts\Runtime\AgentRuntime;
use LimenAi\Contracts\Runtime\CheckpointStore;
use LimenAi\Contracts\Runtime\RunRepository;
use LimenAi\Contracts\Runtime\RunStatusReader;
use LimenAi\Contracts\Tools\ToolRepository;
use LimenAi\Contracts\Workflows\WorkflowEngine;
use LimenAi\Contracts\Workflows\WorkflowRepository;
use LimenAi\Conversations\ConversationService;
use LimenAi\Conversations\InMemoryConversationRepository;
use LimenAi\Conversations\InMemoryMessageRepository;
use LimenAi\Conversations\MessageFormatter;
use LimenAi\Conversations\NullConversationSummarizer;
use LimenAi\Runtime\ArrayCheckpointStore;
use LimenAi\Runtime\DatabaseCheckpointStore;
use LimenAi\Runtime\DatabaseRunRepository;
use LimenAi\Runtime\DefaultAgentRuntime;
use LimenAi\Runtime\DefaultRunStatusReader;
use LimenAi\Runtime\InMemoryRunRepository;
use LimenAi\Runtime\QueuedAgentRunDispatcher;
use LimenAi\Runtime\SyncAgentRunDispatcher;
use LimenAi\Runtime\ToolCallParser;
use LimenAi\Integrations\ConfigHttpConnectorRepository;
use LimenAi\Integrations\DeclarativeHttpToolExecutor;
use LimenAi\Integrations\HttpIntegrationValidator;
use LimenAi\Integrations\HttpRequestBuilder;
use LimenAi\Knowledge\ConfigKnowledgeRepository;
use LimenAi\Knowledge\ConfigKnowledgeRetriever;
use LimenAi\Knowledge\DefaultAgentKnowledgeRetriever;
use LimenAi\Knowledge\InMemoryVectorStore;
use LimenAi\Knowledge\KnowledgeFormatter;
use LimenAi\Knowledge\KnowledgeService;
use LimenAi\Knowledge\NullKnowledgeRetriever;
use LimenAi\Knowledge\NullVectorStore;
use LimenAi\Knowledge\VectorKnowledgeRetriever;
use LimenAi\Memory\DatabaseMemoryStore;
use LimenAi\Memory\DefaultMemoryRetriever;
use LimenAi\Memory\InMemoryMemoryStore;
use LimenAi\Memory\MemoryFormatter;
use LimenAi\Memory\MemoryService;
use LimenAi\Observability\LogAuditLogger;
use LimenAi\Providers\EmbeddingProviderManager;
use LimenAi\Providers\Fake\FakeEmbeddingProvider;
use LimenAi\Providers\Fake\FakeLlmProvider;
use LimenAi\Providers\LlmProviderManager;
use LimenAi\Security\EnvSecretResolver;
use LimenAi\Security\NullContentSanitizer;
use LimenAi\Security\PromptInjectionSanitizer;
use LimenAi\Security\SensitiveDataRedactor;
use LimenAi\Security\SsrfUrlValidator;
use LimenAi\Skills\ConfigSkillRepository;
use LimenAi\Tools\CacheIdempotencyGuard;
use LimenAi\Tools\ClassBasedToolExecutor;
use LimenAi\Tools\ConfigToolRepository;
use LimenAi\Tools\NullIdempotencyGuard;
use LimenAi\Tools\ToolInputValidator;
use LimenAi\Tools\ToolPipeline;
use LimenAi\Tools\ToolSchemaBuilder;
use LimenAi\Ui\ThemeResolver;
use LimenAi\Workflows\ConfigWorkflowRepository;
use LimenAi\Workflows\DefaultWorkflowEngine;
use LimenAi\Workflows\WorkflowBranchEvaluator;
use LimenAi\Workflows\WorkflowStepRunner;
use LimenAi\Workflows\WorkflowValidator;
use LimenAi\Workflows\WorkflowVariableResolver;

class LimenAiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/limen-ai.php', 'limen-ai');

        $this->registerRepositories();
        $this->registerProviders();
        $this->registerAgents();
        $this->registerTools();
        $this->registerRuntime();
        $this->registerConversations();
        $this->registerAuthorization();
        $this->registerMemory();
        $this->registerKnowledge();
        $this->registerWorkflows();
        $this->registerIntegrations();
        $this->registerSecurity();
        $this->registerQueue();
        $this->registerBroadcasting();
        $this->registerUi();
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/limen-ai.php' => config_path('limen-ai.php'),
            ], 'limen-ai-config');

            $this->commands([
                ValidateCommand::class,
            ]);
        }

        $this->loadTranslationsFrom(__DIR__.'/../lang', 'limen-ai');

        if ((bool) $this->app['config']->get('limen-ai.broadcasting.enabled', true)) {
            $this->app->make(AgentEventBroadcaster::class)->subscribe($this->app['events']);
        }

        if ((bool) $this->app['config']->get('limen-ai.ui.enabled', true)) {
            $this->loadViewsFrom(__DIR__.'/../resources/views', 'limen-ai');
            Blade::anonymousComponentPath(__DIR__.'/../resources/views/components', 'limen-ai');
            $this->loadRoutesFrom(__DIR__.'/../routes/limen-ai.php');

            if ($this->app->runningInConsole()) {
                $this->publishes([
                    __DIR__.'/../resources/views' => resource_path('views/vendor/limen-ai'),
                    __DIR__.'/../resources/css' => public_path('vendor/limen-ai/css'),
                    __DIR__.'/../resources/js' => public_path('vendor/limen-ai/js'),
                ], 'limen-ai-ui');
            }
        }

        if (class_exists(Broadcast::class) && Broadcast::getFacadeRoot() !== null) {
            $this->loadRoutesFrom(__DIR__.'/../routes/channels.php');
        }
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

    protected function registerProviders(): void
    {
        $this->app->singleton(FakeLlmProvider::class);
        $this->app->singleton(FakeEmbeddingProvider::class);
        $this->app->singleton(LlmProviderManager::class);
        $this->app->singleton(EmbeddingProviderManager::class);

        $this->app->bind(LlmProvider::class, fn ($app): LlmProvider => $app->make(LlmProviderManager::class)->defaultDriver());
        $this->app->bind(EmbeddingProvider::class, fn ($app): EmbeddingProvider => $app->make(EmbeddingProviderManager::class)->driver());
    }

    protected function registerAgents(): void
    {
        $this->app->singleton(InstructionComposer::class);
        $this->app->singleton(ToolSchemaBuilder::class);
        $this->app->singleton(AgentValidator::class);
        $this->app->singleton(AgentResolver::class, DefaultAgentResolver::class);
    }

    protected function registerTools(): void
    {
        $this->app->singleton(SensitiveDataRedactor::class);
        $this->app->singleton(ToolInputValidator::class);
        $this->app->singleton(AuditLogger::class, LogAuditLogger::class);
        $this->app->singleton(ToolExecutor::class, ClassBasedToolExecutor::class);
        $this->app->singleton(ToolPipeline::class);

        $this->app->singleton(IdempotencyGuard::class, function ($app): IdempotencyGuard {
            $driver = $app['config']->get('limen-ai.tool_pipeline.idempotency.driver', 'cache');

            if ($driver === 'null') {
                return new NullIdempotencyGuard();
            }

            return new CacheIdempotencyGuard(
                $app['cache']->store(),
                (int) $app['config']->get('limen-ai.tool_pipeline.idempotency.ttl', 3600),
            );
        });
    }

    protected function registerRuntime(): void
    {
        $runtime = $this->app['config']->get('limen-ai.runtime', []);

        $this->app->singleton(RunRepository::class, function ($app) use ($runtime): RunRepository {
            $implementation = $runtime['run_repository'] ?? InMemoryRunRepository::class;

            if ($implementation === DatabaseRunRepository::class) {
                return new DatabaseRunRepository($app['db']->connection());
            }

            return $app->make($implementation);
        });

        $this->app->singleton(CheckpointStore::class, function ($app) use ($runtime): CheckpointStore {
            $implementation = $runtime['checkpoint_store'] ?? ArrayCheckpointStore::class;

            if ($implementation === DatabaseCheckpointStore::class) {
                return new DatabaseCheckpointStore($app['db']->connection());
            }

            return $app->make($implementation);
        });

        $this->app->singleton(ApprovalRepository::class, function ($app) use ($runtime): ApprovalRepository {
            $implementation = $runtime['approval_repository'] ?? InMemoryApprovalRepository::class;

            if ($implementation === DatabaseApprovalRepository::class) {
                return new DatabaseApprovalRepository($app['db']->connection());
            }

            return $app->make($implementation);
        });

        $this->app->singleton(ToolCallParser::class);
        $this->app->singleton(AgentRuntime::class, DefaultAgentRuntime::class);
    }

    protected function registerConversations(): void
    {
        $conversations = $this->app['config']->get('limen-ai.conversations', []);

        $this->app->singleton(ConversationRepository::class, $conversations['repository'] ?? InMemoryConversationRepository::class);
        $this->app->singleton(MessageRepository::class, $conversations['message_repository'] ?? InMemoryMessageRepository::class);
        $this->app->singleton(MessageFormatter::class);
        $this->app->singleton(ConversationSummarizer::class, $conversations['summarizer'] ?? NullConversationSummarizer::class);
        $this->app->singleton(ConversationService::class);
    }

    protected function registerAuthorization(): void
    {
        $authorization = $this->app['config']->get('limen-ai.authorization', []);

        $this->app->singleton(GuestSessionValidator::class, function ($app) use ($authorization): GuestSessionValidator {
            $validator = $authorization['guest']['validator'] ?? NullGuestSessionValidator::class;

            if ($validator === CacheGuestSessionValidator::class) {
                return new CacheGuestSessionValidator(
                    $app['cache']->store(),
                    (string) ($authorization['guest']['cache_prefix'] ?? 'limen-ai:guest:'),
                );
            }

            return $app->make($validator);
        });

        $this->app->singleton(AuthorizationService::class, LaravelAuthorizationService::class);
    }

    protected function registerMemory(): void
    {
        $memory = $this->app['config']->get('limen-ai.memory', []);

        $this->app->singleton(MemoryStore::class, function ($app) use ($memory): MemoryStore {
            $implementation = $memory['store'] ?? InMemoryMemoryStore::class;

            if ($implementation === DatabaseMemoryStore::class) {
                return new DatabaseMemoryStore($app['db']->connection());
            }

            return $app->make($implementation);
        });

        $this->app->singleton(MemoryFormatter::class);
        $this->app->singleton(MemoryRetriever::class, $memory['retriever'] ?? DefaultMemoryRetriever::class);
        $this->app->singleton(MemoryService::class);
    }

    protected function registerKnowledge(): void
    {
        $knowledge = $this->app['config']->get('limen-ai.knowledge', []);

        $this->app->singleton(KnowledgeFormatter::class);

        $this->app->singleton(VectorStore::class, function ($app) use ($knowledge): VectorStore {
            $driver = $knowledge['driver'] ?? 'null';

            if ($driver === 'vector') {
                return $app->make($knowledge['vector_store'] ?? InMemoryVectorStore::class);
            }

            return new NullVectorStore();
        });

        $this->app->singleton(KnowledgeRetriever::class, function ($app) use ($knowledge): KnowledgeRetriever {
            if (isset($knowledge['retriever'])) {
                return $app->make($knowledge['retriever']);
            }

            return match ($knowledge['driver'] ?? 'null') {
                'config' => $app->make(ConfigKnowledgeRetriever::class),
                'vector' => $app->make(VectorKnowledgeRetriever::class),
                default => new NullKnowledgeRetriever(),
            };
        });

        $this->app->singleton(AgentKnowledgeRetriever::class, DefaultAgentKnowledgeRetriever::class);
        $this->app->singleton(KnowledgeService::class);
    }

    protected function registerWorkflows(): void
    {
        $this->app->singleton(WorkflowVariableResolver::class);
        $this->app->singleton(WorkflowBranchEvaluator::class);
        $this->app->singleton(WorkflowStepRunner::class);
        $this->app->singleton(WorkflowValidator::class);
        $this->app->singleton(WorkflowEngine::class, DefaultWorkflowEngine::class);
    }

    protected function registerIntegrations(): void
    {
        $this->app->singleton(HttpConnectorRepository::class, ConfigHttpConnectorRepository::class);
        $this->app->singleton(UrlValidator::class, SsrfUrlValidator::class);
        $this->app->singleton(SecretResolver::class, EnvSecretResolver::class);
        $this->app->singleton(HttpRequestBuilder::class);
        $this->app->singleton(HttpToolExecutor::class, DeclarativeHttpToolExecutor::class);
        $this->app->singleton(HttpIntegrationValidator::class);
    }

    protected function registerSecurity(): void
    {
        $this->app->singleton(ContentSanitizer::class, function ($app): ContentSanitizer {
            if (! (bool) $app['config']->get('limen-ai.security.injection.enabled', true)) {
                return new NullContentSanitizer();
            }

            return $app->make(PromptInjectionSanitizer::class);
        });
    }

    protected function registerQueue(): void
    {
        $this->app->singleton(RunStatusReader::class, DefaultRunStatusReader::class);

        $this->app->singleton(AgentRunDispatcher::class, function ($app): AgentRunDispatcher {
            if ((bool) $app['config']->get('limen-ai.queue.agent_runs', false)) {
                return $app->make(QueuedAgentRunDispatcher::class);
            }

            return $app->make(SyncAgentRunDispatcher::class);
        });
    }

    protected function registerBroadcasting(): void
    {
        $this->app->singleton(RealtimeBroadcaster::class, function ($app): RealtimeBroadcaster {
            $driver = $app['config']->get('limen-ai.broadcasting.driver', 'null');

            return match ($driver) {
                'pusher' => $app->make(PusherBroadcaster::class),
                default => $app->make(NullBroadcaster::class),
            };
        });

        $this->app->singleton(AgentEventBroadcaster::class);
    }

    protected function registerUi(): void
    {
        $this->app->singleton(ConversationAccessGuard::class);
        $this->app->singleton(ThemeResolver::class);
    }
}
