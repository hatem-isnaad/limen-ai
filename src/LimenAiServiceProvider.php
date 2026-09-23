<?php

namespace LimenAi;

use Illuminate\Support\ServiceProvider;
use LimenAi\Agents\AgentValidator;
use LimenAi\Agents\ConfigAgentRepository;
use LimenAi\Agents\DefaultAgentResolver;
use LimenAi\Agents\AgentPersonaComposer;
use LimenAi\Agents\AgentResponseGuard;
use LimenAi\Agents\ChainedOutputValidator;
use LimenAi\Agents\ForbiddenTopicsOutputValidator;
use LimenAi\Agents\HeuristicOutputValidator;
use LimenAi\Agents\StructuredOutputValidator;
use LimenAi\Contracts\Agents\OutputModerator;
use LimenAi\Contracts\Agents\OutputValidator;
use LimenAi\Security\BasicOutputModerator;
use LimenAi\Security\NullOutputModerator;
use LimenAi\Agents\InstructionComposer;
use LimenAi\Authorization\CacheGuestSessionValidator;
use LimenAi\Authorization\DatabaseApprovalRepository;
use LimenAi\Authorization\InMemoryApprovalRepository;
use LimenAi\Authorization\LaravelAuthorizationService;
use LimenAi\Authorization\NullGuestSessionValidator;
use LimenAi\Attachments\AttachmentFormatter;
use LimenAi\Attachments\AttachmentService;
use LimenAi\Attachments\AttachmentValidator;
use LimenAi\Attachments\AttachmentVectorIndexer;
use LimenAi\Attachments\DatabaseAttachmentStore;
use LimenAi\Attachments\DefaultAgentAttachmentRetriever;
use LimenAi\Attachments\DefaultAttachmentTextExtractor;
use LimenAi\Attachments\InMemoryAttachmentStore;
use LimenAi\Attachments\NullAttachmentStore;
use LimenAi\Console\AgentTestCommand;
use LimenAi\Console\AgentsCommand;
use LimenAi\Console\ChecklistCommand;
use LimenAi\Console\DoctorCommand;
use LimenAi\Console\ImportKnowledgeCommand;
use LimenAi\Console\InstallCommand;
use LimenAi\Console\ListCommand;
use LimenAi\Console\LogsCommand;
use LimenAi\Console\MakeAgentCommand;
use LimenAi\Console\MakeConnectorCommand;
use LimenAi\Console\MakeKnowledgeCommand;
use LimenAi\Console\MakeMemoryCommand;
use LimenAi\Console\MakeProviderCommand;
use LimenAi\Console\MakeSkillCommand;
use LimenAi\Console\MakeToolCommand;
use LimenAi\Console\MakeWorkflowCommand;
use LimenAi\Console\RunCommand;
use LimenAi\Console\SkillTestCommand;
use LimenAi\Console\SkillsCommand;
use LimenAi\Console\StubGenerator;
use LimenAi\Console\ToolTestCommand;
use LimenAi\Console\ToolsCommand;
use LimenAi\Console\ValidateCommand;
use LimenAi\Console\WorkflowTestCommand;
use LimenAi\Console\WorkflowsCommand;
use LimenAi\Contracts\Attachments\AgentAttachmentRetriever;
use LimenAi\Contracts\Attachments\AttachmentStore;
use LimenAi\Contracts\Attachments\AttachmentTextExtractor;
use LimenAi\Support\LimenAiManager;
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
use LimenAi\Observability\SkillAdherenceReporter;
use LimenAi\Support\EnvironmentDoctor;
use LimenAi\Support\PersistenceConfig;
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
use LimenAi\Memory\StrictMemoryPolicy;
use LimenAi\Memory\InMemoryMemoryStore;
use LimenAi\Memory\MemoryFormatter;
use LimenAi\Memory\MemoryService;
use LimenAi\Observability\AgentObservabilityListener;
use LimenAi\Observability\AuditBuffer;
use LimenAi\Observability\DefaultAuditExporter;
use LimenAi\Observability\LogAuditLogger;
use LimenAi\Observability\LogUsageTracker;
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
use LimenAi\Tools\CompositeToolRepository;
use LimenAi\Tools\ConfigToolRepository;
use LimenAi\Tools\RuntimeToolRegistry;
use LimenAi\Tools\NullIdempotencyGuard;
use LimenAi\Tools\ToolInputValidator;
use LimenAi\Tools\ToolPipeline;
use LimenAi\Tools\ToolSchemaBuilder;
use LimenAi\Ui\ChatUiConfig;
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
        $this->registerAttachments();
        $this->registerManager();
        $this->registerSupport();
        $this->registerConsole();
    }

    public function boot(): void
    {
        $this->mergeImportedKnowledgeCollections();

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/limen-ai.php' => config_path('limen-ai.php'),
                __DIR__.'/../config/limen-ai-provider-agents.php' => config_path('limen-ai-provider-agents.php'),
                __DIR__.'/../config/limen-ai-black-box-defaults.php' => config_path('limen-ai-black-box-defaults.php'),
            ], 'limen-ai-config');

            $this->publishes([
                __DIR__.'/../stubs/limen-ai-knowledge.php.stub' => config_path('limen-ai-knowledge.php'),
            ], 'limen-ai-knowledge');

            $this->publishes([
                __DIR__.'/../src/Http/Middleware/ThrottleAgentRequests.php' => app_path('Http/Middleware/ThrottleAgentRequests.php'),
            ], 'limen-ai-middleware');

            $this->publishes([
                __DIR__.'/../stubs/limen-ai.env.example' => base_path('.env.limen-ai.example'),
            ], 'limen-ai-env');

            $this->publishes([
                __DIR__.'/../stubs' => base_path('stubs/limen-ai'),
            ], 'limen-ai-stubs');

            $this->publishes([
                __DIR__.'/../stubs/limen' => base_path('stubs/limen-ai/limen'),
                __DIR__.'/../examples/limen-host' => base_path('examples/limen-host'),
            ], 'limen-ai-limen-demo');
        }

        $this->loadTranslationsFrom(__DIR__.'/../lang', 'limen-ai');

        if ((bool) $this->app['config']->get('limen-ai.broadcasting.enabled', true)) {
            $this->app->make(AgentEventBroadcaster::class)->subscribe($this->app['events']);
        }

        if ((bool) $this->app['config']->get('limen-ai.observability.audit_enabled', true)) {
            $this->app->make(AgentObservabilityListener::class)->subscribe($this->app['events']);
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
        $this->app->singleton(ConfigToolRepository::class);
        $this->app->singleton(RuntimeToolRegistry::class);
        $this->app->singleton(ToolRepository::class, $repositories['tool'] ?? CompositeToolRepository::class);
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
        $this->app->singleton(OutputValidator::class, function ($app): OutputValidator {
            $validators = [new StructuredOutputValidator()];

            if ((bool) $app['config']->get('limen-ai.quality.heuristic_validation', false)) {
                $validators[] = $app->make(HeuristicOutputValidator::class);
            }

            if ((bool) $app['config']->get('limen-ai.quality.enforce_forbidden_topics', false)) {
                $validators[] = $app->make(ForbiddenTopicsOutputValidator::class);
            }

            $custom = $app['config']->get('limen-ai.quality.output_validator');

            if (is_string($custom) && $custom !== '') {
                $validators[] = $app->make($custom);
            }

            return new ChainedOutputValidator($validators);
        });

        $this->app->singleton(OutputModerator::class, function ($app): OutputModerator {
            if (! (bool) $app['config']->get('limen-ai.quality.output_moderation_enabled', false)) {
                return new NullOutputModerator();
            }

            $custom = $app['config']->get('limen-ai.quality.output_moderator');

            if (is_string($custom) && $custom !== '') {
                return $app->make($custom);
            }

            return $app->make(BasicOutputModerator::class);
        });

        $this->app->singleton(AgentPersonaComposer::class);
        $this->app->singleton(AgentResponseGuard::class);
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
            $implementation = $this->resolvePersistenceClass(
                $app['config']->get('limen-ai.runtime.run_repository'),
                PersistenceConfig::runRepositoryClass($this->persistenceDriver($app)),
            );

            if ($implementation === DatabaseRunRepository::class) {
                return new DatabaseRunRepository($app['db']->connection());
            }

            return $app->make($implementation);
        });

        $this->app->singleton(CheckpointStore::class, function ($app): CheckpointStore {
            $implementation = $this->resolvePersistenceClass(
                $app['config']->get('limen-ai.runtime.checkpoint_store'),
                PersistenceConfig::checkpointStoreClass($this->persistenceDriver($app)),
            );

            if ($implementation === DatabaseCheckpointStore::class) {
                return new DatabaseCheckpointStore($app['db']->connection());
            }

            return $app->make($implementation);
        });

        $this->app->singleton(ApprovalRepository::class, function ($app): ApprovalRepository {
            $implementation = $this->resolvePersistenceClass(
                $app['config']->get('limen-ai.runtime.approval_repository'),
                PersistenceConfig::approvalRepositoryClass($this->persistenceDriver($app)),
            );

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

        $this->app->singleton(ConversationRepository::class, function ($app) use ($conversations): ConversationRepository {
            $implementation = $this->resolvePersistenceClass(
                $conversations['repository'] ?? null,
                PersistenceConfig::conversationRepositoryClass($this->persistenceDriver($app)),
            );

            if ($implementation === DatabaseConversationRepository::class) {
                return new DatabaseConversationRepository($app['db']->connection());
            }

            return $app->make($implementation);
        });

        $this->app->singleton(MessageRepository::class, function ($app) use ($conversations): MessageRepository {
            $implementation = $this->resolvePersistenceClass(
                $conversations['message_repository'] ?? null,
                PersistenceConfig::messageRepositoryClass($this->persistenceDriver($app)),
            );

            if ($implementation === DatabaseMessageRepository::class) {
                return new DatabaseMessageRepository($app['db']->connection());
            }

            return $app->make($implementation);
        });

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
        $this->app->singleton(MemoryStore::class, function ($app): MemoryStore {
            $memory = $app['config']->get('limen-ai.memory', []);
            $implementation = $memory['store'] ?? InMemoryMemoryStore::class;

            if ($implementation === DatabaseMemoryStore::class) {
                return new DatabaseMemoryStore($app['db']->connection());
            }

            return $app->make($implementation);
        });

        $this->app->singleton(MemoryFormatter::class);
        $this->app->singleton(StrictMemoryPolicy::class);
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
        $this->app->singleton(ChatUiConfig::class);
        $this->app->singleton(\LimenAi\Ui\WidgetThemeOptions::class);
    }

    protected function registerAttachments(): void
    {
        $this->app->singleton(AttachmentTextExtractor::class, DefaultAttachmentTextExtractor::class);
        $this->app->singleton(AttachmentValidator::class);
        $this->app->singleton(AttachmentFormatter::class);
        $this->app->singleton(AttachmentVectorIndexer::class);
        $this->app->singleton(AttachmentService::class);
        $this->app->singleton(AgentAttachmentRetriever::class, DefaultAgentAttachmentRetriever::class);

        $this->app->singleton(AttachmentStore::class, function ($app): AttachmentStore {
            $attachments = $app['config']->get('limen-ai.attachments', []);

            if (! ($attachments['enabled'] ?? true)) {
                return new NullAttachmentStore();
            }

            $implementation = $attachments['store'] ?? InMemoryAttachmentStore::class;

            if ($implementation === DatabaseAttachmentStore::class) {
                return new DatabaseAttachmentStore(
                    $app['db']->connection(),
                    (string) ($attachments['disk'] ?? 'local'),
                    (string) ($attachments['path'] ?? 'limen-ai/attachments'),
                );
            }

            return $app->make($implementation);
        });
    }

    protected function registerManager(): void
    {
        $this->app->singleton(LimenAiManager::class);
    }

    protected function registerSupport(): void
    {
        $this->app->singleton(EnvironmentDoctor::class);
    }

    protected function mergeImportedKnowledgeCollections(): void
    {
        if (! function_exists('config_path')) {
            return;
        }

        $path = config_path('limen-ai-knowledge.php');

        if (! is_file($path)) {
            return;
        }

        $collections = require $path;

        if (! is_array($collections)) {
            return;
        }

        $existing = $this->app['config']->get('limen-ai.knowledge.collections', []);

        if (! is_array($existing)) {
            $existing = [];
        }

        $this->app['config']->set(
            'limen-ai.knowledge.collections',
            array_replace($existing, $collections),
        );
    }

    protected function registerConsole(): void
    {
        $this->app->singleton(StubGenerator::class, fn ($app): StubGenerator => new StubGenerator(
            $app['files'],
            dirname(__DIR__).'/stubs',
        ));

        if ($this->app->runningInConsole()) {
            $this->commands([
                ImportKnowledgeCommand::class,
                InstallCommand::class,
                ChecklistCommand::class,
                ValidateCommand::class,
                DoctorCommand::class,
                ListCommand::class,
                AgentsCommand::class,
                ToolsCommand::class,
                SkillsCommand::class,
                SkillTestCommand::class,
                WorkflowsCommand::class,
                LogsCommand::class,
                RunCommand::class,
                AgentTestCommand::class,
                ToolTestCommand::class,
                WorkflowTestCommand::class,
                MakeAgentCommand::class,
                MakeSkillCommand::class,
                MakeToolCommand::class,
                MakeWorkflowCommand::class,
                MakeConnectorCommand::class,
                MakeProviderCommand::class,
                MakeMemoryCommand::class,
                MakeKnowledgeCommand::class,
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
        $this->app->singleton(SkillAdherenceReporter::class);
        $this->app->singleton(AgentObservabilityListener::class);

        $this->app->singleton(UsageTracker::class, function ($app): UsageTracker {
            if (! (bool) $app['config']->get('limen-ai.observability.usage_tracking_enabled', true)) {
                return new NullUsageTracker();
            }

            return $app->make(LogUsageTracker::class);
        });

        $this->app->bind(UsageReader::class, fn ($app): UsageReader => $app->make(UsageTracker::class));
    }

    protected function persistenceDriver($app): string
    {
        $configured = $app['config']->get('limen-ai.persistence.driver');
        $autoDetect = (bool) $app['config']->get('limen-ai.persistence.auto_detect', true);
        $conversationsTableExists = false;

        if ($autoDetect) {
            try {
                if ($app->bound('db')) {
                    $conversationsTableExists = $app['db']->connection()->getSchemaBuilder()->hasTable('limen_ai_conversations');
                }
            } catch (\Throwable) {
                $conversationsTableExists = false;
            }
        }

        return PersistenceConfig::resolveDriver(
            is_string($configured) ? $configured : null,
            $autoDetect,
            $conversationsTableExists,
        );
    }

    protected function resolvePersistenceClass(?string $configured, string $default): string
    {
        return is_string($configured) && $configured !== '' ? $configured : $default;
    }
}
