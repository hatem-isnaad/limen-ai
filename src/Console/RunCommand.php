<?php

namespace LimenAi\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use LimenAi\Contracts\Agents\AgentRepository;
use LimenAi\Contracts\Runtime\AgentRuntime;
use LimenAi\Contracts\Runtime\RunRepository;
use LimenAi\Runtime\RunContextData;
use LimenAi\Runtime\RunStatus;

class RunCommand extends Command
{
    protected $signature = 'limen-ai:run
                            {agent : The agent key}
                            {--message= : Send one message instead of interactive mode}
                            {--conversation= : Existing conversation id}
                            {--user=1 : User id for the run context}';

    protected $description = 'Start an interactive CLI chat session with a Limen AI agent';

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

        if (! $this->authenticateCliUser()) {
            return self::FAILURE;
        }

        $conversationId = (string) ($this->option('conversation') ?: (string) Str::uuid());
        $singleMessage = $this->option('message');

        if (is_string($singleMessage) && $singleMessage !== '') {
            return $this->sendMessage($runtime, $runs, $agentKey, $conversationId, $singleMessage);
        }

        $this->components->info("Starting interactive session with agent [{$agentKey}] (conversation {$conversationId}).");
        $this->line('Type "exit" or press Enter on an empty line to quit.');
        $this->newLine();

        while (true) {
            $message = (string) $this->ask('You');

            if ($message === '' || in_array(strtolower($message), ['exit', 'quit'], true)) {
                break;
            }

            if ($this->sendMessage($runtime, $runs, $agentKey, $conversationId, $message) !== self::SUCCESS) {
                return self::FAILURE;
            }

            $this->newLine();
        }

        $this->components->info('Session ended.');

        return self::SUCCESS;
    }

    protected function sendMessage(
        AgentRuntime $runtime,
        RunRepository $runs,
        string $agentKey,
        string $conversationId,
        string $message,
    ): int {
        $context = RunContextData::make([
            'user_id' => (int) $this->option('user'),
            'conversation_id' => $conversationId,
            'agent_key' => $agentKey,
        ]);

        $runId = $runtime->run($agentKey, $conversationId, $message, $context);
        $run = $runs->find($runId);

        if ($run === null) {
            $this->components->error('Run was not persisted.');

            return self::FAILURE;
        }

        if (($run['status'] ?? null) !== RunStatus::COMPLETED) {
            $this->components->error("Run [{$runId}] ended with status [{$run['status']}].");

            return self::FAILURE;
        }

        $this->line('<fg=cyan>Assistant:</> '.(string) ($run['final_message'] ?? ''));

        return self::SUCCESS;
    }

    protected function authenticateCliUser(): bool
    {
        if (Auth::check()) {
            return true;
        }

        $userId = (int) $this->option('user');

        if (Auth::loginUsingId($userId)) {
            return true;
        }

        $this->components->error("User [{$userId}] was not found. Seed a user or pass --user=<id>.");

        return false;
    }
}
