<?php

namespace LimenAi\Tests\Unit\Knowledge;

use LimenAi\Knowledge\ConfigKnowledgeRetriever;
use LimenAi\Tests\TestCase;

class ConfigKnowledgeRetrieverFaqTest extends TestCase
{
    public function test_it_prefers_faq_question_matches(): void
    {
        $retriever = app(ConfigKnowledgeRetriever::class);

        $results = $retriever->retrieve('reset password', [[
            'key' => 'support',
            'documents' => [
                [
                    'type' => 'faq',
                    'question' => 'How do I reset my password?',
                    'answer' => 'Use forgot password.',
                    'content' => 'Q: How do I reset my password?' . "\n" . 'A: Use forgot password.',
                ],
                [
                    'content' => 'General shipping policy information.',
                ],
            ],
        ]]);

        $this->assertNotEmpty($results);
        $this->assertStringContainsString('forgot password', $results[0]['content']);
        $this->assertGreaterThan(0.75, $results[0]['score']);
    }
}
