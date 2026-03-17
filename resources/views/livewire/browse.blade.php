<div class="p-6 max-w-7xl mx-auto">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-4">Browse</h1>

    {{-- Tabs --}}
    <div class="flex items-center gap-4 mb-4 flex-wrap">
        <button wire:click="$set('tab', 'prompts')"
                class="px-3 py-1.5 text-sm font-medium rounded-md {{ $tab === 'prompts' ? 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-300' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300' }}">
            Prompts
        </button>
        <button wire:click="$set('tab', 'fragments')"
                class="px-3 py-1.5 text-sm font-medium rounded-md {{ $tab === 'fragments' ? 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-300' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300' }}">
            Fragments
        </button>
        <button wire:click="$set('tab', 'collections')"
                class="px-3 py-1.5 text-sm font-medium rounded-md {{ $tab === 'collections' ? 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-300' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300' }}">
            Collections
        </button>
        <button wire:click="$set('tab', 'starred')"
                class="px-3 py-1.5 text-sm font-medium rounded-md {{ $tab === 'starred' ? 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-300' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300' }}">
            Starred
        </button>

        <div class="ml-auto flex items-center gap-2">
            {{-- Category filter --}}
            @if($tab === 'prompts' || $tab === 'fragments')
            <select wire:model.live="categoryFilter"
                    class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 py-1.5">
                <option value="">All categories</option>
                @foreach($categories as $cat)
                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                @endforeach
            </select>
            @endif

            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search..."
                   class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500 py-1.5 w-64">
        </div>
    </div>

    {{-- Tag filter chips --}}
    @if(($tab === 'prompts' || $tab === 'fragments') && $allTags->count() > 0)
    <div class="flex flex-wrap gap-1.5 mb-4">
        @if($tagFilter)
        <button wire:click="$set('tagFilter', '')"
                class="text-xs px-2 py-0.5 rounded-full bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-300 dark:hover:bg-gray-600">
            Clear filter &times;
        </button>
        @endif
        @foreach($allTags as $tag)
        <button wire:click="$set('tagFilter', '{{ $tag }}')"
                class="text-xs px-2 py-0.5 rounded-full transition
                    {{ $tagFilter === $tag
                        ? 'bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 ring-1 ring-indigo-300 dark:ring-indigo-700'
                        : 'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600' }}">
            {{ $tag }}
        </button>
        @endforeach
    </div>
    @endif

    {{-- Content by tab --}}
    @if($tab === 'collections')
        <livewire:browse.collection-list />
    @elseif($tab === 'starred')
        {{-- Starred results --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse($starredResults as $result)
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex items-center gap-2 mb-1">
                    <span class="text-amber-500 text-sm">&#9733;</span>
                    <span class="font-medium text-gray-900 dark:text-gray-100 text-sm">{{ $result->provider_name ?: 'Manual' }}</span>
                    @if($result->model_name)
                    <span class="text-xs text-gray-400 dark:text-gray-500">{{ $result->model_name }}</span>
                    @endif
                </div>
                @if($result->prompt)
                <a href="{{ route('workspace', $result->prompt) }}" wire:navigate
                   class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline">
                    {{ $result->prompt->name }} v{{ $result->promptVersion->version_number ?? '?' }}
                </a>
                @endif
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1 line-clamp-3">{{ $result->response_text }}</p>
                @if($result->rating)
                <div class="flex items-center gap-0.5 mt-2">
                    @for($i = 1; $i <= 5; $i++)
                    <span class="text-xs {{ $result->rating >= $i ? 'text-amber-500' : 'text-gray-300 dark:text-gray-600' }}">&#9733;</span>
                    @endfor
                </div>
                @endif
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ $result->created_at->diffForHumans() }}</p>
            </div>
            @empty
            <p class="text-gray-500 dark:text-gray-400 text-sm col-span-full">No starred results yet.</p>
            @endforelse
        </div>
    @else
        {{-- Prompts/Fragments grid --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse($prompts as $prompt)
            <a href="{{ route('workspace', $prompt) }}" wire:navigate
               class="block bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 hover:border-indigo-300 dark:hover:border-indigo-600 hover:shadow-sm transition">
                <div class="flex items-center gap-2 mb-1">
                    <h3 class="font-medium text-gray-900 dark:text-gray-100 truncate">{{ $prompt->name }}</h3>
                    @if($prompt->category)
                        <span class="text-xs px-1.5 py-0.5 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 rounded">{{ $prompt->category->name }}</span>
                    @endif
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    {{ $prompt->latestVersion ? 'v' . $prompt->latestVersion->version_number : 'No versions' }}
                    &middot; {{ $prompt->updated_at->diffForHumans() }}
                    @if($prompt->results_count > 0)
                    &middot; <span class="text-indigo-600 dark:text-indigo-400">{{ $prompt->results_count }} results</span>
                    @endif
                </p>
                @if($prompt->description)
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1 line-clamp-2">{{ $prompt->description }}</p>
                @endif
                @if($prompt->tags)
                    <div class="flex flex-wrap gap-1 mt-2">
                        @foreach($prompt->tags as $tag)
                            <span class="text-xs px-1.5 py-0.5 bg-gray-50 dark:bg-gray-700 text-gray-500 dark:text-gray-400 rounded">{{ $tag }}</span>
                        @endforeach
                    </div>
                @endif
            </a>
            @empty
            <p class="text-gray-500 dark:text-gray-400 text-sm col-span-full">No {{ $tab }} found.</p>
            @endforelse
        </div>
    @endif
</div>
