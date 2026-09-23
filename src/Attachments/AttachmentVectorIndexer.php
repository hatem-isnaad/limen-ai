<?php

namespace LimenAi\Attachments;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use LimenAi\Contracts\Knowledge\KnowledgeRetriever;
use LimenAi\Knowledge\KnowledgeService;

class AttachmentVectorIndexer
{
    public function __construct(
        private readonly KnowledgeService $knowledge,
        private readonly KnowledgeRetriever $retriever,
        private readonly ConfigRepository $config,
    ) {}

    public function enabled(): bool
    {
        return (bool) $this->config->get('limen-ai.attachments.rag.enabled', true)
            && ($this->config->get('limen-ai.knowledge.driver') === 'vector');
    }

    public function index(string $conversationId, string $attachmentId, string $extractedText): void
    {
        if (! $this->enabled() || trim($extractedText) === '') {
            return;
        }

        $collection = $this->collectionName($conversationId);
        $chunkSize = max(200, (int) $this->config->get('limen-ai.attachments.rag.chunk_size', 1000));

        foreach ($this->chunkText($extractedText, $chunkSize) as $index => $chunk) {
            $this->knowledge->upsert(
                $collection,
                "{$attachmentId}:{$index}",
                $chunk,
                [
                    'attachment_id' => $attachmentId,
                    'conversation_id' => $conversationId,
                    'chunk' => $index,
                ],
            );
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function search(string $conversationId, string $query, int $limit = 5): array
    {
        if (! $this->enabled()) {
            return [];
        }

        return $this->retriever->retrieve($query, [[
            'key' => $this->collectionName($conversationId),
        ]], $limit);
    }

    protected function collectionName(string $conversationId): string
    {
        $prefix = (string) $this->config->get('limen-ai.attachments.rag.collection_prefix', 'attachment');

        return "{$prefix}:{$conversationId}";
    }

    /**
     * @return list<string>
     */
    protected function chunkText(string $text, int $chunkSize): array
    {
        $chunks = [];
        $length = strlen($text);

        for ($offset = 0; $offset < $length; $offset += $chunkSize) {
            $chunks[] = substr($text, $offset, $chunkSize);
        }

        return $chunks === [] ? [$text] : $chunks;
    }
}
