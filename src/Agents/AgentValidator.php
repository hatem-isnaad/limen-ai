<?php

namespace LimenAi\Agents;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use LimenAi\Contracts\Agents\AgentDefinition;
use LimenAi\Contracts\Agents\AgentRepository;
use LimenAi\Contracts\Knowledge\KnowledgeRepository;
use LimenAi\Contracts\Skills\SkillRepository;
use LimenAi\Contracts\Tools\ToolRepository;

class AgentValidator
{
    public function __construct(
        private readonly AgentRepository $agents,
        private readonly ToolRepository $tools,
        private readonly SkillRepository $skills,
        private readonly KnowledgeRepository $knowledge,
        private readonly ConfigRepository $config,
    ) {}

    /** @return list<string> */
    public function validateAll(): array
    {
        $errors = [];

        foreach ($this->agents->all() as $agent) {
            $errors = array_merge($errors, $this->validateAgent($agent));
        }

        return $errors;
    }

    /** @return list<string> */
    public function warningsAll(): array
    {
        $warnings = [];

        foreach ($this->agents->all() as $agent) {
            $warnings = array_merge($warnings, $this->warningsForAgent($agent));
        }

        return $warnings;
    }

    /** @return list<string> */
    public function warnings(string $agentKey): array
    {
        $agent = $this->agents->find($agentKey);

        if ($agent === null) {
            return [];
        }

        return $this->warningsForAgent($agent);
    }

    /** @return list<string> */
    public function validate(string $agentKey): array
    {
        $agent = $this->agents->find($agentKey);

        if ($agent === null) {
            return ["Agent [{$agentKey}] was not found."];
        }

        return $this->validateAgent($agent);
    }

    /** @return list<string> */
    protected function validateAgent(AgentDefinition $agent): array
    {
        $errors = [];
        $key = $agent->key();

        if ($agent->model() === '') {
            $errors[] = "Agent [{$key}] is missing a model.";
        }

        if ($agent->instructions() === '') {
            $errors[] = "Agent [{$key}] is missing instructions.";
        }

        $providerConfig = $this->config->get("limen-ai.providers.{$agent->provider()}");

        if (! is_array($providerConfig)) {
            $errors[] = "Agent [{$key}] references unknown provider [{$agent->provider()}].";
        } else {
            $driver = (string) ($providerConfig['driver'] ?? $agent->provider());
            $driverClass = $this->config->get("limen-ai.providers.drivers.{$driver}");

            if (! is_string($driverClass) || $driverClass === '') {
                $errors[] = "Agent [{$key}] provider [{$agent->provider()}] uses unregistered driver [{$driver}].";
            }
        }

        foreach ($agent->tools() as $toolKey) {
            if ($this->tools->find($toolKey) === null) {
                $errors[] = "Agent [{$key}] references unknown tool [{$toolKey}].";
            }
        }

        foreach ($agent->skills() as $skillKey) {
            if ($this->skills->find($skillKey) === null) {
                $errors[] = "Agent [{$key}] references unknown skill [{$skillKey}].";
            }
        }

        foreach ($agent->knowledge() as $collectionKey) {
            if ($this->knowledge->findCollection($collectionKey) === null) {
                $errors[] = "Agent [{$key}] references unknown knowledge collection [{$collectionKey}].";
            }
        }

        $errors = array_merge($errors, $this->validatePersona($key, $agent->personaConfig()));
        $errors = array_merge($errors, $this->validateMemory($key, $agent->memoryConfig()));
        $errors = array_merge($errors, $this->validateLimits($key, $agent->limits()));
        $errors = array_merge($errors, $this->validateGuestToolSafety($key, $agent));

        return $errors;
    }

    /** @return list<string> */
    protected function validateGuestToolSafety(string $agentKey, AgentDefinition $agent): array
    {
        $authorization = $agent->authorizationConfig();
        $guestAllowed = (bool) ($authorization['guest_allowed'] ?? false);

        if (! $guestAllowed) {
            return [];
        }

        $errors = [];

        foreach ($agent->tools() as $toolKey) {
            $tool = $this->tools->find($toolKey);

            if ($tool === null) {
                continue;
            }

            if ($tool->requiresConfirmation()) {
                $errors[] = "Agent [{$agentKey}] allows guests but tool [{$toolKey}] requires confirmation.";
            }

            $toolConfig = $this->config->get("limen-ai.tools.{$toolKey}");

            if (! is_array($toolConfig) || ! (bool) ($toolConfig['guest_safe'] ?? false)) {
                $errors[] = "Agent [{$agentKey}] allows guests but tool [{$toolKey}] is not marked guest_safe.";
            }
        }

        return $errors;
    }

