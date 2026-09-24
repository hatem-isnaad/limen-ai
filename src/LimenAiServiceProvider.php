<?php

namespace LimenAi;

use Illuminate\Support\ServiceProvider;
use LimenAi\Agents\AgentValidator;
use LimenAi\Agents\AgentDefinitionStore;
use LimenAi\Agents\ClassAgentDefinitionFactory;
use LimenAi\Agents\CompositeAgentRepository;
use LimenAi\Agents\ConfigAgentRepository;
use LimenAi\Agents\DatabaseAgentRepository;
use LimenAi\Agents\DefaultAgentResolver;
use LimenAi\Agents\InstructionComposer;
use LimenAi\Authorization\CacheGuestSessionValidator;
use LimenAi\Authorization\DatabaseApprovalRepository;
use LimenAi\Authorization\InMemoryApprovalRepository;
use LimenAi\Authorization\LaravelAuthorizationService;
use LimenAi\Authorization\NullGuestSessionValidator;
use LimenAi\Console\ClearAgentDefinitionCacheCommand;
use LimenAi\Console\DoctorCommand;
use LimenAi\Console\ImportAgentsCommand;
use LimenAi\Console\InstallCommand;
use LimenAi\Console\ListCommand;
use LimenAi\Console\MakeAgentCommand;
use LimenAi\Console\MakeKnowledgeCommand;
use LimenAi\Console\MakeSkillCommand;
use LimenAi\Console\MakeToolCommand;
use LimenAi\Console\StubGenerator;
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
use LimenAi\Contracts\Observability\AuditExporter;
use LimenAi\Contracts\Observability\AuditLogger;
use LimenAi\Contracts\Observability\UsageReader;
use LimenAi\Contracts\Observability\UsageTracker;
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
use LimenAi\Conversations\DatabaseConversationRepository;
use LimenAi\Conversations\DatabaseMessageRepository;
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
use LimenAi\Observability\AgentObservabilityListener;
use LimenAi\Observability\AuditBuffer;
use LimenAi\Observability\DefaultAuditExporter;
use LimenAi\Observability\LogAuditLogger;
use LimenAi\Observability\LogUsageTracker;
use LimenAi\Observability\PersistingUsageTracker;
use LimenAi\Observability\RunUsageFinalizer;
use LimenAi\Observability\RunUsageMessageLinker;
use LimenAi\Webhooks\WebhookEventSubscriber;
use LimenAi\Observability\NullUsageTracker;
use LimenAi\Observability\RunObservabilityReporter;
use LimenAi\Observability\UsageBuffer;
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
use LimenAi\Tools\RuntimeToolCatalog;
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
use LimenAi\LimenAiManager;
use LimenAi\Registry\LimenAiRegistry;
use LimenAi\Support\ConfigFragmentWriter;
use LimenAi\Support\DefinitionLoader;

class LimenAiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergePackageConfig(__DIR__.'/../config/limen-ai.php', 'limen-ai');

        $this->app->singleton(LimenAiRegistry::class);
        $this->app->singleton(\LimenAi\Ai\AnonymousAgentRegistrar::class);
        $this->app->singleton(\LimenAi\Ai\Sdk\AiSdkManager::class);
        $this->app->singleton(\LimenAi\Runtime\AgentStepRunner::class);
        $this->app->singleton(\LimenAi\Mcp\McpTransportFactory::class);
        $this->app->singleton(DefinitionLoader::class);
        $this->app->singleton(LimenAiManager::class);
        $this->app->singleton(ConfigFragmentWriter::class);

        $this->registerRepositories();
        $this->registerProviders();
        $this->registerAgents();
        $this->registerObservability();
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
        $this->registerConsole();
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/limen-ai.php' => config_path('limen-ai.php'),
            ], 'limen-ai-config');

            $this->publishes([
                __DIR__.'/../stubs' => base_path('stubs/limen-ai'),
            ], 'limen-ai-stubs');

            $this->publishes([
                __DIR__.'/../stubs/limen' => base_path('stubs/limen-ai/limen'),
                __DIR__.'/../examples/limen-host' => base_path('examples/limen-host'),
            ], 'limen-ai-limen-demo');

            $this->publishes([
                __DIR__.'/../stubs/env.limen-ai.example' => base_path('.env.limen-ai.example'),
            ], 'limen-ai-env');
        }

        $this->app->booted(function (): void {
            $this->registerProviderToolsWhenEnabled();
            $this->registerMcpToolsWhenEnabled();

            $registry = $this->app->make(LimenAiRegistry::class);

            foreach ($registry->providers() as $name => $settings) {
                $current = $this->app['config']->get("limen-ai.providers.{$name}", []);
                $this->app['config']->set(
                    "limen-ai.providers.{$name}",
                    array_merge(is_array($current) ? $current : [], $settings),
                );
            }
        });

        $this->loadTranslationsFrom(__DIR__.'/../lang', 'limen-ai');

        if ((bool) $this->app['config']->get('limen-ai.broadcasting.enabled', true)) {
            $this->app->make(AgentEventBroadcaster::class)->subscribe($this->app['events']);
        }

        if ((bool) $this->app['config']->get('limen-ai.webhooks.enabled', false)) {
            $this->app->make(WebhookEventSubscriber::class)->subscribe($this->app['events']);
        }

        if ((bool) $this->app['config']->get('limen-ai.observability.audit_enabled', true)) {
            $this->app->make(AgentObservabilityListener::class)->subscribe($this->app['events']);
        }

        if ((bool) $this->app['config']->get('limen-ai.api.enabled', true)) {
            $this->loadRoutesFrom(__DIR__.'/../routes/limen-ai.php');
        }

        if ((bool) $this->app['config']->get('limen-ai.ui.enabled', true)) {
            $this->loadViewsFrom(__DIR__.'/../resources/views', 'limen-ai');
            Blade::anonymousComponentPath(__DIR__.'/../resources/views/components', 'limen-ai');

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

        $this->app->singleton(ConfigAgentRepository::class);
        $this->app->singleton(DatabaseAgentRepository::class);
        $this->app->singleton(CompositeAgentRepository::class);

        $this->app->singleton(AgentRepository::class, function ($app) use ($repositories) {
            $implementation = $repositories['agent'] ?? CompositeAgentRepository::class;

            return $app->make($implementation);
        });
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
        $this->app->singleton(RuntimeToolCatalog::class);
        $this->app->singleton(AgentDefinitionStore::class);
        $this->app->singleton(ClassAgentDefinitionFactory::class);
        $this->app->singleton(InstructionComposer::class);
        $this->app->singleton(ToolSchemaBuilder::class);
        $this->app->singleton(AgentValidator::class);
        $this->app->singleton(AgentResolver::class, DefaultAgentResolver::class);
    }

    protected function registerTools(): void
    {
        $this->app->singleton(SensitiveDataRedactor::class);
        $this->app->singleton(ToolInputValidator::class);
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
        $this->app->singleton(RunRepository::class, function ($app): RunRepository {
            $runtime = $app['config']->get('limen-ai.runtime', []);
            $implementation = $runtime['run_repository'] ?? InMemoryRunRepository::class;

            if ($implementation === DatabaseRunRepository::class) {
                return new DatabaseRunRepository($this->dbConnection($app));
            }

            return $app->make($implementation);
        });

        $this->app->singleton(CheckpointStore::class, function ($app): CheckpointStore {
            $runtime = $app['config']->get('limen-ai.runtime', []);
            $implementation = $runtime['checkpoint_store'] ?? ArrayCheckpointStore::class;

            if ($implementation === DatabaseCheckpointStore::class) {
                return new DatabaseCheckpointStore($this->dbConnection($app));
            }

            return $app->make($implementation);
        });

        $this->app->singleton(ApprovalRepository::class, function ($app): ApprovalRepository {
            $runtime = $app['config']->get('limen-ai.runtime', []);
            $implementation = $runtime['approval_repository'] ?? InMemoryApprovalRepository::class;

            if ($implementation === DatabaseApprovalRepository::class) {
                return new DatabaseApprovalRepository($this->dbConnection($app));
            }

            return $app->make($implementation);
        });

        $this->app->singleton(ToolCallParser::class);
        $this->app->singleton(AgentRuntime::class, DefaultAgentRuntime::class);
    }

    protected function registerConversations(): void
    {
        $this->app->singleton(ConversationRepository::class, function ($app): ConversationRepository {
            $conversations = $app['config']->get('limen-ai.conversations', []);
            $implementation = $conversations['repository'] ?? InMemoryConversationRepository::class;

            if ($implementation === DatabaseConversationRepository::class) {
                return new DatabaseConversationRepository($this->dbConnection($app));
            }

            return $app->make($implementation);
        });

        $this->app->singleton(MessageRepository::class, function ($app): MessageRepository {
            $conversations = $app['config']->get('limen-ai.conversations', []);
            $implementation = $conversations['message_repository'] ?? InMemoryMessageRepository::class;

            if ($implementation === DatabaseMessageRepository::class) {
                return new DatabaseMessageRepository($this->dbConnection($app));
            }

            return $app->make($implementation);
        });
        $this->app->singleton(MessageFormatter::class);
        $this->app->singleton(ConversationSummarizer::class, function ($app) {
            $conversations = $app['config']->get('limen-ai.conversations', []);

            return $app->make($conversations['summarizer'] ?? NullConversationSummarizer::class);
        });
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
        $this->app->singleton(MemoryStore::class, function ($app): MemoryStore {
            $memory = $app['config']->get('limen-ai.memory', []);
            $implementation = $memory['store'] ?? InMemoryMemoryStore::class;

            if ($implementation === DatabaseMemoryStore::class) {
                return new DatabaseMemoryStore($app['db']->connection());
            }

            return $app->make($implementation);
        });

        $this->app->singleton(MemoryFormatter::class);
        $this->app->singleton(MemoryRetriever::class, function ($app): MemoryRetriever {
            $memory = $app['config']->get('limen-ai.memory', []);

            return $app->make($memory['retriever'] ?? DefaultMemoryRetriever::class);
        });
        $this->app->singleton(MemoryService::class);
    }

    protected function registerKnowledge(): void
    {
        $this->app->singleton(KnowledgeFormatter::class);

        $this->app->singleton(VectorStore::class, function ($app): VectorStore {
            $knowledge = $app['config']->get('limen-ai.knowledge', []);
            $driver = $knowledge['driver'] ?? 'null';

            if ($driver === 'vector') {
                return $app->make($knowledge['vector_store'] ?? InMemoryVectorStore::class);
            }

            return new NullVectorStore();
        });

        $this->app->singleton(KnowledgeRetriever::class, function ($app): KnowledgeRetriever {
            $knowledge = $app['config']->get('limen-ai.knowledge', []);

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

    protected function registerConsole(): void
    {
        $this->app->singleton(StubGenerator::class, fn ($app): StubGenerator => new StubGenerator(
            $app['files'],
            dirname(__DIR__).'/stubs',
        ));

        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
                ValidateCommand::class,
                DoctorCommand::class,
                ListCommand::class,
                MakeAgentCommand::class,
                MakeKnowledgeCommand::class,
                MakeSkillCommand::class,
                MakeToolCommand::class,
                ImportAgentsCommand::class,
                ClearAgentDefinitionCacheCommand::class,
            ]);
        }
    }

    protected function registerObservability(): void
    {
        $this->app->singleton(AuditBuffer::class);
        $this->app->singleton(UsageBuffer::class);
        $this->app->singleton(AuditLogger::class, LogAuditLogger::class);
        $this->app->singleton(AuditExporter::class, DefaultAuditExporter::class);
        $this->app->singleton(RunObservabilityReporter::class);
        $this->app->singleton(AgentObservabilityListener::class);

        $this->app->singleton(LogUsageTracker::class);
        $this->app->singleton(RunUsageFinalizer::class);

        $this->app->singleton(RunUsageMessageLinker::class, function ($app): RunUsageMessageLinker {
            return new RunUsageMessageLinker(
                $app->make(UsageBuffer::class),
                $app['config'],
                $app->bound('db') ? $this->dbConnection($app) : null,
            );
        });

        $this->app->singleton(UsageTracker::class, function ($app): UsageTracker {
            if (! (bool) $app['config']->get('limen-ai.observability.usage_tracking_enabled', true)) {
                return new NullUsageTracker();
            }

            return new PersistingUsageTracker(
                $app->make(LogUsageTracker::class),
                $app['config'],
                $app->bound('db') ? $app->make('db')->connection() : null,
            );
        });

        $this->app->bind(UsageReader::class, fn ($app): UsageReader => $app->make(UsageTracker::class));

        $this->app->singleton(WebhookEventSubscriber::class);
        $this->app->singleton(\LimenAi\Webhooks\WebhookDispatcher::class);
    }

    protected function dbConnection($app): \Illuminate\Database\ConnectionInterface
    {
        $connection = $app['config']->get('limen-ai.persistence.connection');

        return $connection !== null && $connection !== ''
            ? $app['db']->connection($connection)
            : $app['db']->connection();
    }

    protected function registerProviderToolsWhenEnabled(): void
    {
        if (! (bool) $this->app['config']->get('limen-ai.provider_tools.enabled', false)) {
            return;
        }

        $tools = $this->app['config']->get('limen-ai.tools', []);
        $map = [
            'web_search' => \LimenAi\Tools\BuiltIn\WebSearchTool::class,
            'web_fetch' => \LimenAi\Tools\BuiltIn\WebFetchTool::class,
            'file_search' => \LimenAi\Tools\BuiltIn\FileSearchTool::class,
        ];

        foreach ($map as $key => $class) {
            if (! (bool) data_get($this->app['config']->get('limen-ai.provider_tools'), "{$key}.enabled", true)) {
                continue;
            }

            $tools[$key] = array_merge([
                'name' => str($key)->headline()->toString(),
                'description' => 'SDK provider tool.',
                'class' => $class,
                'input_schema' => [],
                'authorization' => ['abilities' => []],
                'confirmation' => false,
                'timeout' => 30,
                'version' => '1.0.0',
            ], is_array($tools[$key] ?? null) ? $tools[$key] : []);
        }

        $this->app['config']->set('limen-ai.tools', $tools);
    }

    protected function registerMcpToolsWhenEnabled(): void
    {
        if (! (bool) $this->app['config']->get('limen-ai.mcp.enabled', false)) {
            return;
        }

        if (! (bool) $this->app['config']->get('limen-ai.mcp.register_tools', true)) {
            return;
        }

        $servers = $this->app['config']->get('limen-ai.mcp.servers', []);

        if (! is_array($servers) || $servers === []) {
            return;
        }

        $factory = $this->app->make(\LimenAi\Mcp\McpTransportFactory::class);
        $tools = $this->app['config']->get('limen-ai.tools', []);

        foreach ($servers as $serverKey => $server) {
            if (! is_array($server)) {
                continue;
            }

            try {
                $transport = $factory->make((string) $serverKey, $server);
                $client = new \LimenAi\Mcp\McpClient($transport);
                $client->initialize();
                $remoteTools = $client->listTools();
                $client->close();
            } catch (\Throwable) {
                continue;
            }

            foreach ($remoteTools as $remote) {
                $remoteName = (string) ($remote['name'] ?? '');

                if ($remoteName === '') {
                    continue;
                }

                $toolKey = 'mcp_'.str($serverKey)->slug('_').'_'.str($remoteName)->slug('_');

                $tools[$toolKey] = array_merge([
                    'name' => 'MCP '.$remoteName,
                    'description' => (string) ($remote['description'] ?? 'Remote MCP tool'),
                    'class' => \LimenAi\Tools\McpBridgeTool::class,
                    'input_schema' => [],
                    'authorization' => ['abilities' => []],
                    'confirmation' => false,
                    'timeout' => 60,
                    'version' => '1.0.0',
                    'mcp' => [
                        'server' => $serverKey,
                        'remote_tool' => $remoteName,
                    ],
                ], is_array($tools[$toolKey] ?? null) ? $tools[$toolKey] : []);

                $binding = "limen-ai.tool.{$toolKey}";
                $this->app->singleton($binding, fn ($app) => new \LimenAi\Tools\McpBridgeTool(
                    $toolKey,
                    $remoteName,
                    (string) $serverKey,
                    $server,
                    $app->make(\LimenAi\Mcp\McpTransportFactory::class),
                ));
            }
        }

        $this->app['config']->set('limen-ai.tools', $tools);
    }

    /**
     * Merge package config while preserving host fragment files (config/limen-ai/{section}/*.php).
     *
     * @param  non-empty-string  $path
     * @param  non-empty-string  $key
     */
    protected function mergePackageConfig(string $path, string $key): void
    {
        $config = $this->app->make('config');
        $existing = $config->get($key, []);
        $existing = is_array($existing) ? $existing : [];

        /** @var array<string, mixed> $package */
        $package = require $path;

        $merged = array_merge($package, $existing);

        foreach (['tools', 'agents', 'skills', 'workflows'] as $section) {
            $merged[$section] = array_merge(
                is_array($package[$section] ?? null) ? $package[$section] : [],
                is_array($existing[$section] ?? null) ? $existing[$section] : [],
            );
        }

        $packageCollections = $package['knowledge']['collections'] ?? [];
        $existingCollections = $existing['knowledge']['collections'] ?? [];

        if (is_array($packageCollections) || is_array($existingCollections)) {
            $merged['knowledge'] = array_merge(
                is_array($package['knowledge'] ?? null) ? $package['knowledge'] : [],
                is_array($existing['knowledge'] ?? null) ? $existing['knowledge'] : [],
            );
            $merged['knowledge']['collections'] = array_merge(
                is_array($packageCollections) ? $packageCollections : [],
                is_array($existingCollections) ? $existingCollections : [],
            );
        }

        $config->set($key, $merged);
    }
}
