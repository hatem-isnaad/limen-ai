<?php

namespace LimenAi\Support;

use Illuminate\Filesystem\Filesystem;
use InvalidArgumentException;

final class KnowledgeConfigWriter
{
    public function __construct(
        private readonly Filesystem $files,
    ) {}

    /**
     * @param  array<string, array<string, mixed>>  $collections
     */
    public function write(string $path, array $collections): void
    {
        if ($collections === []) {
            throw new InvalidArgumentException('Knowledge collections cannot be empty.');
        }

        $directory = dirname($path);

        if (! $this->files->isDirectory($directory)) {
            $this->files->makeDirectory($directory, 0755, true);
        }

        $export = var_export($collections, true);
        $content = "<?php\n\ndeclare(strict_types=1);\n\n/**\n * Imported Limen AI knowledge collections.\n * Merged into config('limen-ai.knowledge.collections') on boot.\n */\nreturn {$export};\n";

        $this->files->put($path, $content);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function read(string $path): array
    {
        if (! $this->files->exists($path)) {
            return [];
        }

        $collections = require $path;

        return is_array($collections) ? $collections : [];
    }
}
