<?php

namespace App\Services;

use App\Models\Prompt;
use App\Models\Result;
use App\Models\User;

class McpToolHandler
{
    public function __construct(
        private TemplateEngine $templateEngine,
        private VersioningService $versioningService,
    ) {}

    public function getServerInfo(): array
    {
        return [
            'name'    => 'urge',
            'version' => '2.0.0',
        ];
    }

    public function getToolDefinitions(): array
    {
        return [
            [
                'name'        => 'get_prompt',
                'description' => 'Get a prompt by slug with its active version content and metadata.',
                'inputSchema' => [
                    'type'       => 'object',
                    'properties' => [
                        'slug'    => ['type' => 'string', 'description' => 'The prompt slug'],
                        'version' => ['type' => 'integer', 'description' => 'Optional version number (defaults to active version)'],
                    ],
                    'required' => ['slug'],
                ],
            ],
            [
                'name'        => 'list_prompts',
                'description' => 'List available prompts with optional filters.',
                'inputSchema' => [
                    'type'       => 'object',
                    'properties' => [
                        'type'     => ['type' => 'string', 'enum' => ['prompt', 'fragment'], 'description' => 'Filter by type'],
                        'category' => ['type' => 'string', 'description' => 'Filter by category name'],
                        'tag'      => ['type' => 'string', 'description' => 'Filter by tag'],
                        'search'   => ['type' => 'string', 'description' => 'Search name and description'],
                    ],
                ],
            ],
            [
                'name'        => 'render_prompt',
                'description' => 'Render a prompt template with variable substitution and include resolution.',
                'inputSchema' => [
                    'type'       => 'object',
                    'properties' => [
                        'slug'      => ['type' => 'string', 'description' => 'The prompt slug'],
                        'version'   => ['type' => 'integer', 'description' => 'Optional version number'],
                        'variables' => ['type' => 'object', 'description' => 'Key-value pairs for template variables'],
                    ],
                    'required' => ['slug'],
                ],
            ],
            [
                'name'        => 'save_version',
                'description' => 'Create a new version of a prompt.',
                'inputSchema' => [
                    'type'       => 'object',
                    'properties' => [
                        'slug'           => ['type' => 'string', 'description' => 'The prompt slug'],
                        'content'        => ['type' => 'string', 'description' => 'The prompt content'],
                        'commit_message' => ['type' => 'string', 'description' => 'Optional commit message'],
                    ],
                    'required' => ['slug', 'content'],
                ],
            ],
            [
                'name'        => 'store_result',
                'description' => 'Store an LLM response result for a prompt version.',
                'inputSchema' => [
                    'type'       => 'object',
                    'properties' => [
                        'slug'          => ['type' => 'string', 'description' => 'The prompt slug'],
                        'version'       => ['type' => 'integer', 'description' => 'The version number'],
                        'response_text' => ['type' => 'string', 'description' => 'The LLM response text'],
                        'provider'      => ['type' => 'string', 'description' => 'Provider name (e.g. OpenAI)'],
                        'model'         => ['type' => 'string', 'description' => 'Model name (e.g. gpt-4)'],
                        'notes'         => ['type' => 'string', 'description' => 'Optional notes'],
                    ],
                    'required' => ['slug', 'version', 'response_text'],
                ],
            ],
            [
                'name'        => 'get_results',
                'description' => 'Get results for a prompt, optionally filtered by version or starred status.',
                'inputSchema' => [
                    'type'       => 'object',
                    'properties' => [
                        'slug'    => ['type' => 'string', 'description' => 'The prompt slug'],
                        'version' => ['type' => 'integer', 'description' => 'Filter by version number'],
                        'starred' => ['type' => 'boolean', 'description' => 'Filter by starred status'],
                        'limit'   => ['type' => 'integer', 'description' => 'Max results (default 10)'],
                    ],
                    'required' => ['slug'],
                ],
            ],
        ];
    }

    public function getResourceDefinitions(): array
    {
        return [
            [
                'uri'         => 'urge://prompts',
                'name'        => 'All Prompts',
                'description' => 'List of all prompts as JSON',
                'mimeType'    => 'application/json',
            ],
            [
                'uri'         => 'urge://prompts/{slug}',
                'name'        => 'Prompt Content',
                'description' => 'Active version content of a prompt',
                'mimeType'    => 'text/plain',
            ],
            [
                'uri'         => 'urge://prompts/{slug}/v/{n}',
                'name'        => 'Prompt Version Content',
                'description' => 'Specific version content of a prompt',
                'mimeType'    => 'text/plain',
            ],
        ];
    }

    // --- Tool Implementations ---

    public function callTool(string $name, array $arguments, ?User $user = null): array
    {
        return match ($name) {
            'get_prompt'    => $this->getPrompt($arguments),
            'list_prompts'  => $this->listPrompts($arguments),
            'render_prompt' => $this->renderPrompt($arguments),
            'save_version'  => $this->saveVersion($arguments, $user),
            'store_result'  => $this->storeResult($arguments, $user),
            'get_results'   => $this->getResults($arguments),
            default         => ['error' => "Unknown tool: {$name}"],
        };
    }

