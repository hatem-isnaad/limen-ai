<?php

namespace LimenAi\Tests\Unit\Attachments;

use LimenAi\Attachments\AttachmentStatus;
use LimenAi\Attachments\InMemoryAttachmentStore;
use LimenAi\Tests\TestCase;

class InMemoryAttachmentStoreTest extends TestCase
{
    public function test_it_stores_processes_and_lists_attachments(): void
    {
        $store = new InMemoryAttachmentStore();

        $id = $store->store('conv-1', [
            'original_name' => 'invoice.txt',
            'mime_type' => 'text/plain',
            'size_bytes' => 12,
        ], 'line one');

        $store->markProcessed($id, 'line one', ['indexed' => false]);

        $attachment = $store->find($id);

        $this->assertSame('conv-1', $attachment['conversation_id']);
        $this->assertSame(AttachmentStatus::PROCESSED, $attachment['status']);
        $this->assertSame('line one', $store->readContents($id));
        $this->assertCount(1, $store->listForConversation('conv-1'));

        $store->delete($id);

        $this->assertNull($store->find($id));
    }
}
