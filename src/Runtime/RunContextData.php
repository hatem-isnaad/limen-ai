<?php

namespace LimenAi\Runtime;

use LimenAi\Contracts\Runtime\ToolExecutionContext;

final class RunContextData implements ToolExecutionContext
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        private readonly ?int $userId = null,
        private readonly ?string $guestToken = null,
        private readonly array $metadata = [],
        private readonly string $locale = 'en',
        private readonly ?string $runId = null,
        private readonly ?string $conversationId = null,
        private readonly ?string $agentKey = null,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function make(array $attributes = []): self
    {
        return new self(
            userId: isset($attributes['user_id']) ? (int) $attributes['user_id'] : null,
            guestToken: isset($attributes['guest_token']) ? (string) $attributes['guest_token'] : null,
            metadata: $attributes['metadata'] ?? [],
            locale: (string) ($attributes['locale'] ?? app()->getLocale()),
            runId: isset($attributes['run_id']) ? (string) $attributes['run_id'] : null,
            conversationId: isset($attributes['conversation_id']) ? (string) $attributes['conversation_id'] : null,
            agentKey: isset($attributes['agent_key']) ? (string) $attributes['agent_key'] : null,
        );
    }

    public function userId(): ?int
    {
        return $this->userId;
    }

    public function guestToken(): ?string
    {
        return $this->guestToken;
    }

    public function metadata(): array
    {
        return $this->metadata;
    }

    public function locale(): string
    {
        return $this->locale;
    }

    public function runId(): string
    {
        return $this->runId ?? '';
    }

    public function conversationId(): string
    {
        return $this->conversationId ?? '';
    }

    public function agentKey(): string
    {
        return $this->agentKey ?? '';
    }

    public function forToolExecution(string $runId, string $conversationId, string $agentKey): self
    {
        return new self(
            userId: $this->userId,
            guestToken: $this->guestToken,
            metadata: $this->metadata,
            locale: $this->locale,
            runId: $runId,
            conversationId: $conversationId,
            agentKey: $agentKey,
        );
    }

    /** @param  array<string, mixed>  $metadata */
    public function withMetadata(array $metadata): self
    {
        return new self(
            userId: $this->userId,
            guestToken: $this->guestToken,
            metadata: array_merge($this->metadata, $metadata),
            locale: $this->locale,
            runId: $this->runId,
            conversationId: $this->conversationId,
            agentKey: $this->agentKey,
        );
    }

    public function withLocale(string $locale): self
    {
        return new self(
            userId: $this->userId,
            guestToken: $this->guestToken,
            metadata: $this->metadata,
            locale: $locale,
            runId: $this->runId,
            conversationId: $this->conversationId,
            agentKey: $this->agentKey,
        );
    }

    public function withApprovalGranted(): self
    {
        return $this->withMetadata(['approval_granted' => true]);
    }
}
