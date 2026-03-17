<div>
    {{-- Create button --}}
    <div class="mb-4">
        @if($showCreateForm)
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 space-y-3">
            <input wire:model="newTitle" type="text" placeholder="Collection title"
                   class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
            <textarea wire:model="newDescription" placeholder="Description (optional)" rows="2"
                      class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
            <div class="flex items-center gap-2">
                <button wire:click="createCollection"
                        class="px-3 py-1.5 text-sm bg-indigo-600 dark:bg-indigo-500 text-white rounded-md hover:bg-indigo-700 dark:hover:bg-indigo-600 font-medium">
                    Create
                </button>
                <button wire:click="$set('showCreateForm', false)"
                        class="px-3 py-1.5 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200">
                    Cancel
                </button>
            </div>
        </div>
        @else
        <button wire:click="$set('showCreateForm', true)"
                class="px-3 py-1.5 text-sm bg-indigo-600 dark:bg-indigo-500 text-white rounded-md hover:bg-indigo-700 dark:hover:bg-indigo-600 font-medium">
            + New Collection
        </button>
        @endif
    </div>

    {{-- Collections grid --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse($collections as $collection)
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden {{ $expandedId === $collection->id ? 'ring-2 ring-indigo-300 dark:ring-indigo-600' : '' }}">
            @if($editingId === $collection->id)
            {{-- Edit form --}}
            <div class="p-4 space-y-2">
                <input wire:model="editTitle" type="text"
                       class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                <textarea wire:model="editDescription" rows="2"
                          class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                <div class="flex gap-2">
                    <button wire:click="saveEdit" class="text-xs text-indigo-600 dark:text-indigo-400 font-medium">Save</button>
                    <button wire:click="$set('editingId', null)" class="text-xs text-gray-400 dark:text-gray-500">Cancel</button>
                </div>
            </div>
            @else
            {{-- Display --}}
            <div class="p-4 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50 transition" wire:click="toggleExpand({{ $collection->id }})">
                <div class="flex items-center justify-between">
                    <h3 class="font-medium text-gray-900 dark:text-gray-100 text-sm">{{ $collection->title }}</h3>
                    <span class="text-xs text-gray-400 dark:text-gray-500">{{ $collection->items_count }} items</span>
                </div>
                @if($collection->description)
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 line-clamp-2">{{ $collection->description }}</p>
                @endif
            </div>
            <div class="px-4 pb-3 flex items-center gap-2">
                <button wire:click="startEditing({{ $collection->id }})"
                        class="text-xs text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300">Edit</button>
                <button wire:click="deleteCollection({{ $collection->id }})" wire:confirm="Delete this collection?"
                        class="text-xs text-red-400 hover:text-red-600 dark:hover:text-red-300">Delete</button>
            </div>
            @endif
        </div>
        @empty
        <div class="col-span-full text-center py-8">
            <p class="text-sm text-gray-400 dark:text-gray-500 italic">No collections yet. Create one to organize your prompts and results.</p>
        </div>
        @endforelse
    </div>

    {{-- Expanded collection items --}}
    @if($expandedCollection)
    <div class="mt-6 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-gray-900 dark:text-gray-100">{{ $expandedCollection->title }}</h3>
            <button wire:click="$set('expandedId', null)" class="text-xs text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300">Close</button>
        </div>

        @if($expandedCollection->items->isEmpty())
        <p class="text-sm text-gray-400 dark:text-gray-500 italic py-4 text-center">
            No items yet. Add items from the workspace (version sidebar or results panel).
        </p>
        @else
        <div class="space-y-2" x-data wire:sortable="reorderItems">
            @foreach($expandedCollection->items as $item)
            <div wire:key="item-{{ $item->id }}" wire:sortable.item="{{ $item->id }}"
                 class="flex items-center gap-3 px-3 py-2 bg-gray-50 dark:bg-gray-900 rounded-md border border-gray-200 dark:border-gray-700 group">
                <span wire:sortable.handle class="cursor-grab text-gray-300 dark:text-gray-600 hover:text-gray-500 dark:hover:text-gray-400 text-sm select-none">&#9776;</span>

                <span class="text-xs px-1.5 py-0.5 rounded font-medium {{ $item->item_type === 'prompt_version' ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400' : 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400' }}">
                    {{ $item->item_type === 'prompt_version' ? 'Prompt' : 'Result' }}
                </span>

                <span class="flex-1 text-sm text-gray-700 dark:text-gray-300 truncate">
                    @php $resolved = $item->item; @endphp
                    @if($item->item_type === 'prompt_version' && $resolved)
                        {{ $resolved->prompt->name ?? 'Unknown' }} v{{ $resolved->version_number }}
                    @elseif($item->item_type === 'result' && $resolved)
                        {{ $resolved->provider_name ?: 'Manual' }} {{ $resolved->model_name ? "({$resolved->model_name})" : '' }}
                    @else
                        <span class="italic text-gray-400 dark:text-gray-500">Deleted item</span>
                    @endif
                </span>

                @if($item->notes)
                <span class="text-xs text-gray-400 dark:text-gray-500 italic truncate max-w-[120px]">{{ $item->notes }}</span>
                @endif

                <button wire:click="removeItem({{ $item->id }})"
                        class="text-xs text-red-400 hover:text-red-600 dark:hover:text-red-300 opacity-0 group-hover:opacity-100 transition">
                    Remove
                </button>
            </div>
            @endforeach
        </div>
        @endif
    </div>
    @endif
</div>
