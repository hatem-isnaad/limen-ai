<?php

namespace LimenAi\Tests\Unit\Support;

use LimenAi\Support\KnowledgeCollectionImporter;
use LimenAi\Tests\TestCase;

class KnowledgeCollectionImporterTest extends TestCase
{
    public function test_it_imports_json_document_list(): void
    {
        $importer = app(KnowledgeCollectionImporter::class);

        $result = $importer->importFromJson(json_encode([
            ['content' => 'Shipping takes 3–5 days.', 'metadata' => ['topic' => 'shipping']],
            ['content' => 'Returns within 14 days.'],
        ], JSON_THROW_ON_ERROR));

        $this->assertCount(2, $result['documents']);
        $this->assertSame('Shipping takes 3–5 days.', $result['documents'][0]['content']);
        $this->assertSame(['topic' => 'shipping'], $result['documents'][0]['metadata']);
    }

    public function test_it_imports_csv_with_metadata_columns(): void
    {
        $path = sys_get_temp_dir().'/limen-ai-knowledge-'.uniqid('', true).'.csv';
        file_put_contents($path, "content,topic\nShipping takes 3 days,shipping\n");

        $importer = app(KnowledgeCollectionImporter::class);
        $result = $importer->importFromCsv($path);

        @unlink($path);

        $this->assertCount(1, $result['documents']);
        $this->assertSame('shipping', $result['documents'][0]['metadata']['topic']);
    }
}
