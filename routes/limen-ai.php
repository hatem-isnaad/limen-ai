<?php

use Illuminate\Support\Facades\Route;
use LimenAi\Http\Controllers\Api\AgentCatalogController;
use LimenAi\Http\Controllers\Api\AgentDefinitionController;
use LimenAi\Http\Controllers\Api\HealthController;
use LimenAi\Http\Controllers\Api\ApprovalController;
use LimenAi\Http\Controllers\Api\ConversationController;
use LimenAi\Http\Controllers\Api\MessageController;
use LimenAi\Http\Controllers\Api\ObservabilityController;
use LimenAi\Http\Controllers\Api\RunController;
use LimenAi\Http\Controllers\Api\StreamMessageController;
use LimenAi\Http\Controllers\Api\VercelChatController;
use LimenAi\Http\Controllers\Api\AgUiChatController;

$prefix = (string) config('limen-ai.api.route_prefix', config('limen-ai.ui.route_prefix', 'limen-ai'));
$middleware = config('limen-ai.api.middleware', config('limen-ai.ui.middleware', ['web', 'auth']));
$adminMiddleware = config('limen-ai.api.admin_middleware', $middleware);

Route::prefix($prefix)
    ->middleware($middleware)
    ->name('limen-ai.')
    ->group(function (): void {
        Route::get('health', HealthController::class)->name('health');
        Route::get('agents', [AgentCatalogController::class, 'index'])->name('agents.index');
        Route::get('agents/{key}', [AgentCatalogController::class, 'show'])->name('agents.show');
        Route::post('conversations', [ConversationController::class, 'store'])->name('conversations.store');
        Route::get('conversations/{conversationId}', [ConversationController::class, 'show'])->name('conversations.show');
        Route::post('conversations/{conversationId}/messages', [MessageController::class, 'store'])->name('messages.store');
        Route::post('conversations/{conversationId}/messages/stream', [StreamMessageController::class, 'store'])
            ->name('messages.stream');

        if ((bool) config('limen-ai.protocols.vercel_chat', false)) {
            Route::post('conversations/{conversationId}/chat', [VercelChatController::class, 'store'])
                ->name('messages.vercel');
        }

        if ((bool) config('limen-ai.protocols.ag_ui', false)) {
            Route::post('conversations/{conversationId}/ag-ui', [AgUiChatController::class, 'store'])
                ->name('messages.ag-ui');
        }
        Route::get('runs/{runId}', [RunController::class, 'show'])->name('runs.show');
        Route::get('runs/{runId}/observability', [ObservabilityController::class, 'show'])->name('runs.observability');
        Route::post('approvals/{approvalId}/approve', [ApprovalController::class, 'approve'])->name('approvals.approve');
        Route::post('approvals/{approvalId}/reject', [ApprovalController::class, 'reject'])->name('approvals.reject');
    });

Route::prefix($prefix)
    ->middleware($adminMiddleware)
    ->name('limen-ai.admin.')
    ->group(function (): void {
        Route::get('agent-definitions', [AgentDefinitionController::class, 'index'])->name('agent-definitions.index');
        Route::post('agent-definitions', [AgentDefinitionController::class, 'store'])->name('agent-definitions.store');
        Route::get('agent-definitions/{key}', [AgentDefinitionController::class, 'show'])->name('agent-definitions.show');
        Route::put('agent-definitions/{key}', [AgentDefinitionController::class, 'update'])->name('agent-definitions.update');
        Route::patch('agent-definitions/{key}', [AgentDefinitionController::class, 'update']);
        Route::delete('agent-definitions/{key}', [AgentDefinitionController::class, 'destroy'])->name('agent-definitions.destroy');
    });
