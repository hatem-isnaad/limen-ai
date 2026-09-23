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
                            {--user=1 : User id for the run context}';

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

        if (($run['status'] ?? null) === RunStatus::COMPLETED) {
            $this->line((string) ($run['final_message'] ?? ''));
        }

        return ($run['status'] ?? null) === RunStatus::COMPLETED
            ? self::SUCCESS
            : self::FAILURE;
    }
}
