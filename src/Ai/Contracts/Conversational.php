<?php

namespace LimenAi\Ai\Contracts;

use LimenAi\Ai\Messages\Message;

interface Conversational
{
    /**
     * @return iterable<int, Message>
     */
    public function messages(): iterable;
}
