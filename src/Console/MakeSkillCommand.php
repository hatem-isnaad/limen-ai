<?php

namespace LimenAi\Console;

use Illuminate\Console\Command;
use LimenAi\Console\Concerns\InteractsWithGeneratorNames;

class MakeSkillCommand extends Command
{
    use InteractsWithGeneratorNames;

    protected $signature = 'limen-ai:make:skill
                            {name : The skill key or name}
                            {--tools=* : Tool keys included in the skill}';

    protected $description = 'Create a new Limen AI skill config stub';

    public function handle(StubGenerator $generator): int
    {
        $key = $this->snakeKey($this->argument('name'));
        $title = $this->studlyName($key);
        $tools = $this->option('tools') ?: [];
        $toolsExport = $tools === []
            ? '[]'
            : "[\n            '".implode("',\n            '", $tools)."',\n        ]";
        $targetPath = config('limen-ai.paths.skills', app_path('LimenAi/Skills')).'/'.$key.'.php';

        try {
            $generator->generate('skill-config.stub', [
                'key' => $key,
                'title' => $title,
                'tools' => $toolsExport,
            ], $targetPath);
        } catch (\Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info("Skill config stub [{$key}] created successfully.");
        $this->line("Path: {$targetPath}");
        $this->newLine();
        $this->line('Merge the returned array into config/limen-ai.php under skills.');

        return self::SUCCESS;
    }
}
