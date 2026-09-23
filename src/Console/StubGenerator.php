<?php

namespace LimenAi\Console;

use Illuminate\Filesystem\Filesystem;
use RuntimeException;

class StubGenerator
{
    public function __construct(
        private readonly Filesystem $files,
        private readonly string $stubPath,
    ) {}

    /**
     * @param  array<string, string>  $replacements
     */
    public function generate(string $stub, array $replacements, string $targetPath): string
    {
        $stubFile = $this->stubPath.'/'.$stub;

        if (! $this->files->exists($stubFile)) {
            throw new RuntimeException("Stub [{$stub}] not found.");
        }

        $contents = $this->files->get($stubFile);

        foreach ($replacements as $search => $replace) {
            $contents = str_replace('{{ '.$search.' }}', $replace, $contents);
        }

        $this->files->ensureDirectoryExists(dirname($targetPath));

        if ($this->files->exists($targetPath)) {
            throw new RuntimeException("File already exists at [{$targetPath}].");
        }

        $this->files->put($targetPath, $contents);

        return $targetPath;
    }
}
