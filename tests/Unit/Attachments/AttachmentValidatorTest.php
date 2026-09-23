<?php

namespace LimenAi\Tests\Unit\Attachments;

use LimenAi\Attachments\AttachmentValidator;
use LimenAi\Attachments\InMemoryAttachmentStore;
use LimenAi\Exceptions\AttachmentValidationException;
use LimenAi\Tests\TestCase;

class AttachmentValidatorTest extends TestCase
{
    public function test_it_enforces_size_limit(): void
    {
        config()->set('limen-ai.attachments.max_size_kb', 1);

        $validator = new AttachmentValidator(config(), new InMemoryAttachmentStore());

        $this->expectException(AttachmentValidationException::class);
        $validator->validateUpload('conv-1', 2048, 'text/plain');
    }

    public function test_it_enforces_conversation_attachment_count(): void
    {
        config()->set('limen-ai.attachments.max_count', 1);

        $store = new InMemoryAttachmentStore();
        $store->store('conv-1', ['original_name' => 'a.txt', 'mime_type' => 'text/plain'], 'existing');

        $validator = new AttachmentValidator(config(), $store);

        $this->expectException(AttachmentValidationException::class);
        $validator->validateUpload('conv-1', 10, 'text/plain');
    }
}
