<?php

namespace LimenAi\Ai;

final class AgentResponse implements \Stringable
{
    public function __construct(
        public readonly string $text,
        public readonly string $runId,
        public readonly string $conversationId,
    ) {}

    public function __toString(): string
    {
        return $this->text;
    }
}
