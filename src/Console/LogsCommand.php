<?php

namespace LimenAi\Console;

use Illuminate\Console\Command;
use LimenAi\Observability\AuditBuffer;

class LogsCommand extends Command
{
    protected $signature = 'limen-ai:logs
                            {--run= : Filter audit entries by run id}
                            {--limit=20 : Maximum number of entries to display}';

    protected $description = 'Show recent Limen AI audit log entries from the in-memory buffer';

    public function handle(AuditBuffer $buffer): int
    {
        $runId = $this->option('run');
        $limit = max(1, (int) $this->option('limit'));

        $entries = $runId !== null && $runId !== ''
            ? $buffer->forRun((string) $runId)
            : $buffer->all();

        if ($entries === []) {
            $this->components->warn('No audit log entries found.');

            return self::SUCCESS;
        }

        $entries = array_slice(array_reverse($entries), 0, $limit);

        foreach ($entries as $entry) {
            $this->line(sprintf(
                '[%s] %s',
                $entry['logged_at'] ?? 'unknown',
                $entry['action'] ?? 'unknown',
            ));

            $context = $entry['context'] ?? [];

            if ($context !== []) {
                $this->line('  '.json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            }
        }

        return self::SUCCESS;
    }
}
