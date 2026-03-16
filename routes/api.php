<?php

use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\PromptController;
use App\Http\Controllers\Api\RenderController;
use App\Http\Controllers\Api\ResultController;
use App\Http\Controllers\Api\VersionController;
use App\Http\Controllers\McpController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('health', HealthController::class);

    Route::middleware('api.auth')->group(function () {
        // Prompts
        Route::get('prompts', [PromptController::class, 'index']);
        Route::post('prompts', [PromptController::class, 'store']);
        Route::get('prompts/{prompt:slug}', [PromptController::class, 'show']);
        Route::patch('prompts/{prompt:slug}', [PromptController::class, 'update']);

        // Versions
        Route::get('prompts/{prompt:slug}/versions', [VersionController::class, 'index']);
        Route::post('prompts/{prompt:slug}/versions', [VersionController::class, 'store']);
        Route::get('prompts/{prompt:slug}/versions/{version}', [VersionController::class, 'show']);

        // Render
        Route::post('prompts/{prompt:slug}/render', [RenderController::class, 'render']);

        // Results
        Route::get('prompts/{prompt:slug}/results', [ResultController::class, 'index']);
        Route::post('prompts/{prompt:slug}/results', [ResultController::class, 'store']);
        Route::get('results/{result}', [ResultController::class, 'show']);
        Route::patch('results/{result}', [ResultController::class, 'update']);

        // MCP
        Route::post('mcp', [McpController::class, 'handle']);
        Route::get('mcp', [McpController::class, 'stream']);
    });
});

// Serve OpenAPI spec
Route::get('openapi.json', function () {
    $path = public_path('openapi.json');
    if (!file_exists($path)) {
        abort(404);
    }
    return response()->file($path, ['Content-Type' => 'application/json']);
});
