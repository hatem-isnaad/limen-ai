<?php

namespace LimenAi\Attachments;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\UploadedFile;
use LimenAi\Contracts\Attachments\AttachmentStore;
use LimenAi\Contracts\Attachments\AttachmentTextExtractor;
use LimenAi\Contracts\Observability\AuditLogger;
use LimenAi\Events\AttachmentProcessed;
use LimenAi\Events\AttachmentUploaded;
use LimenAi\Exceptions\AttachmentValidationException;

class AttachmentService
{
    public function __construct(
        private readonly AttachmentValidator $validator,
        private readonly AttachmentStore $attachments,
        private readonly AttachmentTextExtractor $extractor,
        private readonly AttachmentVectorIndexer $indexer,
        private readonly AuditLogger $audit,
        private readonly Dispatcher $events,
    ) {}

    public function upload(string $conversationId, UploadedFile $file, ?int $userId = null): array
    {
        $this->validator->validateUpload(
            $conversationId,
            (int) $file->getSize(),
            (string) ($file->getMimeType() ?: 'application/octet-stream'),
        );

        $contents = (string) file_get_contents($file->getRealPath());
        $mimeType = (string) ($file->getMimeType() ?: 'application/octet-stream');
        $originalName = (string) $file->getClientOriginalName();

        $attachmentId = $this->attachments->store($conversationId, [
            'user_id' => $userId,
            'original_name' => $originalName,
            'mime_type' => $mimeType,
            'size_bytes' => strlen($contents),
            'metadata' => [
                'extension' => $file->getClientOriginalExtension(),
            ],
        ], $contents);

        $this->events->dispatch(new AttachmentUploaded($attachmentId, $conversationId, $originalName, $userId));
        $this->audit->log('attachment.uploaded', [
            'attachment_id' => $attachmentId,
            'conversation_id' => $conversationId,
            'mime_type' => $mimeType,
            'size_bytes' => strlen($contents),
        ]);

        return $this->process($attachmentId, $contents, $mimeType, $originalName, $conversationId);
    }

    /** @return list<array<string, mixed>> */
    public function forConversation(string $conversationId, array $attachmentIds = []): array
    {
        $attachments = $this->attachments->listForConversation($conversationId);

        if ($attachmentIds !== []) {
            $attachments = array_values(array_filter(
                $attachments,
                fn (array $attachment): bool => in_array((string) ($attachment['id'] ?? ''), $attachmentIds, true),
            ));
        }

        return array_values(array_filter(
            $attachments,
            fn (array $attachment): bool => ($attachment['status'] ?? null) === AttachmentStatus::PROCESSED,
        ));
    }

    public function delete(string $attachmentId): void
    {
        $attachment = $this->attachments->find($attachmentId);

        if ($attachment === null) {
            throw AttachmentValidationException::notFound($attachmentId);
        }

        $this->attachments->delete($attachmentId);

        $this->audit->log('attachment.deleted', [
            'attachment_id' => $attachmentId,
            'conversation_id' => $attachment['conversation_id'] ?? null,
        ]);
    }

    /** @return array<string, mixed> */
    protected function process(
        string $attachmentId,
        string $contents,
        string $mimeType,
        string $originalName,
        string $conversationId,
    ): array {
        $extractedText = $this->extractor->supports($mimeType, $originalName)
            ? $this->extractor->extract($contents, $mimeType, $originalName)
            : '';

        $this->attachments->markProcessed($attachmentId, $extractedText, [
            'extractor' => $this->extractor::class,
            'indexed' => false,
        ]);

        if ($extractedText !== '') {
            $this->indexer->index($conversationId, $attachmentId, $extractedText);
            $attachment = $this->attachments->find($attachmentId) ?? [];
            $metadata = (array) ($attachment['metadata'] ?? []);
            $metadata['indexed'] = $this->indexer->enabled();
            $this->attachments->markProcessed($attachmentId, $extractedText, $metadata);
        }

        $processed = $this->attachments->find($attachmentId);

        if ($processed === null) {
            throw AttachmentValidationException::notFound($attachmentId);
        }

        $this->events->dispatch(new AttachmentProcessed(
            $attachmentId,
            $conversationId,
            (string) ($processed['status'] ?? AttachmentStatus::FAILED),
        ));

        $this->audit->log('attachment.processed', [
            'attachment_id' => $attachmentId,
            'conversation_id' => $conversationId,
            'status' => $processed['status'] ?? AttachmentStatus::FAILED,
        ]);

        return $this->formatAttachment($processed);
    }

    /** @param  array<string, mixed>  $attachment */
    protected function formatAttachment(array $attachment): array
    {
        return [
            'id' => (string) ($attachment['id'] ?? ''),
            'conversation_id' => (string) ($attachment['conversation_id'] ?? ''),
            'original_name' => (string) ($attachment['original_name'] ?? ''),
            'mime_type' => (string) ($attachment['mime_type'] ?? ''),
            'size_bytes' => (int) ($attachment['size_bytes'] ?? 0),
            'status' => (string) ($attachment['status'] ?? AttachmentStatus::PENDING),
            'created_at' => $attachment['created_at'] ?? null,
            'updated_at' => $attachment['updated_at'] ?? null,
        ];
    }
}
