<?php

namespace LimenAi\Providers\Fake;

use LimenAi\Contracts\Providers\LlmProvider;
use LimenAi\Contracts\Providers\LlmResponse;
use LimenAi\Providers\LlmResponseData;

class FakeLlmProvider implements LlmProvider
{
    /** @var list<LlmResponse> */
    private array $queuedResponses = [];

    private ?LlmResponse $defaultResponse = null;

    /** @var list<array<string, mixed>> */
    private array $recordedCalls = [];

    public function name(): string
    {
        return 'fake';
    }

    public function queueResponse(LlmResponse $response): self
    {
        $this->queuedResponses[] = $response;

        return $this;
    }

    public function setDefaultResponse(LlmResponse $response): self
    {
        $this->defaultResponse = $response;

        return $this;
    }

    public function chat(array $messages, array $tools = [], array $options = []): LlmResponse
    {
        $this->recordedCalls[] = [
            'messages' => $messages,
            'tools' => $tools,
            'options' => $options,
        ];

        if ($this->queuedResponses !== []) {
            return array_shift($this->queuedResponses);
        }

        if ($this->defaultResponse !== null) {
            return $this->defaultResponse;
        }

        return LlmResponseData::fromArray([
            'content' => 'Fake LLM response.',
            'usage' => [
                'prompt_tokens' => 10,
                'completion_tokens' => 5,
                'total_tokens' => 15,
            ],
            'finish_reason' => 'stop',
        ]);
    }

    public function supportsStreaming(): bool
    {
        return false;
    }

    /** @return list<array<string, mixed>> */
    public function recordedCalls(): array
    {
        return $this->recordedCalls;
    }

    public function assertChatCalled(): self
    {
        if ($this->recordedCalls === []) {
            throw new \AssertionError('Expected fake LLM chat to be called.');
        }

        return $this;
    }
}
