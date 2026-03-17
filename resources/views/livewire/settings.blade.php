<div class="p-6 max-w-4xl mx-auto">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">Settings</h1>

    {{-- Tabs --}}
    <div class="border-b border-gray-200 dark:border-gray-700 mb-6">
        <nav class="flex space-x-4">
            <button wire:click="$set('activeTab', 'api-keys')"
                    class="px-3 py-2 text-sm font-medium border-b-2 transition {{ $activeTab === 'api-keys' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300' }}">
                API Keys
            </button>
            <button wire:click="$set('activeTab', 'llm-providers')"
                    class="px-3 py-2 text-sm font-medium border-b-2 transition {{ $activeTab === 'llm-providers' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300' }}">
                LLM Providers
            </button>
            <button wire:click="$set('activeTab', 'categories')"
                    class="px-3 py-2 text-sm font-medium border-b-2 transition {{ $activeTab === 'categories' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300' }}">
                Categories
            </button>
            @if(auth()->user()->isAdmin())
            <button wire:click="$set('activeTab', 'users')"
                    class="px-3 py-2 text-sm font-medium border-b-2 transition {{ $activeTab === 'users' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300' }}">
                Users
            </button>
            @endif
        </nav>
    </div>

    {{-- Tab Content --}}
    @if($activeTab === 'api-keys')
        <livewire:settings.api-keys />
    @elseif($activeTab === 'llm-providers')
        <livewire:settings.llm-providers />
    @elseif($activeTab === 'categories')
        <livewire:settings.categories />
    @elseif($activeTab === 'users' && auth()->user()->isAdmin())
        <livewire:settings.user-management />
    @endif
</div>
