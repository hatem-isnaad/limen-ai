<?php

namespace LimenAi\Ai\Contracts;

use Stringable;

/**
 * Laravel AI SDK–style agent contract. Resolved through Limen runtime (workflows, auth, tools).
 */
interface Agent
{
    public function key(): string;

    public function instructions(): string|Stringable;
}
