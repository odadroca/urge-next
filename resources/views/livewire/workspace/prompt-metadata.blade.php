<div>
    <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Metadata</h3>

    <form wire:submit="save" class="space-y-2">
        <div>
            <input wire:model="name" type="text" placeholder="Prompt name"
                   class="w-full rounded-md border-gray-300 shadow-sm text-xs focus:border-indigo-500 focus:ring-indigo-500 py-1.5">
        </div>

        <div>
            <select wire:model="type"
                    class="w-full rounded-md border-gray-300 shadow-sm text-xs focus:border-indigo-500 focus:ring-indigo-500 py-1.5">
                <option value="prompt">Prompt</option>
                <option value="fragment">Fragment</option>
            </select>
        </div>

        <div>
            <select wire:model="categoryId"
                    class="w-full rounded-md border-gray-300 shadow-sm text-xs focus:border-indigo-500 focus:ring-indigo-500 py-1.5">
                <option value="">No category</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <input wire:model="tagsInput" type="text" placeholder="Tags (comma-separated)"
                   class="w-full rounded-md border-gray-300 shadow-sm text-xs focus:border-indigo-500 focus:ring-indigo-500 py-1.5">
        </div>

        <div>
            <textarea wire:model="description" rows="2" placeholder="Description (optional)"
                      class="w-full rounded-md border-gray-300 shadow-sm text-xs focus:border-indigo-500 focus:ring-indigo-500"></textarea>
        </div>

        <button type="submit" class="w-full px-2 py-1.5 bg-gray-100 text-gray-700 text-xs font-medium rounded-md hover:bg-gray-200 transition">
            Update Metadata
        </button>

        @if(session('metadata-saved'))
            <p class="text-xs text-green-600">{{ session('metadata-saved') }}</p>
        @endif
    </form>

    <div class="mt-3 pt-2 border-t border-gray-100">
        <p class="text-xs text-gray-400">Slug: <span class="font-mono">{{ $prompt->slug }}</span></p>
        <p class="text-xs text-gray-400">Created: {{ $prompt->created_at->format('M j, Y') }}</p>
    </div>
</div>
