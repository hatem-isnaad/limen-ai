<?php

namespace LimenAi\Facades;

use Illuminate\Support\Facades\Facade;
use LimenAi\Support\LimenAiManager;

/**
 * @method static string run(string $agentKey, string $conversationId, string $message, array $context = [])
 * @method static string startWorkflow(string $workflowKey, array $input = [], array $context = [])
 * @method static \LimenAi\Contracts\Agents\AgentRepository agents()
 * @method static \LimenAi\Contracts\Tools\ToolRepository tools()
 * @method static \LimenAi\Contracts\Workflows\WorkflowRepository workflows()
 *
 * @see LimenAiManager
 */
class LimenAi extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return LimenAiManager::class;
    }
}
