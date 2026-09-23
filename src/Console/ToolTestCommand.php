<?php

namespace LimenAi\Console;

use Illuminate\Console\Command;
use LimenAi\Contracts\Tools\ToolRepository;
use LimenAi\Runtime\RunContextData;
use LimenAi\Tools\ToolPipeline;

class ToolTestCommand extends Command
{
    protected $signature = 'limen-ai:tool:test
                            {tool : The tool key to execute}
                            {--input={} : JSON input payload}
                            {--user=1 : User id for the run context}';

    protected $description = 'Execute a tool with sample input using the tool pipeline';

    public function handle(ToolRepository $tools, ToolPipeline $pipeline): int
    {
        $toolKey = (string) $this->argument('tool');

        if ($tools->find($toolKey) === null) {
            $this->components->error("Tool [{$toolKey}] is not registered.");

            return self::FAILURE;
        }

        $input = json_decode((string) $this->option('input'), true);

        if (! is_array($input)) {
            $this->components->error('The --input option must be valid JSON.');

            return self::FAILURE;
        }

        $context = RunContextData::make([
            'user_id' => (int) $this->option('user'),
            'conversation_id' => 'cli-tool-test',
            'agent_key' => 'cli',
        ])->forToolExecution('cli-run', 'cli-tool-test', 'cli');

        $result = $pipeline->execute($toolKey, $input, $context);

        $this->components->info("Tool [{$toolKey}] executed successfully.");
        $this->line(json_encode($result->output(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        return self::SUCCESS;
    }
}
