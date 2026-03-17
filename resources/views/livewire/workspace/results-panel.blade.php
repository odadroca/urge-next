<div class="p-3" x-data="{ compareIds: [], showCompare: false }">
    <div class="flex items-center justify-between mb-3">
        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Results</h3>
        <div class="flex items-center gap-2">
            @if($results->count() > 0)
            <button wire:click="exportAllResults" wire:loading.attr="disabled"
                    class="text-xs text-gray-400 dark:text-gray-500 hover:text-indigo-600 dark:hover:text-indigo-400 disabled:opacity-50" title="Export all as ZIP">
                <span wire:loading.remove wire:target="exportAllResults">Export All</span>
                <span wire:loading wire:target="exportAllResults">Exporting...</span>
            </button>
            @endif
            <label class="flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                <input type="checkbox" wire:model.live="showAllVersions" class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500 dark:bg-gray-700">
                All versions
            </label>
        </div>
    </div>

    {{-- Compare bar --}}
    @if($results->count() > 1)
    <div x-show="compareIds.length > 0" x-cloak
         class="mb-3 bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-800 rounded-md px-3 py-2">
        <div class="flex items-center justify-between">
            <span class="text-xs text-indigo-700 dark:text-indigo-300 font-medium">
                <span x-text="compareIds.length"></span> selected
            </span>
            <div class="flex items-center gap-2">
                <button @click="compareIds = []" class="text-xs text-indigo-500 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300">Clear</button>
                <button @click="showCompare = true" x-show="compareIds.length >= 2"
                        class="px-2 py-0.5 text-xs bg-indigo-600 dark:bg-indigo-500 text-white rounded hover:bg-indigo-700 dark:hover:bg-indigo-600 font-medium transition">
                    Compare
                </button>
            </div>
        </div>
    </div>
    @endif

    <div class="space-y-3">
        @forelse($results as $result)
        <div class="bg-gray-50 dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700 p-3" wire:key="result-{{ $result->id }}"
             :class="compareIds.includes('{{ $result->id }}') ? 'ring-2 ring-indigo-300 dark:ring-indigo-600' : ''">
            {{-- Header --}}
            <div class="flex items-center justify-between mb-1.5">
                <div class="flex items-center gap-1.5">
                    @if($results->count() > 1)
                    <input type="checkbox"
                           value="{{ $result->id }}"
                           x-model="compareIds"
                           :disabled="compareIds.length >= 4 && !compareIds.includes('{{ $result->id }}')"
                           class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500 w-3 h-3 disabled:opacity-30 cursor-pointer disabled:cursor-not-allowed">
                    @endif
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

            {{-- Meta info --}}
            @if($result->duration_ms || $result->input_tokens || $result->output_tokens)
            <div class="flex flex-wrap gap-2 text-xs text-gray-400 dark:text-gray-500 mt-1.5">
                @if($result->duration_ms)
                <span>{{ number_format($result->duration_ms / 1000, 2) }}s</span>
                @endif
                @if($result->input_tokens || $result->output_tokens)
                <span>{{ $result->input_tokens ?? '?' }} in / {{ $result->output_tokens ?? '?' }} out</span>
                @endif
            </div>
            @endif

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

            {{-- Actions --}}
            <div class="flex items-center gap-2 mt-2" x-data="{ showCollPicker: false }">
                <button x-data @click="navigator.clipboard.writeText(@js($result->response_text))"
                        class="text-xs text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300">Copy</button>
                <button wire:click="exportResult({{ $result->id }})"
                        class="text-xs text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300">Export</button>
                <div class="relative">
                    <button @click="showCollPicker = !showCollPicker"
                            class="text-xs text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300">+ Collection</button>
                    <div x-show="showCollPicker" x-cloak @click.outside="showCollPicker = false"
                         class="absolute bottom-full right-0 mb-1 w-48 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md shadow-lg z-20 py-1 max-h-40 overflow-y-auto">
                        @forelse($collections as $coll)
                        <button wire:click="addResultToCollection({{ $result->id }}, {{ $coll->id }})"
                                @click="showCollPicker = false"
                                class="w-full text-left px-3 py-1.5 text-xs text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 truncate">
                            {{ $coll->title }}
                        </button>
                        @empty
                        <p class="px-3 py-1.5 text-xs text-gray-400 dark:text-gray-500 italic">No collections yet</p>
                        @endforelse
                    </div>
                </div>
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

    {{-- Compare modal --}}
    @if($results->count() > 1)
    @php
        $resultData = $results->mapWithKeys(function ($r) {
            return [(string) $r->id => [
                'id' => $r->id,
                'provider' => $r->provider_name ?: 'Manual',
                'model' => $r->model_name,
                'text' => $r->response_text,
                'rating' => $r->rating,
                'starred' => $r->starred,
                'duration_ms' => $r->duration_ms,
                'input_tokens' => $r->input_tokens,
                'output_tokens' => $r->output_tokens,
                'version' => $r->promptVersion->version_number ?? null,
                'notes' => $r->notes,
                'created_at' => $r->created_at->diffForHumans(),
            ]];
        });
    @endphp
    <div x-show="showCompare" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         @keydown.escape.window="showCompare = false"
         x-data="{ allResults: {{ $resultData->toJson() }} }">
        <div class="fixed inset-0 bg-black/50" @click="showCompare = false"></div>
        <div class="relative min-h-screen flex items-start justify-center p-4 pt-8">
            <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-7xl overflow-hidden" @click.stop>
                {{-- Header --}}
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <h3 class="font-semibold text-gray-800 dark:text-gray-200 text-lg">Compare Results</h3>
                        {{-- AI Summarize --}}
                        <div x-show="compareIds.length === 2" x-data="{ showAiPicker: false }" class="relative">
                            <button @click="showAiPicker = !showAiPicker" wire:loading.attr="disabled" wire:target="aiSummarizeDifferences"
                                    class="px-2 py-1 text-xs text-purple-600 dark:text-purple-400 border border-purple-300 dark:border-purple-700 rounded-md hover:bg-purple-50 dark:hover:bg-purple-900/20 transition disabled:opacity-50">
                                <span wire:loading.remove wire:target="aiSummarizeDifferences">AI Summarize</span>
                                <span wire:loading wire:target="aiSummarizeDifferences">Analyzing...</span>
                            </button>
                            <div x-show="showAiPicker" x-cloak @click.outside="showAiPicker = false"
                                 class="absolute left-0 top-full mt-1 w-52 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md shadow-lg z-30 py-1">
                                <p class="px-3 py-1 text-xs text-gray-500 dark:text-gray-400 font-medium border-b border-gray-100 dark:border-gray-700">Select provider</p>
                                @foreach(\App\Models\LlmProvider::where('is_active', true)->get() as $prov)
                                <button wire:click="aiSummarizeDifferences(compareIds[0], compareIds[1], {{ $prov->id }})"
                                        @click="showAiPicker = false"
                                        class="w-full text-left px-3 py-1.5 text-xs text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 truncate">
                                    {{ $prov->name }} <span class="text-gray-400 dark:text-gray-500">({{ $prov->model }})</span>
                                </button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <button @click="showCompare = false" class="text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- Side-by-side --}}
                <div class="p-6 overflow-x-auto">
                    <div class="flex gap-4" :style="'min-width: ' + (compareIds.length * 320) + 'px'">
                        <template x-for="rid in compareIds" :key="rid">
                            <div class="flex-1 min-w-[300px] border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                                {{-- Column header --}}
                                <div class="px-4 py-3 bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <span class="font-semibold text-gray-800 dark:text-gray-200 text-sm" x-text="allResults[rid]?.provider"></span>
                                            <span class="text-xs text-gray-400 dark:text-gray-500 font-mono ml-1" x-text="allResults[rid]?.model"></span>
                                        </div>
                                        <template x-if="allResults[rid]?.rating">
                                            <span class="text-yellow-500 text-sm" x-text="'★'.repeat(allResults[rid].rating) + '☆'.repeat(5 - allResults[rid].rating)"></span>
                                        </template>
                                    </div>
                                    <div class="flex gap-3 text-[10px] text-gray-400 dark:text-gray-500 mt-1">
                                        <template x-if="allResults[rid]?.duration_ms">
                                            <span x-text="(allResults[rid].duration_ms / 1000).toFixed(2) + 's'"></span>
                                        </template>
                                        <template x-if="allResults[rid]?.input_tokens || allResults[rid]?.output_tokens">
                                            <span x-text="(allResults[rid].input_tokens || '?') + ' in / ' + (allResults[rid].output_tokens || '?') + ' out'"></span>
                                        </template>
                                        <template x-if="allResults[rid]?.version">
                                            <span x-text="'v' + allResults[rid].version"></span>
                                        </template>
                                    </div>
                                </div>

                                {{-- Response body --}}
                                <div class="p-4 max-h-[60vh] overflow-auto">
                                    <pre class="text-sm font-mono text-gray-800 dark:text-gray-200 whitespace-pre-wrap break-words leading-relaxed" x-text="allResults[rid]?.text"></pre>
                                </div>
                            </div>
                        </template>
                    </div>

                    {{-- AI Summary --}}
                    @if($aiSummary)
                    <div class="mt-4 bg-purple-50 dark:bg-purple-900/10 border border-purple-200 dark:border-purple-800 rounded-lg p-4">
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="text-sm font-medium text-purple-700 dark:text-purple-300">AI Comparison Summary</h4>
                            <button wire:click="$set('aiSummary', null)" class="text-purple-400 hover:text-purple-600 dark:hover:text-purple-200 text-sm">&times;</button>
                        </div>
                        <div class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $aiSummary }}</div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
