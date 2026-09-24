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
            $tool = $this->tools->find($toolKey);

            if ($tool === null) {
                $errors[] = "Agent [{$key}] references unknown tool [{$toolKey}].";
            } elseif (! $tool->isEnabled()) {
                $errors[] = "Agent [{$key}] references disabled tool [{$toolKey}].";
            }
        }

        foreach ($agent->skills() as $skillKey) {
            $skill = $this->skills->find($skillKey);

            if ($skill === null) {
                $errors[] = "Agent [{$key}] references unknown skill [{$skillKey}].";
            } elseif (! $skill->isEnabled()) {
                $errors[] = "Agent [{$key}] references disabled skill [{$skillKey}].";
            }
        }

        foreach ($agent->knowledge() as $collectionKey) {
            if ($this->knowledge->findCollection($collectionKey) === null) {
                $errors[] = "Agent [{$key}] references unknown knowledge collection [{$collectionKey}].";
            }
        }

        return $errors;
    }
}
