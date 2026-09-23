<?php

namespace LimenAi\Tests\Feature;

use Illuminate\Http\UploadedFile;
use LimenAi\Attachments\AttachmentStatus;
use LimenAi\Contracts\Attachments\AttachmentStore;
use LimenAi\Tests\TestCase;

class AttachmentApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('limen-ai.ui.enabled', true);
        config()->set('limen-ai.ui.middleware', []);
    }

    public function test_it_uploads_lists_and_deletes_attachments(): void
    {
        $conversationId = $this->postJson('/limen-ai/conversations')->json('conversation.id');

        $upload = $this->postJson("/limen-ai/conversations/{$conversationId}/attachments", [
            'file' => UploadedFile::fake()->createWithContent('shipment.txt', 'Shipment 12345 is delayed.'),
        ]);

        $upload->assertCreated()
            ->assertJsonPath('attachment.status', AttachmentStatus::PROCESSED);

        $attachmentId = $upload->json('attachment.id');

        $this->getJson("/limen-ai/conversations/{$conversationId}/attachments")
            ->assertOk()
            ->assertJsonPath('attachments.0.id', $attachmentId);

        $this->deleteJson("/limen-ai/attachments/{$attachmentId}")
            ->assertOk()
            ->assertJsonPath('deleted', true);

        $this->assertNull(app(AttachmentStore::class)->find($attachmentId));
    }
}
