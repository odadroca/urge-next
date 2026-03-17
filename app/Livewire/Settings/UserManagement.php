<?php

namespace App\Livewire\Settings;

use App\Models\User;
use Livewire\Component;

class UserManagement extends Component
{
    public ?int $deleteConfirmId = null;

    public function mount(): void
    {
        if (!auth()->user()->isAdmin()) {
            abort(403);
        }
    }

    public function changeRole(int $userId, string $role): void
    {
        if (!auth()->user()->isAdmin()) {
            abort(403);
        }

        if (!in_array($role, ['admin', 'editor', 'viewer'])) {
            return;
        }

        $user = User::findOrFail($userId);

        // Cannot change own role
        if ($user->id === auth()->id()) {
            return;
        }

        $user->update(['role' => $role]);
    }

    public function confirmDelete(int $id): void
    {
        $this->deleteConfirmId = $id;
    }

    public function deleteUser(): void
    {
        if (!auth()->user()->isAdmin()) {
            abort(403);
        }

        if ($this->deleteConfirmId) {
            $user = User::findOrFail($this->deleteConfirmId);

            // Cannot delete self
            if ($user->id === auth()->id()) {
                $this->deleteConfirmId = null;
                return;
            }

            $user->delete();
            $this->deleteConfirmId = null;
        }
    }

    public function cancelDelete(): void
    {
        $this->deleteConfirmId = null;
    }

    public function render()
    {
        return view('livewire.settings.user-management', [
            'users' => User::orderBy('name')->get(),
        ]);
    }
}
