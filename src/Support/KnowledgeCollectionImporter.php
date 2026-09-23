<?php

namespace LimenAi\Support;

use InvalidArgumentException;

final class KnowledgeCollectionImporter
{
    /**
     * @return array{
     *     documents: list<array{content: string, metadata: array<string, mixed>}>,
     *     name: string|null,
     *     description: string|null
     * }
     */
    public function importFromFile(string $path): array
    {
        if (! is_file($path)) {
            throw new InvalidArgumentException("Knowledge import file [{$path}] does not exist.");
        }

        $extension = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));

        return match ($extension) {
            'json' => $this->importFromJson((string) file_get_contents($path)),
            'csv' => $this->importFromCsv($path),
            default => throw new InvalidArgumentException('Knowledge import supports JSON (.json) and CSV (.csv) files only.'),
        };
    }

    /**
     * @return array{
     *     documents: list<array{content: string, metadata: array<string, mixed>}>,
     *     name: string|null,
     *     description: string|null
     * }
     */
    public function importFromJson(string $json): array
    {
        $decoded = json_decode($json, true);

        if (! is_array($decoded)) {
            throw new InvalidArgumentException('Knowledge JSON must decode to an array.');
        }

        if (array_is_list($decoded)) {
            return [
                'documents' => $this->normalizeDocuments($decoded),
                'name' => null,
                'description' => null,
            ];
        }

        if (isset($decoded['documents']) && is_array($decoded['documents'])) {
            return [
                'documents' => $this->normalizeDocuments($decoded['documents']),
                'name' => is_string($decoded['name'] ?? null) ? $decoded['name'] : null,
                'description' => is_string($decoded['description'] ?? null) ? $decoded['description'] : null,
            ];
        }

        if (isset($decoded['content'])) {
            return [
                'documents' => $this->normalizeDocuments([$decoded]),
                'name' => null,
                'description' => null,
            ];
        }

        throw new InvalidArgumentException('Knowledge JSON must be a document list, a {documents: [...]} object, or a single {content: ...} object.');
    }

    /**
     * @return array{
     *     documents: list<array{content: string, metadata: array<string, mixed>}>,
     *     name: string|null,
     *     description: string|null
     * }
     */
    public function importFromCsv(string $path): array
    {
        $handle = fopen($path, 'r');

        if ($handle === false) {
            throw new InvalidArgumentException("Unable to read CSV file [{$path}].");
        }

        $header = fgetcsv($handle);

        if ($header === false) {
            fclose($handle);

            throw new InvalidArgumentException('Knowledge CSV file is empty.');
        }

        $header = array_map(
            fn (mixed $column): string => strtolower(trim((string) $column)),
            $header,
        );

        $contentIndex = array_search('content', $header, true);

        if ($contentIndex === false) {
            fclose($handle);

            throw new InvalidArgumentException('Knowledge CSV must include a [content] column.');
        }

        $metadataIndex = array_search('metadata', $header, true);
        $documents = [];

        while (($row = fgetcsv($handle)) !== false) {
            $content = trim((string) ($row[$contentIndex] ?? ''));

            if ($content === '') {
                continue;
            }

            $metadata = [];

            if ($metadataIndex !== false) {
                $rawMetadata = trim((string) ($row[$metadataIndex] ?? ''));

                if ($rawMetadata !== '') {
                    $decoded = json_decode($rawMetadata, true);

                    if (is_array($decoded)) {
                        $metadata = $this->normalizeMetadata($decoded);
                    }
                }
            }

            foreach ($header as $index => $column) {
                if (in_array($column, ['content', 'metadata'], true)) {
                    continue;
                }

                $value = trim((string) ($row[$index] ?? ''));

                if ($value !== '') {
                    $metadata[$column] = $value;
                }
            }

            $documents[] = [
                'content' => $content,
                'metadata' => $metadata,
            ];
        }

        fclose($handle);

        if ($documents === []) {
            throw new InvalidArgumentException('Knowledge CSV did not contain any document rows.');
        }

        return [
            'documents' => $documents,
            'name' => null,
            'description' => null,
        ];
    }

    /**
     * @param  list<mixed>  $rows
     * @return list<array{content: string, metadata: array<string, mixed>}>
     */
    protected function normalizeDocuments(array $rows): array
    {
        $documents = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                throw new InvalidArgumentException('Each knowledge document must be an object/array.');
            }

            $content = trim((string) ($row['content'] ?? $row['text'] ?? $row['body'] ?? ''));

            if ($content === '') {
                continue;
            }

            $metadata = isset($row['metadata']) && is_array($row['metadata'])
                ? $this->normalizeMetadata($row['metadata'])
                : [];

            $documents[] = [
                'content' => $content,
                'metadata' => $metadata,
            ];
        }

        if ($documents === []) {
            throw new InvalidArgumentException('Knowledge import did not contain any documents with content.');
        }

        return $documents;
    }

    /**
     * @param  array<mixed, mixed>  $metadata
     * @return array<string, mixed>
     */
    protected function normalizeMetadata(array $metadata): array
    {
        $normalized = [];

        foreach ($metadata as $key => $value) {
            if (! is_string($key) || $key === '') {
                continue;
            }

            if (is_scalar($value) || $value === null) {
                $normalized[$key] = $value;
            }
        }

        return $normalized;
    }
}
