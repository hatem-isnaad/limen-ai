<?php

namespace LimenAi\Console;

use Illuminate\Console\Command;
use Illuminate\Database\ConnectionResolverInterface;
use LimenAi\Agents\AgentValidator;
use LimenAi\Support\EnvironmentDoctor;
use LimenAi\Support\PackageVersion;
use LimenAi\Support\PersistenceConfig;

class ChecklistCommand extends Command
{
    protected $signature = 'limen-ai:checklist';

    protected $description = 'Print a first-run checklist for Limen AI host integration';

    public function handle(
        AgentValidator $agents,
        EnvironmentDoctor $doctor,
        ConnectionResolverInterface $database,
    ): int {
        $environment = (string) app()->environment();
        $items = [];

        $items[] = $this->check(
            'Configuration published',
            config()->has('limen-ai'),
        );

        $defaultAgent = (string) config('limen-ai.default_agent', '');
        $items[] = $this->check(
            'Default agent valid',
            $defaultAgent !== '' && $agents->validate($defaultAgent) === [],
            $defaultAgent !== '' ? $defaultAgent : 'not configured',
        );

        $tableExists = false;

        try {
            $tableExists = $database->connection()->getSchemaBuilder()->hasTable('limen_ai_conversations');
        } catch (\Throwable) {
            $tableExists = false;
        }

        $items[] = $this->check('Limen AI tables migrated', $tableExists);

        $driver = PersistenceConfig::resolveDriver(
            is_string(config('limen-ai.persistence.driver')) ? config('limen-ai.persistence.driver') : null,
            (bool) config('limen-ai.persistence.auto_detect', true),
            $tableExists,
        );

        $items[] = $this->check(
            'Persistence uses database',
            $driver === PersistenceConfig::DRIVER_DATABASE,
            $driver,
        );

        $doctorReport = $doctor->inspect($environment);
        $items[] = $this->check(
            'Doctor environment healthy',
            $doctorReport['failures'] === [],
            $doctorReport['failures'] !== [] ? implode('; ', $doctorReport['failures']) : 'ok',
        );

        $publishedUi = function_exists('resource_path')
            ? PackageVersion::publishedUiVersionFile(resource_path())
            : null;
        $packageUi = PackageVersion::uiVersionFile();
        $uiOk = true;
        $uiDetail = 'not published';

        if (is_string($publishedUi) && is_file($publishedUi)) {
            $publishedVersion = trim((string) file_get_contents($publishedUi));
            $packageVersion = is_file($packageUi)
                ? trim((string) file_get_contents($packageUi))
                : PackageVersion::VERSION;
            $uiOk = $publishedVersion === '' || $publishedVersion === $packageVersion;
            $uiDetail = "published {$publishedVersion}, package {$packageVersion}";
        }

        $items[] = $this->check('Published UI version current', $uiOk, $uiDetail);

        $this->components->info('Limen AI first-run checklist');

        foreach ($items as $item) {
            $status = $item['ok'] ? '<fg=green>PASS</>' : '<fg=red>FAIL</>';
            $detail = $item['detail'] !== '' ? " ({$item['detail']})" : '';
            $this->line("{$status} {$item['label']}{$detail}");
        }

        return collect($items)->contains(fn (array $item): bool => ! $item['ok'])
            ? self::FAILURE
            : self::SUCCESS;
    }

    /**
     * @return array{label: string, ok: bool, detail: string}
     */
    protected function check(string $label, bool $ok, string $detail = ''): array
    {
        return [
            'label' => $label,
            'ok' => $ok,
            'detail' => $detail,
        ];
    }
}
