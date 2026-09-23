<?php

namespace LimenAi\Contracts\Conversations;

interface ConversationSummarizer
{
    /**
     * @param  list<array<string, mixed>>  $messages
     */
    public function summarize(string $conversationId, array $messages): ?string;
}
