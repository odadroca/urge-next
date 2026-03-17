<div>
    {{-- Delete Confirmation --}}
    @if($deleteConfirmId)
        <div class="mb-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg flex items-center justify-between">
            <p class="text-sm text-red-700 dark:text-red-400">Delete this user? This cannot be undone.</p>
            <div class="flex gap-2">
                <button wire:click="cancelDelete" class="px-3 py-1 text-xs text-gray-600 dark:text-gray-400">Cancel</button>
                <button wire:click="deleteUser" class="px-3 py-1 text-xs bg-red-600 text-white rounded hover:bg-red-700 transition">Delete</button>
            </div>
        </div>
    @endif

    {{-- Users List --}}
    <div class="space-y-2">
        @foreach($users as $user)
            <div class="flex items-center justify-between p-3 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg">
                <div class="flex-1">
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $user->name }}</span>
                        @if($user->id === auth()->id())
                            <span class="px-1.5 py-0.5 text-xs rounded bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-400">You</span>
                        @endif
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $user->email }}</p>
                </div>
                <div class="flex items-center gap-3">
                    @if($user->id === auth()->id())
                        <span class="px-2 py-1 text-xs font-medium text-gray-600 dark:text-gray-400">{{ ucfirst($user->role) }}</span>
                    @else
                        <select wire:change="changeRole({{ $user->id }}, $event.target.value)"
                                class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 text-xs focus:border-indigo-500 focus:ring-indigo-500 py-1">
                            <option value="admin" {{ $user->role === 'admin' ? 'selected' : '' }}>Admin</option>
                            <option value="editor" {{ $user->role === 'editor' ? 'selected' : '' }}>Editor</option>
                            <option value="viewer" {{ $user->role === 'viewer' ? 'selected' : '' }}>Viewer</option>
                        </select>
                        <button wire:click="confirmDelete({{ $user->id }})"
                                class="px-2 py-1 text-xs text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300">
                            Delete
                        </button>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>
