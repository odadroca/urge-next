<?php

namespace App\Livewire\Workspace;

use App\Models\Prompt;
use App\Models\PromptVersion;
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
        $this->detectTokens();
    }

    public function updatedContent()
    {
        $this->isDirty = true;
        $this->detectTokens();
    }

    public function saveVersion(VersioningService $versioningService)
    {
        $this->validate([
            'content' => 'required|string',
        ]);

        $version = $versioningService->createVersion($this->prompt, [
            'content' => $this->content,
            'commit_message' => $this->commitMessage ?: null,
        ], auth()->user());

        $this->currentVersionId = $version->id;
        $this->isDirty = false;
        $this->commitMessage = '';

        $this->dispatch('version-created', versionId: $version->id);
    }

    private function detectTokens(): void
    {
        $this->detectedVariables = $this->templateEngine->extractVariables($this->content);
        $this->detectedIncludes = $this->templateEngine->extractIncludes($this->content);
    }

    public function render()
    {
        return view('livewire.workspace.editor');
    }
}
