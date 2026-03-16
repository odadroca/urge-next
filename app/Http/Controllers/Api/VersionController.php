<?php

namespace App\Http\Controllers\Api;

use App\Models\Prompt;
use App\Services\VersioningService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VersionController extends ApiController
{
    public function __construct(private VersioningService $versioningService) {}

    public function index(Request $request, Prompt $prompt): JsonResponse
    {
        return $this->paginated(
            $prompt->versions()->getQuery(),
            $request
        );
    }

    public function store(Request $request, Prompt $prompt): JsonResponse
    {
        $validated = $request->validate([
            'content'           => 'required|string',
            'commit_message'    => 'nullable|string|max:500',
            'variable_metadata' => 'nullable|array',
        ]);

        $version = $this->versioningService->createVersion(
            $prompt,
            $validated,
            $request->user()
        );

        return $this->success($version, 201);
    }

    public function show(Prompt $prompt, int $version): JsonResponse
    {
        $promptVersion = $prompt->versions()
            ->where('version_number', $version)
            ->firstOrFail();

        return $this->success($promptVersion);
    }
}
