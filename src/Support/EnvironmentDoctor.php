<?php

namespace LimenAi\Support;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Database\ConnectionResolverInterface;
final class EnvironmentDoctor
{
    /** @var list<string> */
    private array $placeholderApiKeyPatterns = [
        '/^sk-your-/i',
        '/^your[-_]?api[-_]?key$/i',
        '/^changeme$/i',
        '/^placeholder$/i',
        '/^xxx+$/i',
    ];

    public function __construct(
        private readonly ConfigRepository $config,
        private readonly ConnectionResolverInterface $database,
    ) {}

    /**
     * @return array{failures: list<string>, warnings: list<string>}
     */
    public function inspect(string $environment): array
    {
        $failures = [];
        $warnings = [];

        $this->inspectPersistence($environment, $failures, $warnings);
        $this->inspectMigrations($warnings);
        $this->inspectProviderCredentials($warnings);
        $this->inspectPublishedUiVersion($warnings);

        return [
            'failures' => $failures,
            'warnings' => $warnings,
        ];
    }

    /**
     * @param  list<string>  $failures
     * @param  list<string>  $warnings
     */
    protected function inspectPersistence(string $environment, array &$failures, array &$warnings): void
    {
        $conversationRepository = (string) $this->resolvedConversationRepositoryClass();
        $messageRepository = (string) $this->resolvedMessageRepositoryClass();
        $runRepository = (string) $this->resolvedRunRepositoryClass();

        $usesInMemoryConversations = PersistenceConfig::isInMemoryClass($conversationRepository)
            || PersistenceConfig::isInMemoryClass($messageRepository);

        if ($usesInMemoryConversations) {
            $message = 'Conversation persistence uses in-memory repositories. Web chat will fail on the second HTTP request. Set LIMEN_AI_PERSISTENCE_DRIVER=database (and run migrations).';

            if (in_array($environment, ['production', 'staging'], true)) {
                $failures[] = $message;
            } else {
                $warnings[] = $message;
            }
        }

        if (PersistenceConfig::isInMemoryClass($runRepository) && ! (bool) $this->config->get('limen-ai.queue.agent_runs', false)) {
            $warnings[] = 'Run repository uses in-memory storage. Run polling and approval resume will not work across HTTP requests unless LIMEN_AI_PERSISTENCE_DRIVER=database.';
        }

        if ($this->usesDatabaseDriver()) {
            foreach ([
                'limen_ai_conversations' => 'conversations',
                'limen_ai_messages' => 'messages',
                'limen_ai_runs' => 'runs',
            ] as $table => $label) {
                if (! $this->database->connection()->getSchemaBuilder()->hasTable($table)) {
                    $warnings[] = "Persistence driver is database but table [{$table}] is missing. Run php artisan migrate.";
                }
            }
        }
    }

    /**
     * @param  list<string>  $warnings
     */
    protected function inspectMigrations(array &$warnings): void
    {
        if ($this->usesDatabaseDriver()) {
            return;
        }

        if ($this->database->connection()->getSchemaBuilder()->hasTable('limen_ai_conversations')) {
            $warnings[] = 'limen_ai_conversations table exists but LIMEN_AI_PERSISTENCE_DRIVER is not database. Set LIMEN_AI_PERSISTENCE_DRIVER=database for web chat persistence.';
        }
    }

    /**
     * @param  list<string>  $warnings
     */
    protected function inspectProviderCredentials(array &$warnings): void
    {
        if (! (bool) $this->config->get('limen-ai.ui.enabled', true)) {
            return;
        }

        $defaultAgent = (string) $this->config->get('limen-ai.default_agent', '');
        $agent = is_array($this->config->get("limen-ai.agents.{$defaultAgent}"))
            ? $this->config->get("limen-ai.agents.{$defaultAgent}")
            : [];
        $provider = (string) ($agent['provider'] ?? $this->config->get('limen-ai.providers.default', 'fake'));

        if ($provider === 'fake') {
            return;
        }

        $apiKey = (string) ($this->config->get("limen-ai.providers.{$provider}.api_key") ?? '');

        if ($apiKey === '') {
            $warnings[] = "Provider [{$provider}] has no API key configured. Chat requests will fail until credentials are set.";

            return;
        }

        foreach ($this->placeholderApiKeyPatterns as $pattern) {
            if (preg_match($pattern, $apiKey) === 1) {
                $warnings[] = "Provider [{$provider}] appears to use a placeholder API key. Update your .env before production use.";

                return;
            }
        }
    }

    /**
     * @param  list<string>  $warnings
     */
    protected function inspectPublishedUiVersion(array &$warnings): void
    {
        if (! (bool) $this->config->get('limen-ai.ui.enabled', true)) {
            return;
        }

        $basePath = function_exists('resource_path') ? resource_path() : null;

        if ($basePath === null) {
            return;
        }

        $publishedVersionFile = PackageVersion::publishedUiVersionFile($basePath);

        if (! is_file($publishedVersionFile)) {
            return;
        }

        $publishedVersion = trim((string) file_get_contents($publishedVersionFile));
        $packageVersion = is_file(PackageVersion::uiVersionFile())
            ? trim((string) file_get_contents(PackageVersion::uiVersionFile()))
            : PackageVersion::VERSION;

        if ($publishedVersion !== '' && $publishedVersion !== $packageVersion) {
            $warnings[] = "Published Limen AI UI views ({$publishedVersion}) are older than the installed package UI ({$packageVersion}). Run php artisan vendor:publish --tag=limen-ai-ui --force or customize via config/env instead.";
        }
    }

    protected function usesDatabaseDriver(): bool
    {
        return (string) $this->config->get('limen-ai.persistence.driver', PersistenceConfig::driver())
            === PersistenceConfig::DRIVER_DATABASE;
    }

    protected function resolvedConversationRepositoryClass(): string
    {
        return (string) ($this->config->get('limen-ai.conversations.repository')
            ?: PersistenceConfig::conversationRepositoryClass(
                (string) $this->config->get('limen-ai.persistence.driver', PersistenceConfig::driver()),
            ));
    }

    protected function resolvedMessageRepositoryClass(): string
    {
        return (string) ($this->config->get('limen-ai.conversations.message_repository')
            ?: PersistenceConfig::messageRepositoryClass(
                (string) $this->config->get('limen-ai.persistence.driver', PersistenceConfig::driver()),
            ));
    }

    protected function resolvedRunRepositoryClass(): string
    {
        return (string) ($this->config->get('limen-ai.runtime.run_repository')
            ?: PersistenceConfig::runRepositoryClass(
                (string) $this->config->get('limen-ai.persistence.driver', PersistenceConfig::driver()),
            ));
    }
}
