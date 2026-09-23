<?php

namespace LimenAi\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use LimenAi\Attachments\AttachmentService;
use LimenAi\Contracts\Attachments\AttachmentStore;
use LimenAi\Contracts\Conversations\ConversationRepository;
use LimenAi\Http\Concerns\AuthorizesConversationAccess;
use LimenAi\Http\Concerns\BuildsRunContext;

class AttachmentController
{
    use AuthorizesConversationAccess;
    use BuildsRunContext;

    public function __construct(
        private readonly AttachmentService $attachments,
        private readonly AttachmentStore $store,
        private readonly ConversationRepository $conversations,
    ) {}

    public function index(Request $request, string $conversationId): JsonResponse
    {
        $this->authorizeConversationAccess($request, $conversationId);

        $records = array_map(
            fn (array $attachment): array => $this->formatAttachment($attachment),
            $this->store->listForConversation($conversationId),
        );

        return response()->json(['attachments' => $records]);
    }

    public function store(Request $request, string $conversationId): JsonResponse
    {
        $this->authorizeConversationAccess($request, $conversationId);
        abort_if($this->conversations->find($conversationId) === null, 404, 'Conversation not found.');

        $validated = $request->validate([
            'file' => ['required', 'file'],
        ]);

        $attachment = $this->attachments->upload(
            $conversationId,
            $validated['file'],
            $request->user()?->getAuthIdentifier() ? (int) $request->user()->getAuthIdentifier() : null,
        );

        return response()->json(['attachment' => $attachment], 201);
    }

    public function destroy(Request $request, string $attachmentId): JsonResponse
    {
        $attachment = $this->store->find($attachmentId);
        abort_if($attachment === null, 404, 'Attachment not found.');

        $this->authorizeConversationAccess($request, (string) $attachment['conversation_id']);
        $this->attachments->delete($attachmentId);

        return response()->json(['deleted' => true]);
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
            'status' => (string) ($attachment['status'] ?? ''),
            'created_at' => $attachment['created_at'] ?? null,
            'updated_at' => $attachment['updated_at'] ?? null,
        ];
    }
}
