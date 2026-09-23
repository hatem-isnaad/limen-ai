<?php

namespace LimenAi\Contracts\Providers;

interface EmbeddingProvider
{
    public function name(): string;

    /**
     * @return list<list<float>>
     */
    public function embed(array $texts): array;
}