    public function readResource(string $uri): array
    {
        if ($uri === 'urge://prompts') {
            $prompts = Prompt::with('latestVersion')
                ->get()
                ->map(fn ($p) => [
                    'slug'          => $p->slug,
                    'name'          => $p->name,
                    'type'          => $p->type,
                    'description'   => $p->description,
                    'version_count' => $p->versions()->count(),
                ]);

            return [
                'uri'      => $uri,
                'mimeType' => 'application/json',
                'text'     => json_encode($prompts, JSON_PRETTY_PRINT),
            ];
        }

        // urge://prompts/{slug}/v/{n}
        if (preg_match('#^urge://prompts/([^/]+)/v/(\d+)$#', $uri, $m)) {
            $prompt = Prompt::where('slug', $m[1])->firstOrFail();
            $version = $prompt->versions()->where('version_number', (int) $m[2])->firstOrFail();

            return [
                'uri'      => $uri,
                'mimeType' => 'text/plain',
                'text'     => $version->content,
            ];
        }

        // urge://prompts/{slug}
        if (preg_match('#^urge://prompts/([^/]+)$#', $uri, $m)) {
            $prompt = Prompt::where('slug', $m[1])->firstOrFail();
            $version = $prompt->active_version;

            return [
                'uri'      => $uri,
                'mimeType' => 'text/plain',
                'text'     => $version?->content ?? '',
            ];
        }

        return ['error' => "Unknown resource: {$uri}"];
    }

    private function getPrompt(array $args): array
    {
        $prompt = Prompt::where('slug', $args['slug'] ?? '')->first();
        if (!$prompt) {
            return ['error' => 'Prompt not found.'];
        }

        if (!empty($args['version'])) {
            $version = $prompt->versions()->where('version_number', $args['version'])->first();
        } else {
            $version = $prompt->active_version;
        }

        if (!$version) {
            return ['error' => 'Version not found.'];
        }

        return [
            'slug'              => $prompt->slug,
            'name'              => $prompt->name,
            'type'              => $prompt->type,
            'description'       => $prompt->description,
            'version_number'    => $version->version_number,
            'content'           => $version->content,
            'variables'         => $version->variables ?? [],
            'variable_metadata' => $version->variable_metadata,
            'includes'          => $version->includes ?? [],
            'commit_message'    => $version->commit_message,
        ];
    }

    private function listPrompts(array $args): array
    {
        $query = Prompt::query();

        if (!empty($args['type'])) {
            $query->where('type', $args['type']);
        }
        if (!empty($args['category'])) {
            $query->whereHas('category', fn ($q) => $q->where('name', 'like', "%{$args['category']}%"));
        }
        if (!empty($args['tag'])) {
            $query->whereJsonContains('tags', $args['tag']);
        }
        if (!empty($args['search'])) {
            $query->where(function ($q) use ($args) {
                $q->where('name', 'like', "%{$args['search']}%")
                  ->orWhere('description', 'like', "%{$args['search']}%");
            });
        }

        return $query->get()->map(fn ($p) => [
            'slug'          => $p->slug,
            'name'          => $p->name,
            'type'          => $p->type,
            'version_count' => $p->versions()->count(),
        ])->toArray();
    }

    private function renderPrompt(array $args): array
    {
        $prompt = Prompt::where('slug', $args['slug'] ?? '')->first();
        if (!$prompt) {
            return ['error' => 'Prompt not found.'];
        }

        if (!empty($args['version'])) {
            $version = $prompt->versions()->where('version_number', $args['version'])->first();
        } else {
            $version = $prompt->active_version;
        }

        if (!$version) {
            return ['error' => 'Version not found.'];
        }

        $variables = $args['variables'] ?? [];

        return $this->templateEngine->render($version->content, $variables, $version->variable_metadata);
    }

    private function saveVersion(array $args, ?User $user): array
    {
        $prompt = Prompt::where('slug', $args['slug'] ?? '')->first();
        if (!$prompt) {
            return ['error' => 'Prompt not found.'];
        }
        if (!$user) {
            return ['error' => 'User context required for saving versions.'];
        }

        $version = $this->versioningService->createVersion($prompt, [
            'content'        => $args['content'],
            'commit_message' => $args['commit_message'] ?? null,
        ], $user);

        return [
            'version_number' => $version->version_number,
            'variables'      => $version->variables ?? [],
            'includes'       => $version->includes ?? [],
        ];
    }

    private function storeResult(array $args, ?User $user): array
    {
        $prompt = Prompt::where('slug', $args['slug'] ?? '')->first();
        if (!$prompt) {
            return ['error' => 'Prompt not found.'];
        }

        $version = $prompt->versions()->where('version_number', $args['version'])->first();
        if (!$version) {
            return ['error' => 'Version not found.'];
        }

        $result = Result::create([
            'prompt_id'         => $prompt->id,
            'prompt_version_id' => $version->id,
            'source'            => 'mcp',
            'response_text'     => $args['response_text'],
            'provider_name'     => $args['provider'] ?? null,
            'model_name'        => $args['model'] ?? null,
            'notes'             => $args['notes'] ?? null,
            'created_by'        => $user?->id,
        ]);

        return ['id' => $result->id, 'created' => true];
    }

    private function getResults(array $args): array
    {
        $prompt = Prompt::where('slug', $args['slug'] ?? '')->first();
        if (!$prompt) {
            return ['error' => 'Prompt not found.'];
        }

        $query = $prompt->results();

        if (!empty($args['version'])) {
            $version = $prompt->versions()->where('version_number', $args['version'])->first();
            if ($version) {
                $query->where('prompt_version_id', $version->id);
            }
        }

        if (isset($args['starred'])) {
            $query->where('starred', $args['starred']);
        }

        $limit = min($args['limit'] ?? 10, 50);

        return $query->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn ($r) => [
                'id'            => $r->id,
                'version'       => $r->promptVersion?->version_number,
                'provider'      => $r->provider_name,
                'model'         => $r->model_name,
                'response_text' => $r->response_text,
                'rating'        => $r->rating,
                'starred'       => $r->starred,
                'notes'         => $r->notes,
                'created_at'    => $r->created_at->toIso8601String(),
            ])
            ->toArray();
    }
}
