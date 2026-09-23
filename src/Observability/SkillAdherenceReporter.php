<?php

namespace LimenAi\Observability;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use LimenAi\Contracts\Observability\AuditLogger;

final class SkillAdherenceReporter
{
    public function __construct(
        private readonly ConfigRepository $config,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  list<string>  $skillKeys
     */
    public function report(string $runId, string $agentKey, array $skillKeys, string $finalMessage): void
    {
        if (! (bool) $this->config->get('limen-ai.quality.skill_adherence_check', false)) {
            return;
        }

        if ($skillKeys === []) {
            return;
        }

        $haystack = mb_strtolower($finalMessage);
        $warnings = [];

        foreach ($skillKeys as $skillKey) {
            $skillConfig = $this->config->get("limen-ai.skills.{$skillKey}");

            if (! is_array($skillConfig)) {
                continue;
            }

            foreach ($skillConfig['required_phrases'] ?? [] as $phrase) {
                if (! is_string($phrase) || $phrase === '') {
                    continue;
                }

                if (! str_contains($haystack, mb_strtolower($phrase))) {
                    $warnings[] = "Skill [{$skillKey}] missing required phrase [{$phrase}].";
                }
            }

            foreach ($skillConfig['must_not_contain'] ?? [] as $phrase) {
                if (! is_string($phrase) || $phrase === '') {
                    continue;
                }

                if (str_contains($haystack, mb_strtolower($phrase))) {
                    $warnings[] = "Skill [{$skillKey}] reply contains forbidden phrase [{$phrase}].";
                }
            }
        }

        if ($warnings === []) {
            return;
        }

        $this->audit->log('skill.adherence_warning', [
            'run_id' => $runId,
            'agent_key' => $agentKey,
            'skill_keys' => $skillKeys,
            'warnings' => $warnings,
        ]);
    }
}
