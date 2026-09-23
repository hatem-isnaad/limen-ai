<?php

use Illuminate\Support\Facades\Route;
use LimenAi\Http\Controllers\Api\ApprovalController;
use LimenAi\Http\Controllers\Api\AttachmentController;
use LimenAi\Http\Controllers\Api\ConversationController;
use LimenAi\Http\Controllers\Api\MessageController;
use LimenAi\Http\Controllers\Api\ObservabilityController;
use LimenAi\Http\Controllers\Api\RunController;

$prefix = (string) config('limen-ai.ui.route_prefix', 'limen-ai');
$middleware = config('limen-ai.ui.middleware', ['web', 'auth']);

Route::prefix($prefix)
    ->middleware($middleware)
    ->name('limen-ai.')
    ->group(function (): void {
        Route::post('conversations', [ConversationController::class, 'store'])->name('conversations.store');
        Route::get('conversations/{conversationId}', [ConversationController::class, 'show'])->name('conversations.show');
        Route::post('conversations/{conversationId}/messages', [MessageController::class, 'store'])->name('messages.store');
        Route::get('conversations/{conversationId}/attachments', [AttachmentController::class, 'index'])->name('attachments.index');
        Route::post('conversations/{conversationId}/attachments', [AttachmentController::class, 'store'])->name('attachments.store');
        Route::delete('attachments/{attachmentId}', [AttachmentController::class, 'destroy'])->name('attachments.destroy');
        Route::get('runs/{runId}', [RunController::class, 'show'])->name('runs.show');
        Route::get('runs/{runId}/observability', [ObservabilityController::class, 'show'])->name('runs.observability');
        Route::post('approvals/{approvalId}/approve', [ApprovalController::class, 'approve'])->name('approvals.approve');
        Route::post('approvals/{approvalId}/reject', [ApprovalController::class, 'reject'])->name('approvals.reject');
    });
