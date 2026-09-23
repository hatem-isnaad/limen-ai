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
use LimenAi\Contracts\Agents\AgentRepository;
use LimenAi\Contracts\Agents\AgentResolver;
use LimenAi\Contracts\Authorization\ApprovalRepository;
use LimenAi\Contracts\Authorization\AuthorizationService;
use LimenAi\Contracts\Authorization\GuestSessionValidator;
use LimenAi\Contracts\Conversations\ConversationRepository;
use LimenAi\Contracts\Conversations\ConversationSummarizer;
use LimenAi\Contracts\Conversations\MessageRepository;
use LimenAi\Contracts\Knowledge\AgentKnowledgeRetriever;
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
use LimenAi\Contracts\Runtime\AgentRuntime;
use LimenAi\Contracts\Runtime\CheckpointStore;
use LimenAi\Contracts\Runtime\RunRepository;
use LimenAi\Contracts\Tools\ToolRepository;
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
use LimenAi\Runtime\InMemoryRunRepository;
use LimenAi\Runtime\ToolCallParser;
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
use LimenAi\Security\SensitiveDataRedactor;
use LimenAi\Skills\ConfigSkillRepository;
use LimenAi\Tools\CacheIdempotencyGuard;
use LimenAi\Tools\ClassBasedToolExecutor;
use LimenAi\Tools\ConfigToolRepository;
use LimenAi\Tools\NullIdempotencyGuard;
use LimenAi\Tools\ToolInputValidator;
use LimenAi\Tools\ToolPipeline;
use LimenAi\Tools\ToolSchemaBuilder;
use LimenAi\Workflows\ConfigWorkflowRepository;

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
}
