<?php

namespace LimenAi\Contracts\Providers;

use Generator;
use LimenAi\Providers\LlmStreamChunk;

interface StreamingLlmProvider extends LlmProvider
{
    /**
     * @param  list<array<string, mixed>>  $messages
     * @param  list<array<string, mixed>>  $tools
     * @param  array<string, mixed>  $options
     * @return Generator<int, LlmStreamChunk>
     */
    public function streamChat(array $messages, array $tools = [], array $options = []): Generator;
}
