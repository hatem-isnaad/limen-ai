<?php

namespace LimenAi\Contracts\Providers;

interface LlmResponse
{
    public function content(): ?string;

    /** @return list<array<string, mixed>> */
    public function toolCalls(): array;

    /** @return array<string, int> */
    public function usage(): array;

    public function finishReason(): ?string;
}
