<?php

namespace LimenAi\Contracts\Attachments;

interface AgentAttachmentRetriever
{
    /**
     * @param  list<string>  $attachmentIds
     * @return list<array<string, mixed>>
     */
    public function retrieve(string $conversationId, string $query, array $attachmentIds = []): array;
}
