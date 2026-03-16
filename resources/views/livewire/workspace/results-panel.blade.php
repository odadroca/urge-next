<div class="p-3">
    <div class="flex items-center justify-between mb-3">
        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Results</h3>
        <label class="flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
            <input type="checkbox" wire:model.live="showAllVersions" class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500 dark:bg-gray-700">
            All versions
        </label>
    </div>

    <div class="space-y-3">
        @forelse($results as $result)
        <div class="bg-gray-50 dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700 p-3" wire:key="result-{{ $result->id }}">
            {{-- Header --}}
            <div class="flex items-center justify-between mb-1.5">
                <div class="flex items-center gap-1.5">
                    <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                        {{ $result->provider_name ?: 'Manual' }}
                    </span>
                    @if($result->model_name)
                        <span class="text-xs text-gray-400 dark:text-gray-500">{{ $result->model_name }}</span>
                    @endif
                </div>
                <div class="flex items-center gap-1">
                    <button wire:click="toggleStar({{ $result->id }})"
                            class="text-sm {{ $result->starred ? 'text-amber-500' : 'text-gray-300 dark:text-gray-600 hover:text-amber-400' }} transition">
                        &#9733;
                    </button>
                </div>
            </div>

            {{-- Version badge --}}
            @if($showAllVersions)
                <span class="text-xs text-indigo-600 dark:text-indigo-400 mb-1 inline-block">v{{ $result->promptVersion->version_number }}</span>
            @endif

            {{-- Response preview --}}
            <div x-data="{ expanded: false }">
                <div class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap" :class="expanded ? '' : 'line-clamp-4'">{{ $result->response_text }}</div>
                @if(strlen($result->response_text ?? '') > 200)
                    <button @click="expanded = !expanded" class="text-xs text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 mt-1"
                            x-text="expanded ? 'Show less' : 'Show more'"></button>
                @endif
            </div>

            {{-- Rating --}}
            <div class="flex items-center gap-1 mt-2">
                @for($i = 1; $i <= 5; $i++)
                <button wire:click="updateRating({{ $result->id }}, {{ $i }})"
                        class="text-sm {{ ($result->rating ?? 0) >= $i ? 'text-amber-500' : 'text-gray-300 dark:text-gray-600 hover:text-amber-400' }} transition">
                    &#9733;
                </button>
                @endfor
                <span class="text-xs text-gray-400 dark:text-gray-500 ml-2">{{ $result->created_at->diffForHumans() }}</span>
            </div>

            {{-- Notes --}}
            @if($result->notes)
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1.5 italic">{{ $result->notes }}</p>
            @endif

            {{-- Copy + Delete --}}
            <div class="flex items-center gap-2 mt-2">
                <button x-data @click="navigator.clipboard.writeText(@js($result->response_text))"
                        class="text-xs text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300">Copy</button>
                <button wire:click="deleteResult({{ $result->id }})" wire:confirm="Delete this result?"
                        class="text-xs text-red-400 hover:text-red-600 dark:hover:text-red-300">Delete</button>
            </div>
        </div>
        @empty
        <p class="text-xs text-gray-400 dark:text-gray-500 italic text-center py-4">
            No results yet. Paste one below or run with an LLM.
        </p>
        @endforelse
    </div>
</div>
