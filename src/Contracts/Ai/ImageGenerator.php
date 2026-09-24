<?php

namespace LimenAi\Contracts\Ai;

interface ImageGenerator
{
    /**
     * @param  array<string, mixed>  $options
     * @return list<array{url?: string, b64_json?: string}>
     */
    public function generate(string $prompt, array $options = []): array;
}
