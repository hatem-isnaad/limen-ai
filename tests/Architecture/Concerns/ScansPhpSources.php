<?php

namespace LimenAi\Tests\Architecture\Concerns;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

trait ScansPhpSources
{
    /**
     * @return list<string>
     */
    protected function phpFilesIn(string $path): array
    {
        if (! is_dir($path)) {
            return [];
        }

        $files = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));

        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $files[] = $file->getPathname();
        }

        sort($files);

        return $files;
    }

    /**
     * @param  list<string>  $forbidden
     */
    protected function assertNoForbiddenImports(string $path, array $forbidden): void
    {
        foreach ($this->phpFilesIn($path) as $file) {
            $contents = file_get_contents($file);

            foreach ($forbidden as $needle) {
                $this->assertStringNotContainsString(
                    $needle,
                    $contents,
                    sprintf('Forbidden import [%s] found in %s', $needle, $file)
                );
            }
        }
    }

    /**
     * @param  list<string>  $allowedPaths  Relative to package root (e.g. src/Broadcasting)
     */
    protected function assertForbiddenImportsOnlyInAllowedPaths(
        string $scanPath,
        string $forbidden,
        array $allowedPaths,
    ): void {
        $packageRoot = dirname(__DIR__, 3);

        foreach ($this->phpFilesIn($scanPath) as $file) {
            $contents = file_get_contents($file);

            if (! str_contains($contents, $forbidden)) {
                continue;
            }

            $relative = str_replace($packageRoot.'/', '', $file);
            $allowed = false;

            foreach ($allowedPaths as $allowedPath) {
                if (str_starts_with($relative, $allowedPath)) {
                    $allowed = true;

                    break;
                }
            }

            $this->assertTrue(
                $allowed,
                sprintf('Forbidden import [%s] found outside allowed paths in %s', $forbidden, $file)
            );
        }
    }
}
