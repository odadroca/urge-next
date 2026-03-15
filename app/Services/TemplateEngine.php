<?php

namespace App\Services;

use App\Models\Prompt;

class TemplateEngine
{
    private const PATTERN = '/\{\{([a-zA-Z_][a-zA-Z0-9_]*)\}\}/';
    private const INCLUDE_PATTERN = '/\{\{>([a-zA-Z0-9_-]+)\}\}/';

    public function extractVariables(string $content): array
    {
        preg_match_all(self::PATTERN, $content, $matches);
        return array_values(array_unique($matches[1]));
    }

    public function extractIncludes(string $content): array
    {
        preg_match_all(self::INCLUDE_PATTERN, $content, $matches);
        return array_values(array_unique($matches[1]));
    }

    /**
     * Resolve all {{>slug}} includes recursively, then render variables.
     *
     * @return array{rendered: string, variables_used: string[], variables_missing: string[], includes_resolved: string[]}
     */
    public function render(string $content, array $variables, ?array $metadata = null): array
    {
        $includesResolved = [];
        $resolvedContent = $this->resolveIncludes($content, [], $includesResolved);

        // Merge metadata from included prompts
        $mergedMetadata = $metadata ?? [];
        foreach ($includesResolved as $slug) {
            $included = Prompt::where('slug', $slug)->first();
            $activeVersion = $included?->active_version;
            if ($activeVersion?->variable_metadata) {
                $mergedMetadata = array_merge($activeVersion->variable_metadata, $mergedMetadata);
            }
        }
        if (empty($mergedMetadata)) {
            $mergedMetadata = null;
        }

        $missing = [];
        $used = [];

        $rendered = preg_replace_callback(self::PATTERN, function ($matches) use ($variables, $mergedMetadata, &$missing, &$used) {
            $name = $matches[1];
            if (array_key_exists($name, $variables)) {
                $used[] = $name;
                return $variables[$name];
            }
            if ($mergedMetadata && isset($mergedMetadata[$name]['default']) && $mergedMetadata[$name]['default'] !== null) {
                $used[] = $name;
                return $mergedMetadata[$name]['default'];
            }
            $missing[] = $name;
            return $matches[0];
        }, $resolvedContent);

        return [
            'rendered'           => $rendered,
            'variables_used'     => array_values(array_unique($used)),
            'variables_missing'  => array_values(array_unique($missing)),
            'includes_resolved'  => array_values(array_unique($includesResolved)),
        ];
    }

    private function resolveIncludes(string $content, array $chain, array &$resolved): string
    {
        $maxDepth = config('urge.max_include_depth', 10);

        return preg_replace_callback(self::INCLUDE_PATTERN, function ($matches) use ($chain, &$resolved, $maxDepth) {
            $slug = $matches[1];

            if (in_array($slug, $chain, true)) {
                $path = implode(' → ', [...$chain, $slug]);
                throw new \RuntimeException("Circular include detected: {$path}");
            }

            if (count($chain) >= $maxDepth) {
                throw new \RuntimeException("Max include depth ({$maxDepth}) exceeded.");
            }

            $prompt = Prompt::where('slug', $slug)->first();
            $version = $prompt?->active_version;
            if (!$prompt || !$version) {
                return $matches[0];
            }

            $resolved[] = $slug;

            return $this->resolveIncludes($version->content, [...$chain, $slug], $resolved);
        }, $content);
    }
}
