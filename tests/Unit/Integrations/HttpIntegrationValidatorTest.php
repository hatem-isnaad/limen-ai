<?php

namespace LimenAi\Tests\Unit\Integrations;

use LimenAi\Integrations\HttpIntegrationValidator;
use LimenAi\Tests\TestCase;

class HttpIntegrationValidatorTest extends TestCase
{
    public function test_it_validates_example_http_tool_configuration(): void
    {
        $errors = app(HttpIntegrationValidator::class)->validateAll();

        $this->assertSame([], $errors);
    }

    public function test_it_reports_missing_connectors(): void
    {
        config()->set('limen-ai.tools.invalid_http_tool', [
            'name' => 'Invalid HTTP Tool',
            'integration' => [
                'connector' => 'missing_connector',
                'path' => '/status',
            ],
            'input_schema' => [],
        ]);

        $errors = app(HttpIntegrationValidator::class)->validateAll();

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('missing_connector', implode("\n", $errors));
    }
}
