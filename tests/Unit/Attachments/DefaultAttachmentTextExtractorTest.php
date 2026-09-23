<?php

namespace LimenAi\Tests\Unit\Attachments;

use LimenAi\Attachments\DefaultAttachmentTextExtractor;
use LimenAi\Tests\TestCase;

class DefaultAttachmentTextExtractorTest extends TestCase
{
    public function test_it_extracts_plain_text_and_json(): void
    {
        $extractor = new DefaultAttachmentTextExtractor();

        $this->assertSame('hello world', $extractor->extract('hello world', 'text/plain', 'notes.txt'));
        $this->assertStringContainsString('"status"', $extractor->extract('{"status":"delayed"}', 'application/json', 'data.json'));
        $this->assertSame('', $extractor->extract('binary', 'application/pdf', 'file.pdf'));
    }
}
