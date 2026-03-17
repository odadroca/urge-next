<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\Prompt;
use App\Models\Result;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Browse')]
class Browse extends Component
{
    public string $search = '';
    public string $tab = 'prompts';
    public ?int $categoryFilter = null;
    public string $tagFilter = '';

    public function render()
    {
        $categories = Category::orderBy('name')->get();
        $prompts = collect();
        $starredResults = collect();

        if ($this->tab === 'prompts' || $this->tab === 'fragments') {
            $query = Prompt::query()->orderByDesc('updated_at');

            if ($this->tab === 'fragments') {
                $query->where('type', 'fragment');
            } else {
                $query->where('type', 'prompt');
            }

            if ($this->search) {
                $query->where(function ($q) {
                    $q->where('name', 'like', "%{$this->search}%")
                      ->orWhere('description', 'like', "%{$this->search}%");
                });
            }

            if ($this->categoryFilter) {
                $query->where('category_id', $this->categoryFilter);
            }

            if ($this->tagFilter) {
                $query->whereJsonContains('tags', $this->tagFilter);
            }

            $prompts = $query->with('latestVersion', 'category')
                ->withCount('results')
                ->get();
        } elseif ($this->tab === 'starred') {
            $starredResults = Result::where('starred', true)
                ->with(['prompt', 'promptVersion'])
                ->orderByDesc('created_at')
                ->limit(50)
                ->get();
        }

        // Gather all unique tags for filter chips
        $allTags = Prompt::whereNotNull('tags')
            ->pluck('tags')
            ->flatten()
            ->unique()
            ->sort()
            ->values();

        return view('livewire.browse', [
            'prompts' => $prompts,
            'categories' => $categories,
            'allTags' => $allTags,
            'starredResults' => $starredResults,
        ]);
    }
}
