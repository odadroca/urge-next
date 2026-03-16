<div class="flex flex-col h-full" x-data="autocomplete()">
    {{-- Toolbar --}}
    <div class="flex items-center justify-between px-4 py-2 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shrink-0">
        <div class="flex items-center gap-3">
            <h2 class="font-semibold text-gray-900 dark:text-gray-100">{{ $prompt->name }}</h2>
            @if($currentVersionId)
                <span class="text-xs text-gray-400 dark:text-gray-500">
                    v{{ App\Models\PromptVersion::find($currentVersionId)?->version_number }}
                </span>
            @endif
            @if($isDirty)
                <span class="text-xs text-amber-600 dark:text-amber-400 font-medium">unsaved</span>
            @endif
        </div>

        <div class="flex items-center gap-2">
            {{-- Editor mode toggle --}}
            <div class="inline-flex rounded-md shadow-sm" x-data="{ mode: @js($editorMode) }">
                <button type="button"
                        wire:click="switchMode('text')"
                        @click="mode = 'text'"
                        class="px-2.5 py-1 text-xs font-medium rounded-l-md border transition"
                        :class="mode === 'text'
                            ? 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-300 border-indigo-300 dark:border-indigo-700'
                            : 'bg-white dark:bg-gray-700 text-gray-600 dark:text-gray-400 border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600'">
                    Text
                </button>
                <button type="button"
                        wire:click="switchMode('visual')"
                        @click="mode = 'visual'"
                        class="px-2.5 py-1 text-xs font-medium rounded-r-md border-t border-r border-b transition"
                        :class="mode === 'visual'
                            ? 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-300 border-indigo-300 dark:border-indigo-700'
                            : 'bg-white dark:bg-gray-700 text-gray-600 dark:text-gray-400 border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600'">
                    Visual
                </button>
            </div>

            <input wire:model="commitMessage" type="text" placeholder="Commit message (optional)"
                   class="w-48 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm text-xs focus:border-indigo-500 focus:ring-indigo-500 py-1">
            <button wire:click="saveVersion" wire:loading.attr="disabled"
                    class="px-3 py-1.5 bg-indigo-600 dark:bg-indigo-500 text-white text-sm font-medium rounded-md hover:bg-indigo-700 dark:hover:bg-indigo-600 disabled:opacity-50 transition">
                <span wire:loading.remove wire:target="saveVersion">Save Version</span>
                <span wire:loading wire:target="saveVersion">Saving...</span>
            </button>
        </div>
    </div>

    {{-- Editor area --}}
    <div class="flex-1 overflow-hidden relative">
        @if($editorMode === 'text')
        {{-- Text editor with autocomplete --}}
        <textarea wire:model.live.debounce.300ms="content"
                  x-ref="editorTextarea"
                  @input="handleInput($event); positionDropdown($event.target)"
                  @keydown="handleKeydown($event)"
                  class="w-full h-full resize-none border-0 focus:ring-0 p-4 font-mono text-sm text-gray-800 dark:text-gray-200 bg-gray-50 dark:bg-gray-900"
                  placeholder="Write your prompt here...&#10;&#10;Use {'{'}{'{'} variable {'}'}{'}'}  for variables&#10;Use {'{'}{'{'}> slug {'}'}{'}'}  to include fragments"
                  spellcheck="false"></textarea>

        {{-- Autocomplete dropdown --}}
        <div x-ref="autocompleteDropdown"
             x-show="showDropdown" x-cloak
             class="absolute z-50 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg max-h-48 overflow-auto min-w-[200px]"
             @click.outside="dismiss()">
            <template x-for="(item, idx) in filteredItems" :key="item.value">
                <button type="button"
                        @click="selectedIndex = idx; insertSelected()"
                        @mouseenter="selectedIndex = idx"
                        class="w-full text-left px-3 py-1.5 text-sm flex items-center gap-2 transition-colors"
                        :class="idx === selectedIndex
                            ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300'
                            : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'">
                    <span x-show="triggerType === 'variable'" class="text-blue-500 dark:text-blue-400 text-xs font-mono">{<span>{</span></span>
                    <span x-show="triggerType === 'fragment'" class="text-purple-500 dark:text-purple-400 text-xs font-mono">{<span>{</span>&gt;</span>
                    <span>
                        <span class="font-mono" x-text="item.value"></span>
                        <span x-show="item.label !== item.value" class="text-xs text-gray-400 dark:text-gray-500 ml-1" x-text="item.label"></span>
                    </span>
                </button>
            </template>
            <div x-show="filteredItems.length === 0" class="px-3 py-2 text-xs text-gray-400 dark:text-gray-500 italic">No matches</div>
        </div>
        @else
        {{-- Visual composer --}}
        <div class="h-full overflow-auto p-4 bg-gray-50 dark:bg-gray-900" x-data="composer()" x-init="parseContent(@js($content))">
            <div class="space-y-2 mb-4" x-ref="composerBlocks" x-init="initSortable($refs.composerBlocks)">
                <template x-for="(block, index) in blocks" :key="block.id">
                    <div class="flex items-start gap-2 group">
                        <span class="composer-handle cursor-grab text-gray-300 dark:text-gray-600 hover:text-gray-500 dark:hover:text-gray-400 mt-2 text-sm select-none">&#9776;</span>

                        <div class="flex-1" x-show="block.type === 'text'">
                            <textarea x-model="block.value"
                                      @input="$wire.set('content', serialize()); $wire.call('updatedContent')"
                                      rows="2"
                                      class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 text-sm font-mono resize-y focus:border-indigo-500 focus:ring-indigo-500"
                                      placeholder="Text block..."></textarea>
                        </div>

                        <div class="flex-1" x-show="block.type === 'variable'">
                            <div class="flex items-center gap-2 px-3 py-2 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-md">
                                <span class="text-blue-700 dark:text-blue-400 font-mono text-sm">{<span>{</span></span>
                                <input x-model="block.value"
                                       @input="$wire.set('content', serialize()); $wire.call('updatedContent')"
                                       class="flex-1 bg-transparent border-0 text-sm font-mono text-blue-700 dark:text-blue-300 focus:ring-0 p-0"
                                       placeholder="variable_name">
                                <span class="text-blue-700 dark:text-blue-400 font-mono text-sm">}<span>}</span></span>
                            </div>
                        </div>

                        <div class="flex-1" x-show="block.type === 'include'">
                            <div class="flex items-center gap-2 px-3 py-2 bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-md">
                                <span class="text-purple-700 dark:text-purple-400 font-mono text-sm">{<span>{</span>&gt;</span>
                                <input x-model="block.value"
                                       @input="$wire.set('content', serialize()); $wire.call('updatedContent')"
                                       class="flex-1 bg-transparent border-0 text-sm font-mono text-purple-700 dark:text-purple-300 focus:ring-0 p-0"
                                       placeholder="fragment-slug">
                                <span class="text-purple-700 dark:text-purple-400 font-mono text-sm">}<span>}</span></span>
                            </div>
                        </div>

                        <button @click="removeBlock(index); $wire.set('content', serialize()); $wire.call('updatedContent')"
                                class="text-gray-300 dark:text-gray-600 hover:text-red-500 dark:hover:text-red-400 opacity-0 group-hover:opacity-100 transition mt-2 text-sm">
                            &times;
                        </button>
                    </div>
                </template>
            </div>

            {{-- Add block buttons --}}
            <div class="flex items-center gap-2">
                <button @click="addTextBlock()" type="button"
                        class="px-2.5 py-1 text-xs bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-400 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700">
                    + Text
                </button>
                <button @click="addVariableBlock()" type="button"
                        class="px-2.5 py-1 text-xs bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 text-blue-700 dark:text-blue-400 rounded-md hover:bg-blue-100 dark:hover:bg-blue-900/40">
                    + Variable
                </button>
                <button @click="addIncludeBlock()" type="button"
                        class="px-2.5 py-1 text-xs bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 text-purple-700 dark:text-purple-400 rounded-md hover:bg-purple-100 dark:hover:bg-purple-900/40">
                    + Include
                </button>
            </div>
        </div>
        @endif
    </div>

    {{-- Detected tokens bar --}}
    @if(!empty($detectedVariables) || !empty($detectedIncludes))
    <div class="px-4 py-2 border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shrink-0 flex flex-wrap gap-2">
        @foreach($detectedVariables as $var)
            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400 border border-blue-200 dark:border-blue-800">
                {'{'}{'{'} {{ $var }} {'}'}{'}'}
            </span>
        @endforeach
        @foreach($detectedIncludes as $inc)
            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono bg-purple-50 dark:bg-purple-900/20 text-purple-700 dark:text-purple-400 border border-purple-200 dark:border-purple-800">
                {'{'}{'{'}> {{ $inc }} {'}'}{'}'}
            </span>
        @endforeach
    </div>
    @endif

    {{-- Variable Metadata Panel --}}
    @if(!empty($detectedVariables))
    <div x-data="{ showMeta: false }" class="border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shrink-0">
        <button @click="showMeta = !showMeta" type="button"
                class="w-full px-4 py-2 flex items-center justify-between text-xs font-medium text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
            <span>Variable Metadata</span>
            <svg class="w-3.5 h-3.5 transition-transform" :class="showMeta ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>

        <div x-show="showMeta" x-cloak class="px-4 pb-3 space-y-3 max-h-48 overflow-auto">
            @foreach($detectedVariables as $var)
            @php
                $meta = $variableMetadata[$var] ?? ['type' => 'string', 'default' => '', 'description' => ''];
            @endphp
            <div class="bg-gray-50 dark:bg-gray-900 rounded-md p-2.5 space-y-1.5">
                <div class="flex items-center gap-2">
                    <span class="font-mono text-xs font-medium text-blue-700 dark:text-blue-400">{{ $var }}</span>
                    <select wire:change="setMetaField('{{ $var }}', 'type', $event.target.value)"
                            class="text-xs rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 py-0.5 focus:border-indigo-500 focus:ring-indigo-500">
                        @foreach(['string', 'text', 'enum', 'number', 'boolean'] as $type)
                        <option value="{{ $type }}" {{ ($meta['type'] ?? 'string') === $type ? 'selected' : '' }}>{{ $type }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-1.5">
                    <input type="text" value="{{ $meta['default'] ?? '' }}"
                           wire:change="setMetaField('{{ $var }}', 'default', $event.target.value)"
                           placeholder="Default value"
                           class="text-xs rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 py-1 focus:border-indigo-500 focus:ring-indigo-500">
                    <input type="text" value="{{ $meta['description'] ?? '' }}"
                           wire:change="setMetaField('{{ $var }}', 'description', $event.target.value)"
                           placeholder="Description"
                           class="text-xs rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 py-1 focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                @if(($meta['type'] ?? 'string') === 'enum')
                <input type="text" value="{{ $meta['options_csv'] ?? (isset($meta['options']) ? implode(', ', $meta['options']) : '') }}"
                       wire:change="setMetaField('{{ $var }}', 'options_csv', $event.target.value)"
                       placeholder="Options (comma-separated)"
                       class="w-full text-xs rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 py-1 focus:border-indigo-500 focus:ring-indigo-500">
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Errors --}}
    @error('content')
        <div class="px-4 py-2 bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 text-sm border-t border-red-200 dark:border-red-800">{{ $message }}</div>
    @enderror
</div>
