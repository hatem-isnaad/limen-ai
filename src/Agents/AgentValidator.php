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

        if (! is_array($this->config->get("limen-ai.providers.{$agent->provider()}"))) {
            $errors[] = "Agent [{$key}] references unknown provider [{$agent->provider()}].";
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

        return $errors;
    }
}
