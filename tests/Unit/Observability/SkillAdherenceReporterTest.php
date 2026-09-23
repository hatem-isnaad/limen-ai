<?php

namespace LimenAi\Tests\Unit\Observability;

use LimenAi\Contracts\Observability\AuditLogger;
use LimenAi\Observability\SkillAdherenceReporter;
use LimenAi\Tests\TestCase;
use Mockery;

class SkillAdherenceReporterTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_logs_warning_when_required_phrase_is_missing(): void
    {
        $this->expectNotToPerformAssertions();

        config()->set('limen-ai.quality.skill_adherence_check', true);
        config()->set('limen-ai.skills.general_assistance.required_phrases', ['refund policy']);

        $audit = Mockery::mock(AuditLogger::class);
        $audit->shouldReceive('log')
            ->once()
            ->with('skill.adherence_warning', Mockery::on(function (array $context): bool {
                return $context['agent_key'] === 'example'
                    && str_contains(implode(' ', $context['warnings']), 'refund policy');
            }));

        (new SkillAdherenceReporter(config(), $audit))->report(
            'run-1',
            'example',
            ['general_assistance'],
            'Hello there',
        );
    }
}
