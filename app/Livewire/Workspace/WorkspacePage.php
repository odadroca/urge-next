<?php

namespace App\Livewire\Workspace;

use App\Models\Prompt;
use App\Models\PromptVersion;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('layouts.app')]
class WorkspacePage extends Component
{
    public Prompt $prompt;
    public ?PromptVersion $currentVersion = null;

    public function mount(Prompt $prompt)
    {
        $this->prompt = $prompt->load(['versions', 'category']);
        $this->currentVersion = $prompt->active_version;
    }

    #[On('version-selected')]
    public function selectVersion(int $versionId)
    {
        $this->currentVersion = PromptVersion::findOrFail($versionId);
    }

    #[On('version-created')]
    public function onVersionCreated(int $versionId)
    {
        $this->prompt->refresh();
        $this->prompt->load('versions');
        $this->currentVersion = PromptVersion::findOrFail($versionId);
    }

    #[On('result-saved')]
    public function onResultSaved()
    {
        // ResultsPanel listens to this event too and refreshes
    }

    public function getTitle(): string
    {
        return $this->prompt->name;
    }

    public function render()
    {
        return view('livewire.workspace.workspace-page')
            ->title($this->prompt->name);
    }
}
