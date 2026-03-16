<?php

namespace App\Http\Controllers\Api;

use App\Models\Prompt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PromptController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = Prompt::query()->with(['category', 'latestVersion']);

        // Scope to allowed prompts if key has scoped access
        $apiKey = $request->attributes->get('api_key');
        if ($apiKey && $apiKey->prompts()->count() > 0) {
            $query->whereIn('id', $apiKey->prompts()->pluck('prompts.id'));
        }

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }
        if ($categoryId = $request->input('category_id')) {
            $query->where('category_id', $categoryId);
        }
        if ($tag = $request->input('tag')) {
            $query->whereJsonContains('tags', $tag);
        }
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $query->orderByDesc('updated_at');

        return $this->paginated($query, $request);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'type'        => 'in:prompt,fragment',
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id',
            'tags'        => 'nullable|array',
            'tags.*'      => 'string|max:50',
        ]);

        $prompt = Prompt::create([
            'name'        => $validated['name'],
            'type'        => $validated['type'] ?? 'prompt',
            'description' => $validated['description'] ?? null,
            'category_id' => $validated['category_id'] ?? null,
            'tags'        => $validated['tags'] ?? null,
            'created_by'  => $request->user()->id,
        ]);

        return $this->success($prompt->load('category'), 201);
    }

    public function show(Prompt $prompt, Request $request): JsonResponse
    {
        $this->authorizePromptAccess($prompt, $request);

        $prompt->load(['category', 'activeVersion']);
        $prompt->loadCount(['versions', 'results']);

        return $this->success($prompt);
    }

    public function update(Request $request, Prompt $prompt): JsonResponse
    {
        $this->authorizePromptAccess($prompt, $request);

        $validated = $request->validate([
            'name'        => 'sometimes|required|string|max:255',
            'type'        => 'in:prompt,fragment',
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id',
            'tags'        => 'nullable|array',
            'tags.*'      => 'string|max:50',
        ]);

        $prompt->update($validated);

        return $this->success($prompt->fresh()->load('category'));
    }

    private function authorizePromptAccess(Prompt $prompt, Request $request): void
    {
        $apiKey = $request->attributes->get('api_key');
        if ($apiKey && $apiKey->prompts()->count() > 0) {
            if (!$apiKey->prompts()->where('prompts.id', $prompt->id)->exists()) {
                abort(403, 'API key does not have access to this prompt.');
            }
        }
    }
}
