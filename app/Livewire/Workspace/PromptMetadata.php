<?php

namespace App\Livewire\Workspace;

use App\Models\Category;
use App\Models\Prompt;
use Livewire\Component;

class PromptMetadata extends Component
{
    public Prompt $prompt;
    public string $name;
    public string $description;
    public string $type;
    public ?int $categoryId;
    public string $tagsInput;

    public function mount(Prompt $prompt)
    {
        $this->prompt = $prompt;
        $this->name = $prompt->name;
        $this->description = $prompt->description ?? '';
        $this->type = $prompt->type;
        $this->categoryId = $prompt->category_id;
        $this->tagsInput = $prompt->tags ? implode(', ', $prompt->tags) : '';
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'in:prompt,fragment',
            'categoryId' => 'nullable|exists:categories,id',
        ]);

        $tags = $this->tagsInput
            ? array_map('trim', explode(',', $this->tagsInput))
            : null;

        $this->prompt->update([
            'name' => $this->name,
            'description' => $this->description ?: null,
            'type' => $this->type,
            'category_id' => $this->categoryId,
            'tags' => $tags,
        ]);

        session()->flash('metadata-saved', 'Saved.');
    }

    public function render()
    {
        return view('livewire.workspace.prompt-metadata', [
            'categories' => Category::orderBy('name')->get(),
        ]);
    }
}
