<?php

namespace App\Livewire;

use App\Models\Prompt;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Browse')]
class Browse extends Component
{
    public string $search = '';
    public string $tab = 'prompts';

    public function render()
    {
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

        return view('livewire.browse', [
            'prompts' => $query->with('latestVersion', 'category')->get(),
        ]);
    }
}
