<?php

namespace LimenAi\Ai\Contracts;

interface HasStructuredOutput
{
    /**
     * JSON-schema style map for structured LLM output.
     *
     * @return array<string, mixed>
     */
    public function schema(): array;
}
