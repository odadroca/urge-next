<?php

namespace App\Livewire\Settings;

use App\Models\Category;
use Livewire\Component;

class Categories extends Component
{
    public bool $showForm = false;
    public ?int $editingId = null;
    public string $name = '';
    public string $color = 'gray';
    public ?int $deleteConfirmId = null;

    public function create(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'color' => 'required|string|max:30',
        ]);

        Category::create([
            'name' => $this->name,
            'color' => $this->color,
        ]);

        $this->resetForm();
        $this->dispatch('notify', message: 'Category created', type: 'success');
    }

    public function edit(int $id): void
    {
        $category = Category::findOrFail($id);
        $this->editingId = $id;
        $this->name = $category->name;
        $this->color = $category->color;
        $this->showForm = true;
    }

    public function update(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'color' => 'required|string|max:30',
        ]);

        $category = Category::findOrFail($this->editingId);
        $category->update([
            'name' => $this->name,
            'color' => $this->color,
        ]);

        $this->resetForm();
        $this->dispatch('notify', message: 'Category updated', type: 'success');
    }

    public function confirmDelete(int $id): void
    {
        $this->deleteConfirmId = $id;
    }

    public function delete(): void
    {
        if ($this->deleteConfirmId) {
            $category = Category::findOrFail($this->deleteConfirmId);
            // Unlink prompts before deleting
            $category->prompts()->update(['category_id' => null]);
            $category->delete();
            $this->deleteConfirmId = null;
            $this->dispatch('notify', message: 'Category deleted', type: 'success');
        }
    }

    public function cancelDelete(): void
    {
        $this->deleteConfirmId = null;
    }

    private function resetForm(): void
    {
        $this->reset(['showForm', 'editingId', 'name', 'color']);
        $this->color = 'gray';
    }

    public function render()
    {
        return view('livewire.settings.categories', [
            'categories' => Category::withCount('prompts')->orderBy('name')->get(),
            'availableColors' => ['gray', 'red', 'orange', 'amber', 'yellow', 'lime', 'green', 'emerald', 'teal', 'cyan', 'sky', 'blue', 'indigo', 'violet', 'purple', 'fuchsia', 'pink', 'rose'],
        ]);
    }
}
