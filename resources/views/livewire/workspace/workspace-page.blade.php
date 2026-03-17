<div class="h-[calc(100vh-3.5rem)] flex overflow-hidden">
    {{-- Version Sidebar --}}
    <div class="w-64 border-r border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 flex flex-col shrink-0 overflow-y-auto">
        <livewire:workspace.version-sidebar :prompt="$prompt" :current-version="$currentVersion" :key="'vs-'.$prompt->id" />

        <div class="border-t border-gray-200 dark:border-gray-700 p-3">
            <livewire:workspace.prompt-metadata :prompt="$prompt" :key="'pm-'.$prompt->id" />
        </div>
    </div>

    {{-- Editor Panel --}}
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
        <livewire:workspace.editor :prompt="$prompt" :current-version="$currentVersion" :key="'ed-'.$prompt->id" />
    </div>

    {{-- Results Panel --}}
    <div class="w-80 border-l border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 flex flex-col shrink-0 overflow-y-auto">
        <livewire:workspace.results-panel :prompt="$prompt" :current-version="$currentVersion" :key="'rp-'.$prompt->id" />

        <div class="border-t border-gray-200 dark:border-gray-700">
            <livewire:workspace.manual-result-form :prompt="$prompt" :current-version="$currentVersion" :key="'mr-'.$prompt->id" />
        </div>

        <livewire:workspace.import-results :prompt="$prompt" :current-version="$currentVersion" :key="'ir-'.$prompt->id" />
    </div>
</div>
