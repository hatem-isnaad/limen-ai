<?php

namespace LimenAi\Contracts\Ai;

interface AudioTranscriber
{
    /**
     * @param  array<string, mixed>  $options
     */
    public function transcribe(string $path, array $options = []): string;
}
