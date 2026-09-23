<?php

namespace LimenAi\Providers\Fake;

use LimenAi\Contracts\Providers\EmbeddingProvider;

class FakeEmbeddingProvider implements EmbeddingProvider
{
    public function name(): string
    {
        return 'fake';
    }

    public function embed(array $texts): array
    {
        return array_map(
            fn (string $text): array => $this->embeddingFor($text),
            $texts,
        );
    }

    /** @return list<float> */
    private function embeddingFor(string $text): array
    {
        $dimensions = 8;
        $hash = crc32($text);
        $vector = [];

        for ($i = 0; $i < $dimensions; $i++) {
            $vector[] = (($hash >> ($i % 32)) & 0xFF) / 255;
        }

        return $vector;
    }
}
