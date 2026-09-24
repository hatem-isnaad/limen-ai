<?php

namespace LimenAi\Contracts\Ai;

interface SpeechGenerator
{
    /**
     * @param  array<string, mixed>  $options
     * @return array{format: string, content: string, encoding: 'base64'|'binary'}
     */
    public function generate(string $text, array $options = []): array;
}
