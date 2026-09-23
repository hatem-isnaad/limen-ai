<?php

namespace LimenAi\Tests\Unit\Attachments;

use LimenAi\Attachments\NullAttachmentStore;
use LimenAi\Exceptions\AttachmentValidationException;
use LimenAi\Tests\TestCase;

class NullAttachmentStoreTest extends TestCase
{
    public function test_it_rejects_uploads_when_attachments_are_disabled(): void
    {
        $store = new NullAttachmentStore;

        $this->expectException(AttachmentValidationException::class);
        $this->expectExceptionMessage('Attachment uploads are disabled');

        $store->store('conv-1', [
            'original_name' => 'notes.txt',
            'mime_type' => 'text/plain',
        ], 'hello');
    }

    public function test_it_returns_empty_lists_and_null_records(): void
    {
        $store = new NullAttachmentStore;

        $this->assertNull($store->find('missing'));
        $this->assertSame([], $store->listForConversation('conv-1'));
        $this->assertSame(0, $store->countForConversation('conv-1'));
    }
}
