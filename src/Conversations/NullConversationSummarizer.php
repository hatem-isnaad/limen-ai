<?php

namespace LimenAi\Conversations;

use LimenAi\Contracts\Conversations\ConversationSummarizer;

class NullConversationSummarizer implements ConversationSummarizer
{
    public function summarize(string $conversationId, array $messages): ?string
    {
        return null;
    }
}
