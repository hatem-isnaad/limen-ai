<?php

namespace LimenAi\Tests\Unit\Knowledge;

use LimenAi\Contracts\Knowledge\KnowledgeRepository;
use LimenAi\Contracts\Knowledge\KnowledgeRetriever;
use LimenAi\Knowledge\DefaultAgentKnowledgeRetriever;
use LimenAi\Knowledge\KnowledgeFormatter;
use LimenAi\Tests\TestCase;
use Mockery;

class DefaultAgentKnowledgeRetrieverTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_returns_empty_when_agent_has_no_collections(): void
    {
        $knowledge = Mockery::mock(KnowledgeRepository::class);
        $knowledge->shouldReceive('collectionsForAgent')
            ->once()
            ->with('example')
            ->andReturn([]);

        $retriever = new DefaultAgentKnowledgeRetriever(
            $knowledge,
            Mockery::mock(KnowledgeRetriever::class),
            app(KnowledgeFormatter::class),
            config(),
        );

        $this->assertSame([], $retriever->retrieve('example', 'query'));
    }

    public function test_it_retrieves_and_formats_knowledge_chunks(): void
    {
        $collections = [[
            'key' => 'docs',
            'documents' => [['content' => 'Limen AI documentation.']],
        ]];

        $knowledge = Mockery::mock(KnowledgeRepository::class);
        $knowledge->shouldReceive('collectionsForAgent')
            ->once()
            ->with('example')
            ->andReturn($collections);

        $vector = Mockery::mock(KnowledgeRetriever::class);
        $vector->shouldReceive('retrieve')
            ->once()
            ->with('billing help', $collections, 5)
            ->andReturn([
                ['collection' => 'docs', 'content' => 'Billing FAQ', 'score' => 0.9],
            ]);

        config()->set('limen-ai.knowledge.limit', 5);

        $retriever = new DefaultAgentKnowledgeRetriever(
            $knowledge,
            $vector,
            app(KnowledgeFormatter::class),
            config(),
        );

        $messages = $retriever->retrieve('example', 'billing help');

        $this->assertCount(1, $messages);
        $this->assertSame('system', $messages[0]['role']);
        $this->assertStringContainsString('Billing FAQ', $messages[0]['content']);
    }
}
