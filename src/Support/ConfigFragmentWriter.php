<?php

namespace LimenAi\Support;

use Illuminate\Filesystem\Filesystem;

final class ConfigFragmentWriter
{
    public function __construct(
        private readonly Filesystem $files,
    ) {}

    /** @param  array<string, mixed>  $payload */
    public function write(string $section, string $key, array $payload): string
    {
        if (! function_exists('config_path')) {
            throw new \RuntimeException('Config path helper is unavailable.');
        }

        $directory = config_path('limen-ai/'.$section);

        if (! $this->files->isDirectory($directory)) {
            $this->files->makeDirectory($directory, 0755, true);
        }

        $path = $directory.'/'.$key.'.php';
        $export = var_export($payload, true);
        $this->files->put($path, "<?php\n\nreturn {$export};\n");

        return $path;
    }
}
