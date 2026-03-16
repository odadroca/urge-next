<div class="p-6 max-w-7xl mx-auto">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-4">Browse</h1>

    {{-- Tabs --}}
    <div class="flex items-center gap-4 mb-4">
        <button wire:click="$set('tab', 'prompts')"
                class="px-3 py-1.5 text-sm font-medium rounded-md {{ $tab === 'prompts' ? 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-300' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300' }}">
            Prompts
        </button>
        <button wire:click="$set('tab', 'fragments')"
                class="px-3 py-1.5 text-sm font-medium rounded-md {{ $tab === 'fragments' ? 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-300' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300' }}">
            Fragments
        </button>

        <div class="ml-auto">
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search..."
                   class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500 py-1.5 w-64">
        </div>
    </div>

    {{-- Grid --}}
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
</div>
