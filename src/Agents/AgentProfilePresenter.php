<?php

namespace LimenAi\Agents;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use LimenAi\Contracts\Agents\AgentDefinition;

class AgentProfilePresenter
{
    public function __construct(
        private readonly ConfigRepository $config,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function present(AgentDefinition $agent): array
    {
        $persona = $agent->personaConfig();
        $ui = is_array($persona['ui'] ?? null) ? $persona['ui'] : [];
        $region = (string) ($persona['region'] ?? $this->config->get('limen-ai.agent_defaults.persona.region', 'international'));
        $gender = (string) ($persona['gender'] ?? $this->config->get('limen-ai.agent_defaults.persona.gender', 'neutral'));

        return [
            'key' => $agent->key(),
            'name' => $agent->name(),
            'description' => $agent->description(),
            'provider' => $agent->provider(),
            'model' => $agent->model(),
            'version' => $agent->version(),
            'persona' => [
                'display_name' => (string) ($persona['display_name'] ?? $agent->name()),
                'tone' => (string) ($persona['tone'] ?? $this->config->get('limen-ai.quality.default_tone', 'professional')),
                'language' => (string) ($persona['language'] ?? $this->config->get('limen-ai.quality.default_language', 'en')),
                'response_style' => (string) ($persona['response_style'] ?? 'concise'),
                'gender' => $gender,
                'gender_label' => $this->presetLabel('genders', $gender),
                'region' => $region,
                'region_label' => $this->regionLabel($region),
                'formality' => (string) ($persona['formality'] ?? 'neutral'),
                'voice' => (string) ($persona['voice'] ?? ''),
            ],
            'limits' => [
                'temperature' => $agent->limits()['temperature'] ?? null,
                'max_tokens' => $agent->limits()['max_tokens'] ?? null,
                'max_history_messages' => $agent->limits()['max_history_messages'] ?? null,
            ],
            'ui' => [
                'title' => (string) ($ui['title'] ?? $persona['display_name'] ?? $agent->name()),
                'subtitle' => (string) ($ui['subtitle'] ?? $agent->description() ?? ''),
                'welcome_message' => (string) ($ui['welcome_message'] ?? ''),
                'avatar_url' => $ui['avatar_url'] ?? null,
            ],
        ];
    }

    protected function presetLabel(string $group, string $key): string
    {
        $preset = $this->config->get("limen-ai.agent_presets.{$group}.{$key}", []);

        if (is_array($preset) && isset($preset['label'])) {
            return (string) $preset['label'];
        }

        return ucfirst(str_replace('_', ' ', $key));
    }

    protected function regionLabel(string $region): string
    {
        $preset = $this->config->get("limen-ai.agent_presets.regions.{$region}", []);

        if (is_array($preset) && isset($preset['label'])) {
            return (string) $preset['label'];
        }

        return strtoupper($region);
    }
}
