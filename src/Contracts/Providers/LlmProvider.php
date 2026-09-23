<?php

namespace LimenAi\Contracts\Providers;

interface LlmProvider
{
    public function name(): string;

    /**
     * @param  list<array<string, mixed>>  $messages
     * @param  list<array<string, mixed>>  $tools
     */
    public function chat(array $messages, array $tools = [], array $options = []): LlmResponse;

    public function supportsStreaming(): bool;
}
