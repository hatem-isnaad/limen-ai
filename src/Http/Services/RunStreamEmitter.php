<?php

namespace LimenAi\Http\Services;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use LimenAi\Contracts\Runtime\RunRepository;
use LimenAi\Contracts\Runtime\RunStatusReader;
use LimenAi\Runtime\RunStatus;

class RunStreamEmitter
{
    public function __construct(
        private readonly RunRepository $runs,
        private readonly RunStatusReader $statusReader,
        private readonly ConfigRepository $config,
    ) {}

    public function emit(string $runId): void
    {
        $pollIntervalMs = max(100, (int) $this->config->get('limen-ai.streaming.poll_interval_ms', 400));
        $maxWaitSeconds = max(5, (int) $this->config->get('limen-ai.streaming.max_wait_seconds', 120));
        $chunkChars = max(8, (int) $this->config->get('limen-ai.streaming.chunk_chars', 24));
        $startedAt = microtime(true);

        while (true) {
            $run = $this->runs->find($runId);

            if ($run === null) {
                $this->sendEvent('error', ['message' => 'Run not found.']);

                return;
            }

            $status = (string) ($run['status'] ?? '');
            $terminal = $this->statusReader->isTerminal($runId);

            $this->sendEvent('status', [
                'run_id' => $runId,
                'status' => $status,
                'terminal' => $terminal,
            ]);

            if ($terminal) {
                if ($status === RunStatus::COMPLETED) {
                    $finalMessage = (string) ($run['final_message'] ?? '');

                    if ($finalMessage !== '' && $this->streamingChunksEnabled()) {
                        foreach ($this->chunkText($finalMessage, $chunkChars) as $delta) {
                            $this->sendEvent('delta', ['content' => $delta]);
                        }
                    }

                    $this->sendEvent('completed', [
                        'run_id' => $runId,
                        'status' => $status,
                        'final_message' => $run['final_message'] ?? null,
                    ]);
                } elseif ($status === RunStatus::WAITING_APPROVAL) {
                    $this->sendEvent('approval_required', [
                        'run_id' => $runId,
                        'status' => $status,
                    ]);
                } else {
                    $this->sendEvent('failed', [
                        'run_id' => $runId,
                        'status' => $status,
                        'error' => $run['error'] ?? 'Agent run failed.',
                    ]);
                }

                return;
            }

            if ((microtime(true) - $startedAt) >= $maxWaitSeconds) {
                $this->sendEvent('timeout', [
                    'run_id' => $runId,
                    'status' => $status,
                ]);

                return;
            }

            usleep($pollIntervalMs * 1000);
        }
    }

    /**
     * @return list<string>
     */
    protected function chunkText(string $text, int $chunkChars): array
    {
        if ($text === '') {
            return [];
        }

        return str_split($text, $chunkChars) ?: [];
    }

    protected function streamingChunksEnabled(): bool
    {
        return (bool) $this->config->get('limen-ai.streaming.chunk_final_message', true);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function sendEvent(string $event, array $payload): void
    {
        echo 'event: '.$event."\n";
        echo 'data: '.json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)."\n\n";

        if (function_exists('ob_flush')) {
            @ob_flush();
        }

        flush();
    }
}
