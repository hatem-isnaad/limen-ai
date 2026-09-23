<?php

namespace LimenAi\Contracts\Runtime;

interface RunContext
{
    public function userId(): ?int;

    public function guestToken(): ?string;

    /** @return array<string, mixed> */
    public function metadata(): array;

    public function locale(): string;
}
