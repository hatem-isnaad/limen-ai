<?php

namespace LimenAi\Facades;

use Illuminate\Support\Facades\Facade;
use LimenAi\LimenAiManager;

/**
 * @method static \LimenAi\LimenAiManager configure(callable $callback)
 * @method static \LimenAi\LimenAiManager tool(string $key, array|string $definition)
 * @method static \LimenAi\LimenAiManager agent(string $key, array|string $definition)
 * @method static \LimenAi\LimenAiManager skill(string $key, array $definition)
 * @method static \LimenAi\LimenAiManager workflow(string $key, array $definition)
 * @method static \LimenAi\LimenAiManager knowledge(string $collectionKey, array $definition)
 * @method static \LimenAi\LimenAiManager faq(string $collectionKey, string $question, string $answer, array $metadata = [])
 * @method static \LimenAi\LimenAiManager provider(string $name, array $settings)
 * @method static string run(string $agentKey, string $conversationId, string $message, ?\LimenAi\Contracts\Runtime\RunContext $context = null)
 * @method static \Generator<int, \LimenAi\Providers\LlmStreamChunk> stream(string $agentKey, string $conversationId, string $message, ?\LimenAi\Contracts\Runtime\RunContext $context = null)
 * @method static array executeTool(string $toolKey, array $input, ?\LimenAi\Contracts\Runtime\RunContext $context = null, string $runId = '', string $conversationId = '', string $agentKey = 'manual')
 * @method static array usageSummary(?string $runId = null)
 * @method static \LimenAi\Registry\LimenAiRegistry registry()
 *
 * @see \LimenAi\LimenAiManager
 */
class LimenAi extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return LimenAiManager::class;
    }
}
