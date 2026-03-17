<?php

namespace App\Livewire\Workspace;

use App\Models\LlmProvider;
use App\Models\Prompt;
use App\Models\PromptVersion;
use App\Services\AiAssistantService;
use App\Services\ImportExportService;
use App\Services\TemplateEngine;
use App\Services\VersioningService;
use Livewire\Attributes\On;
use Livewire\Component;

class Editor extends Component
{
    public Prompt $prompt;
    public ?int $currentVersionId = null;
    public string $content = '';
    public string $commitMessage = '';
    public array $detectedVariables = [];
    public array $detectedIncludes = [];
    public bool $isDirty = false;
    public string $editorMode = 'text'; // 'text' or 'visual'
    public array $variableMetadata = [];
    public ?string $aiSuggestions = null;
    public bool $showPreview = false;
    public array $previewVariables = [];
    public ?array $previewResult = null;
    public ?string $previewError = null;

    protected TemplateEngine $templateEngine;

    public function boot(TemplateEngine $templateEngine)
    {
        $this->templateEngine = $templateEngine;
    }

    public function mount(Prompt $prompt, ?PromptVersion $currentVersion = null)
    {
        $this->prompt = $prompt;
        if ($currentVersion) {
            $this->currentVersionId = $currentVersion->id;
            $this->content = $currentVersion->content;
            $this->variableMetadata = $currentVersion->variable_metadata ?? [];
            $this->detectTokens();
        }
    }

    #[On('version-selected')]
    public function loadVersion(int $versionId)
    {
        $version = PromptVersion::findOrFail($versionId);
        $this->currentVersionId = $versionId;
        $this->content = $version->content;
        $this->isDirty = false;
        $this->commitMessage = '';
        $this->variableMetadata = $version->variable_metadata ?? [];
        $this->previewVariables = [];
        $this->detectTokens();
        if ($this->showPreview) {
            $this->renderPreview();
        }
    }

    public function updatedContent()
    {
        $this->isDirty = true;
        $this->detectTokens();
        if ($this->showPreview) {
            $this->renderPreview();
        }
    }

    public function switchMode(string $mode)
    {
        $this->editorMode = $mode;
    }

    public function setMetaField(string $varName, string $field, $value)
    {
        if (!isset($this->variableMetadata[$varName])) {
            $this->variableMetadata[$varName] = ['type' => 'string', 'default' => '', 'description' => ''];
        }
        $this->variableMetadata[$varName][$field] = $value;
        $this->isDirty = true;
        if ($this->showPreview) {
            $this->renderPreview();
        }
    }

    public function saveVersion(VersioningService $versioningService)
    {
        $this->validate([
            'content' => 'required|string',
        ]);

        // Process options_csv for enum variables
        $metadata = $this->variableMetadata;
        if (!empty($metadata)) {
            foreach ($metadata as $varName => &$meta) {
                if (!empty($meta['options_csv'])) {
                    $meta['options'] = array_values(array_filter(
                        array_map('trim', explode(',', $meta['options_csv']))
                    ));
                }
                unset($meta['options_csv']);
            }
            unset($meta);
        }

        $version = $versioningService->createVersion($this->prompt, [
            'content' => $this->content,
            'commit_message' => $this->commitMessage ?: null,
            'variable_metadata' => !empty($metadata) ? $metadata : null,
        ], auth()->user());

        $this->currentVersionId = $version->id;
        $this->isDirty = false;
        $this->commitMessage = '';
        $this->variableMetadata = $version->variable_metadata ?? [];

        $this->dispatch('version-created', versionId: $version->id);
        $this->dispatch('notify', message: "Version {$version->version_number} saved", type: 'success');
    }

    public function suggestImprovements(int $providerId, AiAssistantService $aiService): void
    {
        if (empty($this->content)) {
            return;
        }

        $provider = LlmProvider::findOrFail($providerId);
        $result = $aiService->suggestImprovements($this->content, $provider);

        $this->aiSuggestions = $result->success ? $result->text : "Error: {$result->error}";
    }

    public function exportPrompt()
    {
        if (!$this->currentVersionId) {
            return;
        }

        $version = PromptVersion::with('prompt')->findOrFail($this->currentVersionId);
        $service = app(ImportExportService::class);
        $content = $service->exportPromptVersion($version);
        $filename = $version->prompt->slug . '-v' . $version->version_number . '.md';

        return response()->streamDownload(function () use ($content) {
            echo $content;
        }, $filename);
    }

    public function togglePreview()
    {
        $this->showPreview = !$this->showPreview;
        if ($this->showPreview) {
            $this->renderPreview();
        } else {
            $this->previewResult = null;
            $this->previewError = null;
        }
    }

    public function renderPreview()
    {
        if (empty($this->content)) {
            $this->previewResult = null;
            $this->previewError = null;
            return;
        }

        $variables = [];
        foreach ($this->variableMetadata as $varName => $meta) {
            if (!empty($meta['default'])) {
                $variables[$varName] = $meta['default'];
            }
        }
        foreach ($this->previewVariables as $varName => $value) {
            if ($value !== '' && $value !== null) {
                $variables[$varName] = $value;
            }
        }

        try {
            $this->previewResult = $this->templateEngine->render(
                $this->content, $variables, $this->variableMetadata
            );
            $this->previewError = null;
        } catch (\RuntimeException $e) {
            $this->previewError = $e->getMessage();
            $this->previewResult = null;
        }
    }

    public function updatedPreviewVariables()
    {
        if ($this->showPreview) {
            $this->renderPreview();
        }
    }

    public function getRenderedContent(): string
    {
        if (empty($this->content)) {
            return '';
        }

        $defaults = [];
        foreach ($this->variableMetadata as $varName => $meta) {
            if (!empty($meta['default'])) {
                $defaults[$varName] = $meta['default'];
            }
        }

        $result = $this->templateEngine->render($this->content, $defaults);
        return $result['rendered'];
    }

    private function detectTokens(): void
    {
        $this->detectedVariables = $this->templateEngine->extractVariables($this->content);
        $this->detectedIncludes = $this->templateEngine->extractIncludes($this->content);

        // Clean up metadata for variables that no longer exist
        $validVars = array_flip($this->detectedVariables);
        $this->variableMetadata = array_intersect_key($this->variableMetadata, $validVars);
    }

    public function render()
    {
        return view('livewire.workspace.editor');
    }
}
