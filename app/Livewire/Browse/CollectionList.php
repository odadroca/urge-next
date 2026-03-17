<?php

namespace App\Livewire\Browse;

use App\Models\Collection;
use App\Models\CollectionItem;
use Livewire\Component;

class CollectionList extends Component
{
    public string $newTitle = '';
    public string $newDescription = '';
    public bool $showCreateForm = false;
    public ?int $expandedId = null;
    public ?int $editingId = null;
    public string $editTitle = '';
    public string $editDescription = '';

    public function createCollection()
    {
        $this->validate([
            'newTitle' => 'required|string|max:255',
            'newDescription' => 'nullable|string',
        ]);

        Collection::create([
            'title' => $this->newTitle,
            'description' => $this->newDescription ?: null,
            'created_by' => auth()->id(),
        ]);

        $this->reset(['newTitle', 'newDescription', 'showCreateForm']);
    }

    public function toggleExpand(int $id)
    {
        $this->expandedId = $this->expandedId === $id ? null : $id;
    }

    public function startEditing(int $id)
    {
        $collection = Collection::findOrFail($id);
        $this->editingId = $id;
        $this->editTitle = $collection->title;
        $this->editDescription = $collection->description ?? '';
    }

    public function saveEdit()
    {
        $this->validate([
            'editTitle' => 'required|string|max:255',
        ]);

        $collection = Collection::findOrFail($this->editingId);
        $collection->update([
            'title' => $this->editTitle,
            'description' => $this->editDescription ?: null,
        ]);

        $this->reset(['editingId', 'editTitle', 'editDescription']);
    }

    public function deleteCollection(int $id)
    {
        Collection::findOrFail($id)->delete();
        if ($this->expandedId === $id) {
            $this->expandedId = null;
        }
    }

    public function removeItem(int $itemId)
    {
        CollectionItem::findOrFail($itemId)->delete();
    }

    public function reorderItems(array $order)
    {
        foreach ($order as $index => $itemId) {
            CollectionItem::where('id', $itemId)->update(['sort_order' => $index]);
        }
    }

    public function render()
    {
        $collections = Collection::withCount('items')
            ->orderBy('title')
            ->get();

        $expandedCollection = null;
        if ($this->expandedId) {
            $expandedCollection = Collection::with(['items' => function ($q) {
                $q->orderBy('sort_order');
            }])->find($this->expandedId);
        }

        return view('livewire.browse.collection-list', [
            'collections' => $collections,
            'expandedCollection' => $expandedCollection,
        ]);
    }
}
