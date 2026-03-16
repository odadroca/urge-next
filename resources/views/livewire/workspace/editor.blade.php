<div class="flex flex-col h-full">
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
        <textarea wire:model.live.debounce.300ms="content"
                  class="w-full h-full resize-none border-0 focus:ring-0 p-4 font-mono text-sm text-gray-800 dark:text-gray-200 bg-gray-50 dark:bg-gray-900"
                  placeholder="Write your prompt here...&#10;&#10;Use {'{'}{'{'} variable {'}'}{'}'}  for variables&#10;Use {'{'}{'{'}> slug {'}'}{'}'}  to include fragments"
                  spellcheck="false"></textarea>
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

    {{-- Errors --}}
    @error('content')
        <div class="px-4 py-2 bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 text-sm border-t border-red-200 dark:border-red-800">{{ $message }}</div>
    @enderror
</div>
