<?php

namespace App\Livewire;

use App\Models\Prompt;
use App\Models\Result;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Dashboard')]
class Dashboard extends Component
{
    public string $newPromptName = '';
    public string $newPromptType = 'prompt';
    public bool $showCreateForm = false;

    public function createPrompt()
    {
        $this->validate([
            'newPromptName' => 'required|string|max:255',
            'newPromptType' => 'in:prompt,fragment',
        ]);

        $prompt = Prompt::create([
            'name' => $this->newPromptName,
            'type' => $this->newPromptType,
            'created_by' => auth()->id(),
        ]);

        return $this->redirect(route('workspace', $prompt), navigate: true);
    }

    public function render()
    {
        return view('livewire.dashboard', [
            'recentPrompts' => Prompt::with('latestVersion')
                ->orderByDesc('updated_at')
                ->limit(12)
                ->get(),
            'starredResults' => Result::where('starred', true)
                ->with(['prompt', 'promptVersion'])
                ->orderByDesc('created_at')
                ->limit(6)
                ->get(),
            'recentResults' => Result::with(['prompt', 'promptVersion'])
                ->orderByDesc('created_at')
                ->limit(6)
                ->get(),
        ]);
    }
}
