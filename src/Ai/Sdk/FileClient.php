<?php

namespace LimenAi\Ai\Sdk;

use LimenAi\Knowledge\KnowledgeService;

/** SDK-style file ingest into a knowledge / vector collection. */
final class FileClient
{
    public function __construct(
        private readonly string $collection,
        private readonly KnowledgeService $knowledge,
    ) {}

    /** @param  array<string, mixed>  $metadata */
    public function upload(string $path, ?string $id = null, array $metadata = []): array
    {
        $id ??= basename($path);

        return $this->put($id, $path, $metadata);
    }

    /** @param  array<string, mixed>  $metadata */
    public function put(string $id, string $path, array $metadata = []): array
    {
        if (! is_readable($path)) {
            throw new \InvalidArgumentException("File is not readable: {$path}");
        }

        $content = (string) file_get_contents($path);

        $this->knowledge->upsert($this->collection, $id, $content, array_merge($metadata, [
            'source_path' => $path,
            'mime' => mime_content_type($path) ?: 'application/octet-stream',
        ]));

        return [
            'id' => $id,
            'collection' => $this->collection,
            'bytes' => strlen($content),
        ];
    }
}