    /** @return list<string> */
    protected function warningsForAgent(AgentDefinition $agent): array
    {
        $warnings = [];
        $key = $agent->key();
        $toolCount = $this->resolvedToolCount($agent);
        $warnThreshold = max(1, (int) $this->config->get('limen-ai.quality.tool_count_warn', 15));
        $criticalThreshold = max($warnThreshold + 1, (int) $this->config->get('limen-ai.quality.tool_count_critical', 25));

        if ($toolCount >= $criticalThreshold) {
            $warnings[] = "Agent [{$key}] exposes {$toolCount} tools (critical). Split into multiple agents or use workflows. See docs/scaling-agents-and-tools.md.";
        } elseif ($toolCount >= $warnThreshold) {
            $warnings[] = "Agent [{$key}] exposes {$toolCount} tools (high). Consider splitting agents or using a stronger model.";
        }

        return $warnings;
    }

    protected function resolvedToolCount(AgentDefinition $agent): int
    {
        $keys = $agent->tools();

        foreach ($agent->skills() as $skillKey) {
            $skill = $this->skills->find($skillKey);

            if ($skill !== null) {
                $keys = array_merge($keys, $skill->allowedTools());
            }
        }

        return count(array_unique($keys));
    }

    /** @param  array<string, mixed>  $persona
     * @return list<string>
     */
    protected function validatePersona(string $agentKey, array $persona): array
    {
        $errors = [];

        if ($persona === []) {
            return $errors;
        }

        $tone = (string) ($persona['tone'] ?? '');

        if ($tone !== '' && ! in_array($tone, AgentPersonaComposer::allowedTones(), true)) {
            $errors[] = "Agent [{$agentKey}] persona tone [{$tone}] is invalid.";
        }

        $style = (string) ($persona['response_style'] ?? '');

        if ($style !== '' && ! in_array($style, AgentPersonaComposer::allowedResponseStyles(), true)) {
            $errors[] = "Agent [{$agentKey}] persona response_style [{$style}] is invalid.";
        }

        $language = (string) ($persona['language'] ?? '');

        if ($language !== '' && $language !== 'auto' && ! preg_match('/^[a-z]{2}(-[A-Z]{2})?$/', $language)) {
            $errors[] = "Agent [{$agentKey}] persona language [{$language}] must be ISO-639-1 (e.g. en, ar) or auto.";
        }

        $gender = (string) ($persona['gender'] ?? '');

        if ($gender !== '' && ! in_array($gender, AgentPersonaComposer::allowedGenders(), true)) {
            $errors[] = "Agent [{$agentKey}] persona gender [{$gender}] is invalid.";
        }

        $region = (string) ($persona['region'] ?? '');

        if ($region !== '' && ! in_array($region, AgentPersonaComposer::allowedRegions(), true)) {
            $errors[] = "Agent [{$agentKey}] persona region [{$region}] is invalid.";
        }

        $formality = (string) ($persona['formality'] ?? '');

        if ($formality !== '' && ! in_array($formality, AgentPersonaComposer::allowedFormalities(), true)) {
            $errors[] = "Agent [{$agentKey}] persona formality [{$formality}] is invalid.";
        }

        return $errors;
    }

    /** @param  array<string, mixed>  $memory
     * @return list<string>
     */
    protected function validateMemory(string $agentKey, array $memory): array
    {
        $errors = [];
        $allowed = $memory['allowed_keys'] ?? null;

        if ($allowed !== null && ! is_array($allowed)) {
            $errors[] = "Agent [{$agentKey}] memory.allowed_keys must be an array.";
        }

        return $errors;
    }

    /** @param  array<string, mixed>  $limits
     * @return list<string>
     */
    protected function validateLimits(string $agentKey, array $limits): array
    {
        $errors = [];

        if (isset($limits['temperature']) && ! is_numeric($limits['temperature'])) {
            $errors[] = "Agent [{$agentKey}] limits.temperature must be numeric.";
        }

        if (isset($limits['max_tokens']) && (int) $limits['max_tokens'] <= 0) {
            $errors[] = "Agent [{$agentKey}] limits.max_tokens must be greater than zero when set.";
        }

        return $errors;
    }
}
