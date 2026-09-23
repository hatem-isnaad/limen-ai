<?php

namespace LimenAi\Tests\Feature;

use Illuminate\Http\UploadedFile;
use LimenAi\Attachments\AttachmentService;
use LimenAi\Contracts\Runtime\RunRepository;
use LimenAi\Providers\Fake\FakeLlmProvider;
use LimenAi\Providers\LlmResponseData;
use LimenAi\Runtime\RunStatus;
use LimenAi\Tests\TestCase;

class AttachmentRuntimeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('limen-ai.ui.enabled', true);
        config()->set('limen-ai.ui.middleware', []);
    }

    public function test_it_injects_attachment_context_into_agent_run(): void
    {
        $conversationId = $this->postJson('/limen-ai/conversations')->json('conversation.id');

        $attachment = app(AttachmentService::class)->upload(
            $conversationId,
            UploadedFile::fake()->createWithContent('status.txt', 'Shipment 12345 is delayed in Riyadh.'),
            1,
        );

        app(FakeLlmProvider::class)->setDefaultResponse(LlmResponseData::fromArray([
            'content' => 'I see the shipment delay details.',
            'finish_reason' => 'stop',
        ]));

        $response = $this->postJson("/limen-ai/conversations/{$conversationId}/messages", [
            'message' => 'What does the attachment say?',
            'attachment_ids' => [$attachment['id']],
        ]);

        $run = app(RunRepository::class)->find($response->json('run_id'));

        $this->assertSame(RunStatus::COMPLETED, $run['status']);
        $this->assertStringContainsString('Shipment 12345 is delayed', json_encode($run['messages'] ?? []));
    }
}
