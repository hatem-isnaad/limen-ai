<?php

namespace LimenAi\Support;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Support\Facades\Http;

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
        $this->inspectAutoDetectedPersistence($warnings);
        $this->inspectMigrations($warnings);
        $this->inspectProviderCredentials($warnings);
        $this->inspectOllamaEndpoint($warnings);
        $this->inspectCloudLlmEndpoints($warnings);
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

        $tableExists = false;

        try {
            $tableExists = $this->database->connection()->getSchemaBuilder()->hasTable('limen_ai_conversations');
        } catch (\Throwable) {
            $tableExists = false;
        }

        if ($usesInMemoryConversations) {
            $message = 'Conversation persistence uses in-memory repositories. Web chat will fail on the second HTTP request. Run php artisan migrate (auto-detects database) or set LIMEN_AI_PERSISTENCE_DRIVER=database.';

            if ($this->shouldFailPersistenceIssues($environment) || in_array($environment, ['production', 'staging'], true) || $tableExists) {
                $failures[] = $message;
            } else {
                $warnings[] = $message;
            }
        }

        if ((bool) $this->config->get('limen-ai.ui.enabled', true) && ! $tableExists && $this->shouldFailPersistenceIssues($environment)) {
            $failures[] = 'Limen AI UI is enabled but table [limen_ai_conversations] is missing. Run php artisan migrate or php artisan limen-ai:install --migrate.';
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
    protected function inspectAutoDetectedPersistence(array &$warnings): void
    {
        $configured = $this->config->get('limen-ai.persistence.driver');

        if (is_string($configured) && $configured !== '') {
            return;
        }

        if (! $this->usesDatabaseDriver()) {
            return;
        }

        if (! (bool) $this->config->get('limen-ai.persistence.auto_detect', true)) {
            return;
        }

        if ($this->database->connection()->getSchemaBuilder()->hasTable('limen_ai_conversations')) {
            $warnings[] = 'Persistence driver auto-detected as database from limen_ai_conversations. Set LIMEN_AI_PERSISTENCE_DRIVER=database explicitly to silence this hint.';
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
            $warnings[] = 'limen_ai_conversations table exists but persistence is not database. Set LIMEN_AI_PERSISTENCE_DRIVER=database or enable auto_detect (default).';
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
    protected function inspectOllamaEndpoint(array &$warnings): void
    {
        ['provider' => $provider, 'agent' => $agent] = $this->defaultAgentContext();

        if ($provider !== 'openai') {
            return;
        }

        $baseUrl = rtrim((string) ($this->config->get('limen-ai.providers.openai.base_url') ?? ''), '/');
        $apiKey = (string) ($this->config->get('limen-ai.providers.openai.api_key') ?? '');

        if (! $this->looksLikeOllamaEndpoint($baseUrl, $apiKey)) {
            return;
        }

        if ($baseUrl === '') {
            $warnings[] = 'Ollama-style API key detected but OPENAI_BASE_URL is empty. Set OPENAI_BASE_URL=http://localhost:11434/v1';

            return;
        }

        $model = (string) ($agent['model'] ?? $this->config->get('limen-ai.agent_defaults.model', ''));

        try {
            $response = Http::timeout(3)
                ->withToken($apiKey !== '' ? $apiKey : 'ollama')
                ->acceptJson()
                ->get($baseUrl.'/models');
        } catch (\Throwable $exception) {
            $warnings[] = "Ollama endpoint [{$baseUrl}] is not reachable: {$exception->getMessage()}";

            return;
        }

        if (! $response->successful()) {
            $warnings[] = "Ollama endpoint [{$baseUrl}] returned HTTP {$response->status()}. Ensure Ollama is running (ollama serve).";

            return;
        }

        if ($model === '') {
            return;
        }

        $listedModels = collect($response->json('data', []))
            ->pluck('id')
            ->filter(fn (mixed $id): bool => is_string($id) && $id !== '')
            ->values()
            ->all();

        if ($listedModels === []) {
            return;
        }

        if (! in_array($model, $listedModels, true)) {
            $warnings[] = "Ollama is reachable but model [{$model}] is not installed. Run: ollama pull {$model}";
        }
    }

    /**
     * @param  list<string>  $warnings
     */
    protected function inspectCloudLlmEndpoints(array &$warnings): void
    {
        ['provider' => $provider, 'model' => $model] = $this->defaultAgentContext();

        if (in_array($provider, ['fake', ''], true)) {
            return;
        }

        $apiKey = (string) ($this->config->get("limen-ai.providers.{$provider}.api_key") ?? '');

        if ($apiKey === '') {
            return;
        }

        if ($provider === 'openai') {
            $baseUrl = rtrim((string) ($this->config->get('limen-ai.providers.openai.base_url') ?? ''), '/');

            if ($this->looksLikeOllamaEndpoint($baseUrl, $apiKey)) {
                return;
            }

            $this->probeOpenAiCompatibleEndpoint($warnings, 'OpenAI', $baseUrl, $apiKey, $model);

            return;
        }

        if ($provider === 'openrouter') {
            $baseUrl = rtrim((string) ($this->config->get('limen-ai.providers.openrouter.base_url') ?? 'https://openrouter.ai/api/v1'), '/');
            $this->probeOpenAiCompatibleEndpoint($warnings, 'OpenRouter', $baseUrl, $apiKey, $model);

            return;
        }

        if ($provider === 'anthropic') {
            $this->probeAnthropicEndpoint($warnings, $apiKey, $model);

            return;
        }

        if ($provider === 'gemini') {
            $this->probeGeminiEndpoint($warnings, $apiKey, $model);
        }
    }

    /**
     * @param  list<string>  $warnings
     */
    protected function probeOpenAiCompatibleEndpoint(
        array &$warnings,
        string $label,
        string $baseUrl,
        string $apiKey,
        string $model,
    ): void {
        if ($baseUrl === '') {
            $warnings[] = "{$label} provider is selected but no base URL is configured.";

            return;
        }

        try {
            $response = Http::timeout(5)
                ->withToken($apiKey)
                ->acceptJson()
                ->get($baseUrl.'/models');
        } catch (\Throwable $exception) {
            $warnings[] = "{$label} endpoint [{$baseUrl}] is not reachable: {$exception->getMessage()}";

            return;
        }

        if (! $response->successful()) {
            $warnings[] = "{$label} endpoint [{$baseUrl}] returned HTTP {$response->status()}. Check API credentials and network access.";

            return;
        }

        $this->warnWhenModelMissingFromList($warnings, $label, $model, $response->json('data', []));
    }

    /**
     * @param  list<string>  $warnings
     */
    protected function probeAnthropicEndpoint(array &$warnings, string $apiKey, string $model): void
    {
        $baseUrl = 'https://api.anthropic.com/v1';
        $apiVersion = (string) ($this->config->get('limen-ai.providers.anthropic.api_version') ?? '2023-06-01');

        try {
            $response = Http::timeout(5)
                ->withHeaders([
                    'x-api-key' => $apiKey,
                    'anthropic-version' => $apiVersion,
                ])
                ->acceptJson()
                ->get($baseUrl.'/models');
        } catch (\Throwable $exception) {
            $warnings[] = "Anthropic endpoint is not reachable: {$exception->getMessage()}";

            return;
        }

        if (! $response->successful()) {
            $warnings[] = "Anthropic API returned HTTP {$response->status()}. Check ANTHROPIC_API_KEY and network access.";

            return;
        }

        $this->warnWhenModelMissingFromList($warnings, 'Anthropic', $model, $response->json('data', []));
    }

    /**
     * @param  list<string>  $warnings
     */
    protected function probeGeminiEndpoint(array &$warnings, string $apiKey, string $model): void
    {
        $baseUrl = rtrim((string) ($this->config->get('limen-ai.providers.gemini.base_url') ?? 'https://generativelanguage.googleapis.com/v1beta'), '/');

        try {
            $response = Http::timeout(5)
                ->acceptJson()
                ->get($baseUrl.'/models', ['key' => $apiKey]);
        } catch (\Throwable $exception) {
            $warnings[] = "Gemini endpoint is not reachable: {$exception->getMessage()}";

            return;
        }

        if (! $response->successful()) {
            $warnings[] = "Gemini API returned HTTP {$response->status()}. Check GEMINI_API_KEY and network access.";

            return;
        }

        $models = collect($response->json('models', []))
            ->map(function (mixed $entry): ?string {
                if (! is_array($entry)) {
                    return null;
                }

                $name = (string) ($entry['name'] ?? '');

                if ($name === '') {
                    return null;
                }

                return str_starts_with($name, 'models/') ? substr($name, 7) : $name;
            })
            ->filter(fn (?string $name): bool => is_string($name) && $name !== '')
            ->map(fn (string $name): array => ['id' => $name])
            ->values()
            ->all();

        $this->warnWhenModelMissingFromList($warnings, 'Gemini', $model, $models);
    }

    /**
     * @param  list<string>  $warnings
     * @param  list<mixed>  $models
     */
    protected function warnWhenModelMissingFromList(array &$warnings, string $label, string $model, array $models): void
    {
        if ($model === '') {
            return;
        }

        $listedModels = collect($models)
            ->map(function (mixed $entry): ?string {
                if (is_string($entry) && $entry !== '') {
                    return $entry;
                }

                if (! is_array($entry)) {
                    return null;
                }

                $id = $entry['id'] ?? $entry['name'] ?? null;

                return is_string($id) && $id !== '' ? $id : null;
            })
            ->filter(fn (?string $id): bool => is_string($id) && $id !== '')
            ->values()
            ->all();

        if ($listedModels === []) {
            return;
        }

        $matches = in_array($model, $listedModels, true)
            || collect($listedModels)->contains(fn (string $listed): bool => str_ends_with($listed, $model) || str_contains($listed, $model));

        if (! $matches) {
            $warnings[] = "{$label} is reachable but model [{$model}] was not found in the provider model list.";
        }
    }

    /**
     * @return array{provider: string, model: string, agent: array<string, mixed>}
     */
    protected function defaultAgentContext(): array
    {
        $defaultAgent = (string) $this->config->get('limen-ai.default_agent', '');
        $agent = is_array($this->config->get("limen-ai.agents.{$defaultAgent}"))
            ? $this->config->get("limen-ai.agents.{$defaultAgent}")
            : [];

        return [
            'provider' => (string) ($agent['provider'] ?? $this->config->get('limen-ai.providers.default', 'fake')),
            'model' => (string) ($agent['model'] ?? $this->config->get('limen-ai.agent_defaults.model', '')),
            'agent' => $agent,
        ];
    }

    protected function looksLikeOllamaEndpoint(string $baseUrl, string $apiKey): bool
    {
        if (strtolower($apiKey) === 'ollama') {
            return true;
        }

        $normalized = strtolower($baseUrl);

        return str_contains($normalized, ':11434')
            || str_contains($normalized, 'ollama')
            || str_contains($normalized, 'localhost:11434');
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
        return $this->resolvedPersistenceDriver() === PersistenceConfig::DRIVER_DATABASE;
    }

    protected function resolvedPersistenceDriver(): string
    {
        $configured = $this->config->get('limen-ai.persistence.driver');
        $autoDetect = (bool) $this->config->get('limen-ai.persistence.auto_detect', true);
        $conversationsTableExists = false;

        if ($autoDetect) {
            try {
                $conversationsTableExists = $this->database->connection()->getSchemaBuilder()->hasTable('limen_ai_conversations');
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

    protected function resolvedConversationRepositoryClass(): string
    {
        return (string) ($this->config->get('limen-ai.conversations.repository')
            ?: PersistenceConfig::conversationRepositoryClass($this->resolvedPersistenceDriver()));
    }

    protected function resolvedMessageRepositoryClass(): string
    {
        return (string) ($this->config->get('limen-ai.conversations.message_repository')
            ?: PersistenceConfig::messageRepositoryClass($this->resolvedPersistenceDriver()));
    }

    protected function resolvedRunRepositoryClass(): string
    {
        return (string) ($this->config->get('limen-ai.runtime.run_repository')
            ?: PersistenceConfig::runRepositoryClass($this->resolvedPersistenceDriver()));
    }

    protected function shouldFailPersistenceIssues(string $environment): bool
    {
        if (! (bool) $this->config->get('limen-ai.ui.enabled', true)) {
            return false;
        }

        return ! in_array($environment, ['testing'], true);
    }
}
