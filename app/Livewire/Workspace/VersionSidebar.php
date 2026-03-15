<?php

namespace App\Livewire\Workspace;

use App\Models\Prompt;
use App\Models\PromptVersion;
use App\Services\VersioningService;
use Livewire\Attributes\On;
use Livewire\Component;

class VersionSidebar extends Component
{
    public Prompt $prompt;
    public ?int $currentVersionId = null;
    public bool $showCreateForm = false;
    public string $commitMessage = '';

    public function mount(Prompt $prompt, ?PromptVersion $currentVersion = null)
    {
        $this->prompt = $prompt;
        $this->currentVersionId = $currentVersion?->id;
    }

    public function selectVersion(int $versionId)
    {
        $this->currentVersionId = $versionId;
        $this->dispatch('version-selected', versionId: $versionId);
    }

    #[On('version-created')]
    public function onVersionCreated(int $versionId)
    {
        $this->prompt->refresh();
        $this->prompt->load('versions');
        $this->currentVersionId = $versionId;
        $this->showCreateForm = false;
        $this->commitMessage = '';
    }

    public function render()
    {
        return view('livewire.workspace.version-sidebar', [
            'versions' => $this->prompt->versions()->get(),
        ]);
    }
}
