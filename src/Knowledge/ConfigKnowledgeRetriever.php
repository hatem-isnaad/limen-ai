<?php

namespace LimenAi\Knowledge;

use LimenAi\Contracts\Knowledge\KnowledgeRetriever;

class ConfigKnowledgeRetriever implements KnowledgeRetriever
{
    public function retrieve(string $query, array $collections, int $limit = 5): array
    {
        $results = [];

        foreach ($collections as $collection) {
            foreach ($collection['documents'] ?? [] as $document) {
                $content = (string) ($document['content'] ?? '');

                if ($content === '') {
                    continue;
                }

                $score = $this->score($query, $content);

                if ($score <= 0) {
                    continue;
                }

                $results[] = [
                    'collection' => (string) ($collection['key'] ?? ''),
                    'content' => $content,
                    'score' => $score,
                    'metadata' => $document['metadata'] ?? [],
                ];
            }
        }

        usort($results, fn (array $left, array $right): int => $right['score'] <=> $left['score']);

        return array_slice($results, 0, $limit);
    }

    protected function score(string $query, string $content): float
    {
        $query = mb_strtolower(trim($query));
        $contentLower = mb_strtolower($content);

        if ($query === '') {
            return 0.0;
        }

        if (str_contains($contentLower, $query)) {
            return 1.0;
        }

        $terms = preg_split('/\s+/', $query) ?: [];
        $matched = 0;

        foreach ($terms as $term) {
            if ($term !== '' && str_contains($contentLower, $term)) {
                $matched++;
            }
        }

        if ($matched === 0) {
            return 0.0;
        }

        return $matched / max(count($terms), 1);
    }
}
