<?php

namespace LimenAi\Tests\Unit\Knowledge;

use LimenAi\Knowledge\ConfigKnowledgeRetriever;
use LimenAi\Tests\TestCase;

class ConfigKnowledgeRetrieverTest extends TestCase
{
    public function test_it_scores_documents_by_keyword_overlap(): void
    {
        $retriever = new ConfigKnowledgeRetriever();

        $results = $retriever->retrieve('echo tool testing', [[
            'key' => 'getting_started',
            'documents' => [
                ['content' => 'Use the example_echo tool to echo messages during development and testing.'],
                ['content' => 'Unrelated warehouse inventory policy.'],
            ],
        ]], 5);

        $this->assertCount(1, $results);
        $this->assertSame('getting_started', $results[0]['collection']);
        $this->assertStringContainsString('example_echo', $results[0]['content']);
    }

    public function test_it_returns_empty_results_for_empty_query(): void
    {
        $retriever = new ConfigKnowledgeRetriever();

        $results = $retriever->retrieve('', [[
            'key' => 'getting_started',
            'documents' => [
                ['content' => 'Some knowledge content.'],
            ],
        ]]);

        $this->assertSame([], $results);
    }

    public function test_it_respects_the_result_limit(): void
    {
        $retriever = new ConfigKnowledgeRetriever();

        $results = $retriever->retrieve('limen tool', [[
            'key' => 'docs',
            'documents' => [
                ['content' => 'Limen AI tool pipeline documentation.'],
                ['content' => 'Limen AI authorization tool checks.'],
                ['content' => 'Limen AI runtime tool loop.'],
            ],
        ]], 2);

        $this->assertCount(2, $results);
    }
}
