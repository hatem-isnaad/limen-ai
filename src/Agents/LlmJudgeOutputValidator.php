<?php

namespace LimenAi\Agents;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use LimenAi\Contracts\Agents\AgentDefinition;
use LimenAi\Contracts\Agents\OutputValidator;
use LimenAi\Exceptions\OutputValidationException;
use LimenAi\Providers\LlmProviderManager;

class LlmJudgeOutputValidator implements OutputValidator
{
    public function __construct(
        private readonly LlmProviderManager $providers,
        private readonly ConfigRepository $config,
    ) {}

    public function validate(AgentDefinition $agent, string $content): string
    {
        if (! (bool) $this->config->get('limen-ai.quality.semantic_validation', false)) {
            return $content;
        }

        $provider = $this->providers->driver($agent->provider());

        $response = $provider->chat(
            messages: [
                ['role' => 'system', 'content' => $this->judgeInstructions()],
                ['role' => 'user', 'content' => $this->buildPrompt($agent, $content)],
            ],
            tools: [],
            options: array_filter([
                'model' => $agent->model() !== '' ? $agent->model() : null,
                'temperature' => 0,
                'max_tokens' => 160,
            ]),
        );

        $verdict = $this->parseVerdict((string) ($response->content() ?? ''));

        if ($verdict === null) {
            if ((bool) $this->config->get('limen-ai.quality.semantic_validation_strict', false)) {
                throw OutputValidationException::forAgent($agent->key(), 'semantic judge returned an unreadable verdict');
            }

            return $content;
        }

        $minScore = (float) $this->config->get('limen-ai.quality.semantic_min_score', 0.65);

        if (! $verdict['pass'] || $verdict['score'] < $minScore) {
            throw OutputValidationException::forAgent(
                $agent->key(),
                $verdict['reason'] !== '' ? $verdict['reason'] : 'semantic quality check failed',
            );
        }

        return $content;
    }

    protected function judgeInstructions(): string
    {
        return 'You are a reply quality judge for a customer support assistant. '
            .'Evaluate ONLY the assistant reply (not the user question). '
            .'Reply with JSON only: {"pass":true|false,"score":0.0-1.0,"reason":"short"} '
            .'Fail replies that are empty fluff, off-topic, unsafe, or clearly hallucinated facts. '
            .'Pass helpful, grounded, professional replies even if brief.';
    }

    protected function buildPrompt(AgentDefinition $agent, string $content): string
    {
        return "Agent: {$agent->name()}\nAssistant reply:\n{$content}";
    }

    /**
     * @return array{pass: bool, score: float, reason: string}|null
     */
    protected function parseVerdict(string $raw): ?array
    {
        $raw = trim($raw);

        if ($raw === '') {
            return null;
        }

        if (preg_match('/\{.*\}/s', $raw, $matches) === 1) {
            $raw = $matches[0];
        }

        try {
            $payload = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        if (! is_array($payload)) {
            return null;
        }

        return [
            'pass' => (bool) ($payload['pass'] ?? false),
            'score' => (float) ($payload['score'] ?? 0),
            'reason' => trim((string) ($payload['reason'] ?? '')),
        ];
    }
}
