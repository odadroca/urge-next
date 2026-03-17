<?php

namespace App\Livewire\Workspace;

use App\Models\Collection;
use App\Models\CollectionItem;
use App\Models\Prompt;
use App\Models\PromptVersion;
use App\Models\Result;
use App\Services\ImportExportService;
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

    public function exportResult(int $resultId)
    {
        $result = Result::with(['prompt', 'promptVersion'])->findOrFail($resultId);
        $service = app(ImportExportService::class);
        $content = $service->exportResult($result);
        $filename = ($result->prompt->slug ?? 'result') . '-v' . ($result->promptVersion->version_number ?? 0) . '-' . $result->id . '.md';

        return response()->streamDownload(function () use ($content) {
            echo $content;
        }, $filename);
    }

    public function exportAllResults()
    {
        $query = Result::where('prompt_id', $this->prompt->id)
            ->with(['prompt', 'promptVersion'])
            ->orderByDesc('created_at');

        if (!$this->showAllVersions && $this->currentVersionId) {
            $query->where('prompt_version_id', $this->currentVersionId);
        }

        $results = $query->get();
        if ($results->isEmpty()) {
            return;
        }

        $service = app(ImportExportService::class);
        $filename = $this->prompt->slug . '-results.zip';

        return response()->streamDownload(function () use ($results, $service) {
            $tmpFile = tempnam(sys_get_temp_dir(), 'urge_export_');
            $zip = new \ZipArchive();
            $zip->open($tmpFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

            foreach ($results as $i => $result) {
                $name = ($result->provider_name ?: 'result') . '-' . $result->id . '.md';
                $zip->addFromString($name, $service->exportResult($result));
            }

            $zip->close();
            readfile($tmpFile);
            unlink($tmpFile);
        }, $filename);
    }

    public function addResultToCollection(int $resultId, int $collectionId)
    {
        CollectionItem::firstOrCreate([
            'collection_id' => $collectionId,
            'item_type' => 'result',
            'item_id' => $resultId,
        ], [
            'sort_order' => CollectionItem::where('collection_id', $collectionId)->max('sort_order') + 1,
        ]);
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
            'collections' => Collection::orderBy('title')->get(['id', 'title']),
        ]);
    }
}
