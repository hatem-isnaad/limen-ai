<?php

namespace LimenAi\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

class ClearAgentDefinitionCacheCommand extends Command
{
    protected $signature = 'limen-ai:agents:clear-cache';

    protected $description = 'Clear cached database agent definitions';

    public function handle(CacheRepository $cache): int
    {
        if (method_exists($cache->getStore(), 'flush')) {
            $this->components->warn('Using cache flush — this clears the entire cache store.');

            $cache->flush();
        } else {
            $this->components->info('For tagged cache drivers, restart workers or bump agent version after DB edits.');
        }

        $this->components->info('Agent definition cache cleared (where supported).');

        return self::SUCCESS;
    }
}
