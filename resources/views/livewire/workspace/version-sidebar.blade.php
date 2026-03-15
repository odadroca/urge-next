<div class="p-3">
    <div class="flex items-center justify-between mb-3">
        <h3 class="text-sm font-semibold text-gray-700">Versions</h3>
        <span class="text-xs text-gray-400">{{ $versions->count() }}</span>
    </div>

    <div class="space-y-1">
        @forelse($versions as $version)
        <button wire:click="selectVersion({{ $version->id }})"
                class="w-full text-left px-2.5 py-1.5 rounded-md text-sm transition
                    {{ $currentVersionId === $version->id ? 'bg-indigo-50 text-indigo-700 font-medium' : 'text-gray-600 hover:bg-gray-50' }}">
            <div class="flex items-center justify-between">
                <span>v{{ $version->version_number }}</span>
                @if($prompt->pinned_version_id === $version->id)
                    <span class="text-xs text-indigo-500" title="Pinned">&#x1f4cc;</span>
                @elseif($loop->first && !$prompt->pinned_version_id)
                    <span class="w-2 h-2 rounded-full bg-green-400" title="Active (latest)"></span>
                @endif
            </div>
            @if($version->commit_message)
                <p class="text-xs text-gray-400 truncate mt-0.5">{{ $version->commit_message }}</p>
            @endif
            <p class="text-xs text-gray-400 mt-0.5">{{ $version->created_at->diffForHumans() }}</p>
        </button>
        @empty
        <p class="text-xs text-gray-400 italic">No versions yet. Write content and save.</p>
        @endforelse
    </div>
</div>
