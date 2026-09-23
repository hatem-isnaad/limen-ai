<?php

namespace LimenAi\Tests\Feature;

use Illuminate\Support\Facades\File;
use LimenAi\Tests\TestCase;

class ImportKnowledgeCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        $path = $this->knowledgeConfigPath();

        if (is_file($path)) {
            @unlink($path);
        }

        parent::tearDown();
    }

    public function test_it_writes_imported_documents_to_knowledge_config_file(): void
    {
        $source = sys_get_temp_dir().'/limen-ai-import-'.uniqid('', true).'.json';
        file_put_contents($source, json_encode([
            ['content' => 'Warehouse hours: Sun–Thu 8:00–17:00.'],
        ], JSON_THROW_ON_ERROR));

        $this->artisan('limen-ai:import:knowledge', [
            'file' => $source,
            '--collection' => 'product_help',
            '--name' => 'Product Help',
        ])->assertSuccessful();

        @unlink($source);

        $path = $this->knowledgeConfigPath();
        $this->assertFileExists($path);

        $collections = require $path;

        $this->assertArrayHasKey('product_help', $collections);
        $this->assertSame('Product Help', $collections['product_help']['name']);
        $this->assertCount(1, $collections['product_help']['documents']);
    }

    public function test_it_appends_documents_when_append_option_is_used(): void
    {
        $path = $this->knowledgeConfigPath();
        File::ensureDirectoryExists(dirname($path));
        file_put_contents($path, "<?php\n\nreturn ".var_export([
            'product_help' => [
                'name' => 'Product Help',
                'description' => '',
                'documents' => [
                    ['content' => 'Existing document.', 'metadata' => []],
                ],
            ],
        ], true).";\n");

        $source = sys_get_temp_dir().'/limen-ai-import-'.uniqid('', true).'.json';
        file_put_contents($source, json_encode([
            ['content' => 'New document.'],
        ], JSON_THROW_ON_ERROR));

        $this->artisan('limen-ai:import:knowledge', [
            'file' => $source,
            '--collection' => 'product_help',
            '--append' => true,
        ])->assertSuccessful();

        @unlink($source);

        $collections = require $path;

        $this->assertCount(2, $collections['product_help']['documents']);
    }

    protected function knowledgeConfigPath(): string
    {
        return config_path('limen-ai-knowledge.php');
    }
}
