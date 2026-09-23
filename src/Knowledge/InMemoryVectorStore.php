<?php

namespace LimenAi\Knowledge;

use LimenAi\Contracts\Knowledge\VectorStore;

class InMemoryVectorStore implements VectorStore
{
    /** @var array<string, list<array<string, mixed>>> */
    private array $vectors = [];

    public function upsert(string $collection, array $vectors): void
    {
        $this->vectors[$collection] ??= [];

        foreach ($vectors as $vector) {
            $id = (string) ($vector['id'] ?? '');

            if ($id === '') {
                continue;
            }

            $this->vectors[$collection][$id] = $vector;
        }
    }

    public function search(string $collection, array $embedding, int $limit = 5): array
    {
        $results = [];

        foreach ($this->vectors[$collection] ?? [] as $vector) {
            $score = $this->cosineSimilarity($embedding, (array) ($vector['embedding'] ?? []));

            if ($score <= 0) {
                continue;
            }

            $results[] = array_merge($vector, ['score' => $score]);
        }

        usort($results, fn (array $left, array $right): int => $right['score'] <=> $left['score']);

        return array_slice($results, 0, $limit);
    }

    /** @param  list<float>  $left  @param  list<float>  $right */
    protected function cosineSimilarity(array $left, array $right): float
    {
        if ($left === [] || $right === []) {
            return 0.0;
        }

        $length = min(count($left), count($right));
        $dot = 0.0;
        $leftMagnitude = 0.0;
        $rightMagnitude = 0.0;

        for ($index = 0; $index < $length; $index++) {
            $dot += $left[$index] * $right[$index];
            $leftMagnitude += $left[$index] ** 2;
            $rightMagnitude += $right[$index] ** 2;
        }

        if ($leftMagnitude === 0.0 || $rightMagnitude === 0.0) {
            return 0.0;
        }

        return $dot / (sqrt($leftMagnitude) * sqrt($rightMagnitude));
    }
}
