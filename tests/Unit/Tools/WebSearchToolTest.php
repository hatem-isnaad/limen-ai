<?php

namespace LimenAi\Tests\Unit\Tools;

use Illuminate\Support\Facades\Http;
use LimenAi\Runtime\RunContextData;
use LimenAi\Tests\TestCase;
use LimenAi\Tools\BuiltIn\WebSearchTool;

class WebSearchToolTest extends TestCase
{
    public function test_web_search_returns_payload(): void
    {
        Http::fake([
            '*' => Http::response([
                'AbstractText' => 'Laravel is a PHP framework.',
                'Answer' => '',
                'RelatedTopics' => [],
            ]),
        ]);

        config()->set('limen-ai.provider_tools.web_search.endpoint', 'https://search.test/');

        $context = RunContextData::make(['user_id' => 1])
            ->forToolExecution('run-1', 'conv-1', 'example');

        $result = app(WebSearchTool::class)->handle(['query' => 'Laravel'], $context);

        $this->assertSame('Laravel', $result['query']);
        $this->assertStringContainsString('PHP framework', implode(' ', $result['results']));
    }
}
