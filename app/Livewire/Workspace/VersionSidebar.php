<?php

namespace App\Livewire\Workspace;

use App\Models\Collection;
use App\Models\CollectionItem;
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

    public function addVersionToCollection(int $versionId, int $collectionId)
    {
        CollectionItem::firstOrCreate([
            'collection_id' => $collectionId,
            'item_type' => 'prompt_version',
            'item_id' => $versionId,
        ], [
            'sort_order' => CollectionItem::where('collection_id', $collectionId)->max('sort_order') + 1,
        ]);
    }

    public function render()
    {
        return view('livewire.workspace.version-sidebar', [
            'versions' => $this->prompt->versions()->get(),
            'collections' => Collection::orderBy('title')->get(['id', 'title']),
        ]);
    }
}
