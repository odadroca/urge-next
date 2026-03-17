<div class="p-3" x-data="{ ...diffViewer(), diffSelection: [] }">
    <div class="flex items-center justify-between mb-3">
        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Versions</h3>
        <span class="text-xs text-gray-400 dark:text-gray-500">{{ $versions->count() }}</span>
    </div>

    <div class="space-y-1">
        @forelse($versions as $version)
        <div class="flex items-center gap-1 group">
            {{-- Diff checkbox --}}
            @if($versions->count() > 1)
            <input type="checkbox"
                   value="{{ $version->id }}"
                   x-model="diffSelection"
                   :disabled="diffSelection.length >= 2 && !diffSelection.includes('{{ $version->id }}')"
                   class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500 w-3 h-3 disabled:opacity-30 cursor-pointer disabled:cursor-not-allowed shrink-0">
            @endif

            <button wire:click="selectVersion({{ $version->id }})"
                    class="flex-1 text-left px-2.5 py-1.5 rounded-md text-sm transition
                        {{ $currentVersionId === $version->id ? 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-300 font-medium' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                <div class="flex items-center justify-between">
                    <span>v{{ $version->version_number }}</span>
                    @if($prompt->pinned_version_id === $version->id)
                        <span class="text-xs text-indigo-500 dark:text-indigo-400" title="Pinned">&#x1f4cc;</span>
                    @elseif($loop->first && !$prompt->pinned_version_id)
                        <span class="w-2 h-2 rounded-full bg-green-400" title="Active (latest)"></span>
                    @endif
                </div>
                @if($version->commit_message)
                    <p class="text-xs text-gray-400 dark:text-gray-500 truncate mt-0.5">{{ $version->commit_message }}</p>
                @endif
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ $version->created_at->diffForHumans() }}</p>
            </button>

            {{-- Add to collection --}}
            @if($collections->count() > 0)
            <div class="relative opacity-0 group-hover:opacity-100 transition shrink-0" x-data="{ open: false }">
                <button @click.stop="open = !open" class="text-gray-300 dark:text-gray-600 hover:text-indigo-500 dark:hover:text-indigo-400 text-xs" title="Add to collection">+</button>
                <div x-show="open" x-cloak @click.outside="open = false"
                     class="absolute right-0 top-full mt-1 w-40 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md shadow-lg z-20 py-1 max-h-32 overflow-y-auto">
                    @foreach($collections as $coll)
                    <button wire:click="addVersionToCollection({{ $version->id }}, {{ $coll->id }})"
                            @click="open = false"
                            class="w-full text-left px-3 py-1 text-xs text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 truncate">
                        {{ $coll->title }}
                    </button>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
        @empty
        <p class="text-xs text-gray-400 dark:text-gray-500 italic">No versions yet. Write content and save.</p>
        @endforelse
    </div>

    {{-- Diff bar --}}
    @if($versions->count() > 1)
    <div x-show="diffSelection.length > 0" x-cloak class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
        <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">
            <span x-text="diffSelection.length"></span> selected
            <span x-show="diffSelection.length < 2" class="text-gray-400 dark:text-gray-500">&mdash; pick one more</span>
        </p>
        <div class="flex items-center gap-2">
            <button x-show="diffSelection.length === 2"
                    @click="
                        const versions = @js($versions->pluck('content', 'id')->toArray());
                        const labels = @js($versions->pluck('version_number', 'id')->toArray());
                        const id1 = diffSelection[0], id2 = diffSelection[1];
                        openDiff(versions[id1], versions[id2], 'v' + labels[id1], 'v' + labels[id2]);
                    "
                    class="px-2.5 py-1 text-xs bg-indigo-600 dark:bg-indigo-500 text-white rounded-md hover:bg-indigo-700 dark:hover:bg-indigo-600 transition font-medium">
                Quick Diff
            </button>
            <button @click="diffSelection = []"
                    class="text-xs text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300">
                Clear
            </button>
        </div>
    </div>

    {{-- Diff modal --}}
    <div x-show="showDiff" x-cloak class="fixed inset-0 z-50 overflow-y-auto" @keydown.escape.window="closeDiff()">
        <div class="fixed inset-0 bg-black/50" @click="closeDiff()"></div>
        <div class="relative min-h-screen flex items-start justify-center p-4 pt-16">
            <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-4xl overflow-hidden" @click.stop>
                {{-- Header --}}
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <h3 class="font-semibold text-gray-800 dark:text-gray-200">
                            <span x-text="oldLabel"></span> &rarr; <span x-text="newLabel"></span>
                        </h3>
                        <div class="inline-flex rounded-md shadow-sm">
                            <button @click="toggleMode('words')" type="button"
                                    class="px-2 py-0.5 text-xs rounded-l-md border transition"
                                    :class="diffMode === 'words'
                                        ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 border-indigo-300 dark:border-indigo-700'
                                        : 'bg-white dark:bg-gray-700 text-gray-600 dark:text-gray-400 border-gray-300 dark:border-gray-600'">
                                Words
                            </button>
                            <button @click="toggleMode('chars')" type="button"
                                    class="px-2 py-0.5 text-xs rounded-r-md border-t border-r border-b transition"
                                    :class="diffMode === 'chars'
                                        ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 border-indigo-300 dark:border-indigo-700'
                                        : 'bg-white dark:bg-gray-700 text-gray-600 dark:text-gray-400 border-gray-300 dark:border-gray-600'">
                                Chars
                            </button>
                        </div>
                    </div>
                    <button @click="closeDiff()" class="text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- Stats --}}
                <div class="px-6 py-2 bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700 text-xs text-gray-500 dark:text-gray-400 flex gap-4">
                    <span class="text-green-600 dark:text-green-400">+<span x-text="stats.added"></span> added</span>
                    <span class="text-red-600 dark:text-red-400">-<span x-text="stats.removed"></span> removed</span>
                </div>

                {{-- Diff content --}}
                <div class="p-6 max-h-[70vh] overflow-auto">
                    <pre class="font-mono text-sm whitespace-pre-wrap break-words leading-relaxed" x-html="unifiedHtml"></pre>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
