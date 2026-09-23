<?php

namespace LimenAi\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use LimenAi\Contracts\Runtime\AgentRuntime;
use LimenAi\Runtime\RunContextData;

class RunAgentJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @param  array<string, mixed>  $contextAttributes
     */
    public function __construct(
        public readonly string $agentKey,
        public readonly string $conversationId,
        public readonly string $userMessage,
        public readonly array $contextAttributes = [],
    ) {}

    public function handle(AgentRuntime $runtime): string
    {
        return $runtime->run(
            $this->agentKey,
            $this->conversationId,
            $this->userMessage,
            RunContextData::make($this->contextAttributes),
        );
    }
}
