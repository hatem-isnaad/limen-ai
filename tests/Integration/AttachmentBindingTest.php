<?php

namespace LimenAi\Tests\Integration;

use LimenAi\Attachments\AttachmentService;
use LimenAi\Attachments\InMemoryAttachmentStore;
use LimenAi\Contracts\Attachments\AgentAttachmentRetriever;
use LimenAi\Contracts\Attachments\AttachmentStore;
use LimenAi\Tests\TestCase;

class AttachmentBindingTest extends TestCase
{
    public function test_attachment_pipeline_bindings_are_registered(): void
    {
        $this->assertInstanceOf(InMemoryAttachmentStore::class, app(AttachmentStore::class));
        $this->assertInstanceOf(AttachmentService::class, app(AttachmentService::class));
        $this->assertInstanceOf(AgentAttachmentRetriever::class, app(AgentAttachmentRetriever::class));
    }
}
