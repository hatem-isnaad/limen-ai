<?php

namespace LimenAi\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use LimenAi\Contracts\Runtime\AgentRuntime;
use LimenAi\Runtime\RunContextData;

class ResumeAgentRunJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @param  array<string, mixed>  $contextAttributes
     */
    public function __construct(
        public readonly string $runId,
        public readonly array $contextAttributes = [],
    ) {}

    public function handle(AgentRuntime $runtime): void
    {
        $runtime->resume($this->runId, RunContextData::make($this->contextAttributes));
    }
}
