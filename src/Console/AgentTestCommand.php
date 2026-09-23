<?php

namespace LimenAi\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use LimenAi\Contracts\Agents\AgentRepository;
use LimenAi\Contracts\Runtime\AgentRuntime;
use LimenAi\Contracts\Runtime\RunRepository;
use LimenAi\Runtime\RunContextData;
use LimenAi\Runtime\RunStatus;

class AgentTestCommand extends Command
{
    protected $signature = 'limen-ai:agent:test
                            {agent : The agent key to test}
                            {--message=Hello from Limen AI CLI : Message to send}
                            {--conversation= : Conversation id (generated when omitted)}
                            {--user=1 : User id for the run context}
                            {--expect-contains=* : Substring that must appear in the final assistant message}
                            {--expect-not-contains=* : Forbidden substrings in the final assistant message}
                            {--min-length= : Minimum character length for the final assistant message}';

    protected $description = 'Run an agent once against the configured provider (fake by default)';

    public function handle(
        AgentRepository $agents,
        AgentRuntime $runtime,
        RunRepository $runs,
    ): int {
        $agentKey = (string) $this->argument('agent');

        if ($agents->find($agentKey) === null) {
            $this->components->error("Agent [{$agentKey}] is not registered.");

            return self::FAILURE;
        }

        $userId = (int) $this->option('user');

        if (! Auth::check() && ! Auth::loginUsingId($userId)) {
            $this->components->error("User [{$userId}] was not found. Seed a user or pass --user=<id>.");

            return self::FAILURE;
        }

        $conversationId = (string) ($this->option('conversation') ?: 'cli-'.uniqid());
        $context = RunContextData::make([
            'user_id' => (int) $this->option('user'),
            'conversation_id' => $conversationId,
            'agent_key' => $agentKey,
        ]);

        $runId = $runtime->run(
            $agentKey,
            $conversationId,
            (string) $this->option('message'),
            $context,
        );

        $run = $runs->find($runId);

        if ($run === null) {
            $this->components->error('Run was not persisted.');

            return self::FAILURE;
        }

        $this->components->info("Run [{$runId}] completed with status [{$run['status']}].");

        $finalMessage = (string) ($run['final_message'] ?? '');

        if (($run['status'] ?? null) === RunStatus::COMPLETED) {
            $this->line($finalMessage);
        }

        if (($run['status'] ?? null) !== RunStatus::COMPLETED) {
            return self::FAILURE;
        }

        foreach ((array) $this->option('expect-contains') as $expected) {
            if (! is_string($expected) || $expected === '') {
                continue;
            }

            if (! str_contains($finalMessage, $expected)) {
                $this->components->error("Expected assistant output to contain [{$expected}].");
                $this->line(mb_substr($finalMessage, 0, 500));

                return self::FAILURE;
            }
        }

        foreach ((array) $this->option('expect-not-contains') as $forbidden) {
            if (! is_string($forbidden) || $forbidden === '') {
                continue;
            }

            if (str_contains($finalMessage, $forbidden)) {
                $this->components->error("Assistant output must not contain [{$forbidden}].");
                $this->line(mb_substr($finalMessage, 0, 500));

                return self::FAILURE;
            }
        }

        $minLength = $this->option('min-length');

        if ($minLength !== null && $minLength !== '') {
            $required = max(1, (int) $minLength);

            if (mb_strlen($finalMessage) < $required) {
                $this->components->error("Assistant output must be at least {$required} characters.");

                return self::FAILURE;
            }
        }

        return self::SUCCESS;
    }
}
