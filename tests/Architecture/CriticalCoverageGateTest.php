<?php

namespace LimenAi\Tests\Architecture;

use LimenAi\Tests\TestCase;

class CriticalCoverageGateTest extends TestCase
{
    /**
     * Security-critical production classes that must have dedicated tests.
     *
     * @return array<string, list<string>>
     */
    public static function criticalCoverageMatrix(): array
    {
        return [
            'SSRF URL validation' => [
                'src/Security/SsrfUrlValidator.php',
                'tests/Unit/Security/SsrfUrlValidatorTest.php',
            ],
            'Prompt injection sanitization' => [
                'src/Security/PromptInjectionSanitizer.php',
                'tests/Unit/Security/PromptInjectionSanitizerTest.php',
            ],
            'Sensitive data redaction' => [
                'src/Security/SensitiveDataRedactor.php',
                'tests/Unit/Security/SensitiveDataRedactorTest.php',
            ],
            'Tool authorization pipeline' => [
                'src/Tools/ToolPipeline.php',
                'tests/Unit/Tools/ToolPipelineAuthorizationTest.php',
            ],
            'Laravel authorization service' => [
                'src/Authorization/LaravelAuthorizationService.php',
                'tests/Unit/Authorization/LaravelAuthorizationServiceTest.php',
            ],
            'HTTP integration SSRF guard' => [
                'src/Integrations/HttpIntegrationValidator.php',
                'tests/Unit/Integrations/HttpIntegrationValidatorTest.php',
            ],
            'Runtime execution limits' => [
                'src/Runtime/RuntimeLimits.php',
                'tests/Unit/Runtime/RuntimeLimitsTest.php',
            ],
            'Conversation access guard' => [
                'src/Http/Services/ConversationAccessGuard.php',
                'tests/Unit/Http/ConversationAccessGuardTest.php',
            ],
        ];
    }

    /**
     * @dataProvider criticalCoverageMatrix
     *
     * @param  list<string>  $paths
     */
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

    /**
     * @return array<string, list<string>>
     */
    public static function criticalFeatureScenarioMatrix(): array
    {
        return [
            'Unauthenticated agent runs blocked' => ['tests/Feature/AgentAuthorizationTest.php'],
            'Prompt injection hardening in runtime' => ['tests/Feature/PromptInjectionHardeningTest.php'],
            'SSRF blocked on HTTP tools' => ['tests/Feature/HttpToolExecutionTest.php'],
            'Approval pause and resume lifecycle' => ['tests/Feature/RunStateTest.php'],
            'Queued agent run dispatch' => ['tests/Feature/QueuedAgentRunTest.php'],
        ];
    }

    /**
     * @dataProvider criticalFeatureScenarioMatrix
     *
     * @param  list<string>  $paths
     */
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
