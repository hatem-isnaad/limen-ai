<?php

namespace LimenAi\Tests\Feature;

use Illuminate\Support\Facades\Storage;
use LimenAi\Attachments\AttachmentStatus;
use LimenAi\Attachments\DatabaseAttachmentStore;
use LimenAi\Tests\DatabaseTestCase;

class DatabaseAttachmentStoreTest extends DatabaseTestCase
{
    public function test_it_persists_attachment_metadata_and_file_contents(): void
    {
        Storage::fake('local');

        $conversationId = '11111111-1111-1111-1111-111111111111';
        $this->seedConversation($conversationId);

        $store = new DatabaseAttachmentStore($this->app['db']->connection(), 'local', 'limen-ai/attachments');

        $id = $store->store($conversationId, [
            'original_name' => 'notes.txt',
            'mime_type' => 'text/plain',
            'user_id' => 1,
        ], 'delayed shipment');

        $store->markProcessed($id, 'delayed shipment');

        $attachment = $store->find($id);

        $this->assertSame(AttachmentStatus::PROCESSED, $attachment['status']);
        $this->assertSame('delayed shipment', $store->readContents($id));
        $this->assertCount(1, $store->listForConversation($conversationId));
    }

    protected function seedConversation(string $conversationId): void
    {
        $this->app['db']->table('limen_ai_conversations')->insert([
            'id' => $conversationId,
            'agent_key' => 'example',
            'user_id' => 1,
            'guest_token' => null,
            'title' => null,
            'metadata' => json_encode([], JSON_THROW_ON_ERROR),
            'state' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
