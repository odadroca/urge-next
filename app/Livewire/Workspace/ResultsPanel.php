<?php

namespace App\Livewire\Workspace;

use App\Models\Prompt;
use App\Models\PromptVersion;
use App\Models\Result;
use Livewire\Attributes\On;
use Livewire\Component;

class ResultsPanel extends Component
{
    public Prompt $prompt;
    public ?int $currentVersionId = null;
    public bool $showAllVersions = false;

    public function mount(Prompt $prompt, ?PromptVersion $currentVersion = null)
    {
        $this->prompt = $prompt;
        $this->currentVersionId = $currentVersion?->id;
    }

    #[On('version-selected')]
    #[On('version-created')]
    public function onVersionChanged(int $versionId)
    {
        $this->currentVersionId = $versionId;
    }

    #[On('result-saved')]
    public function refreshResults()
    {
        // Just re-render
    }

    public function toggleStar(int $resultId)
    {
        $result = Result::findOrFail($resultId);
        $result->update(['starred' => !$result->starred]);
    }

    public function updateRating(int $resultId, int $rating)
    {
        $result = Result::findOrFail($resultId);
        $result->update(['rating' => $rating]);
    }

    public function deleteResult(int $resultId)
    {
        Result::findOrFail($resultId)->delete();
    }

    public function render()
    {
        $query = Result::where('prompt_id', $this->prompt->id)
            ->with('promptVersion')
            ->orderByDesc('created_at');

        if (!$this->showAllVersions && $this->currentVersionId) {
            $query->where('prompt_version_id', $this->currentVersionId);
        }

        return view('livewire.workspace.results-panel', [
            'results' => $query->get(),
        ]);
    }
}
