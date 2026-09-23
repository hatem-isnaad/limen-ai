<?php

namespace LimenAi\Tests\Architecture;

use LimenAi\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class CriticalCoverageGateTest extends TestCase
{
    public static function criticalCoverageMatrix(): array
    {
        return [
            'SSRF URL validation' => [
                'SSRF URL validation',
                [
                    'src/Security/SsrfUrlValidator.php',
                    'tests/Unit/Security/SsrfUrlValidatorTest.php',
                ],
            ],
            'Prompt injection sanitization' => [
                'Prompt injection sanitization',
                [
                    'src/Security/PromptInjectionSanitizer.php',
                    'tests/Unit/Security/PromptInjectionSanitizerTest.php',
                ],
            ],
            'Sensitive data redaction' => [
                'Sensitive data redaction',
                [
                    'src/Security/SensitiveDataRedactor.php',
                    'tests/Unit/Security/SensitiveDataRedactorTest.php',
                ],
            ],
            'Tool authorization pipeline' => [
                'Tool authorization pipeline',
                [
                    'src/Tools/ToolPipeline.php',
                    'src/Tools/ToolInstanceAuthorizer.php',
                    'tests/Unit/Tools/ToolPipelineAuthorizationTest.php',
                    'tests/Unit/Tools/ToolInstanceAuthorizerTest.php',
                ],
            ],
            'Laravel authorization service' => [
                'Laravel authorization service',
                [
                    'src/Authorization/LaravelAuthorizationService.php',
                    'tests/Unit/Authorization/LaravelAuthorizationServiceTest.php',
                ],
            ],
            'HTTP integration SSRF guard' => [
                'HTTP integration SSRF guard',
                [
                    'src/Integrations/HttpIntegrationValidator.php',
                    'tests/Unit/Integrations/HttpIntegrationValidatorTest.php',
                ],
            ],
            'Runtime execution limits' => [
                'Runtime execution limits',
                [
                    'src/Runtime/RuntimeLimits.php',
                    'tests/Unit/Runtime/RuntimeLimitsTest.php',
                ],
            ],
            'Conversation access guard' => [
                'Conversation access guard',
                [
                    'src/Http/Services/ConversationAccessGuard.php',
                    'tests/Unit/Http/ConversationAccessGuardTest.php',
                ],
            ],
            'Attachment upload validation' => [
                'Attachment upload validation',
                [
                    'src/Attachments/AttachmentValidator.php',
                    'tests/Unit/Attachments/AttachmentValidatorTest.php',
                ],
            ],
            'Attachment text extraction' => [
                'Attachment text extraction',
                [
                    'src/Attachments/DefaultAttachmentTextExtractor.php',
                    'tests/Unit/Attachments/DefaultAttachmentTextExtractorTest.php',
                ],
            ],
        ];
    }

    #[DataProvider('criticalCoverageMatrix')]
    public function test_security_critical_paths_have_test_coverage(string $scenario, array $paths): void
    {
        $root = dirname(__DIR__, 2);

        foreach ($paths as $path) {
            $this->assertFileExists(
                $root.'/'.$path,
                sprintf('Missing %s for scenario [%s]', $path, $scenario)
            );
        }
    }

    public static function criticalFeatureScenarioMatrix(): array
    {
        return [
            'Unauthenticated agent runs blocked' => [
                'Unauthenticated agent runs blocked',
                ['tests/Feature/AgentAuthorizationTest.php'],
            ],
            'Prompt injection hardening in runtime' => [
                'Prompt injection hardening in runtime',
                ['tests/Feature/PromptInjectionHardeningTest.php'],
            ],
            'SSRF blocked on HTTP tools' => [
                'SSRF blocked on HTTP tools',
                ['tests/Feature/HttpToolExecutionTest.php'],
            ],
            'Approval pause and resume lifecycle' => [
                'Approval pause and resume lifecycle',
                ['tests/Feature/RunStateTest.php'],
            ],
            'Queued agent run dispatch' => [
                'Queued agent run dispatch',
                ['tests/Feature/QueuedAgentRunTest.php'],
            ],
            'Attachment upload API' => [
                'Attachment upload API',
                ['tests/Feature/AttachmentApiTest.php'],
            ],
            'Attachment runtime injection' => [
                'Attachment runtime injection',
                ['tests/Feature/AttachmentRuntimeTest.php'],
            ],
        ];
    }

    #[DataProvider('criticalFeatureScenarioMatrix')]
    public function test_security_critical_feature_scenarios_have_tests(string $scenario, array $paths): void
    {
        $root = dirname(__DIR__, 2);

        foreach ($paths as $path) {
            $this->assertFileExists(
                $root.'/'.$path,
                sprintf('Missing feature test %s for scenario [%s]', $path, $scenario)
            );
        }
    }
}
