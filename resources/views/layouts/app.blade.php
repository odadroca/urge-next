<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?? 'URGE' }} - URGE</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <script>
            (function() {
                function applyTheme() {
                    document.documentElement.classList.toggle('dark', localStorage.theme === 'dark');
                }
                applyTheme();
                document.addEventListener('livewire:navigated', applyTheme);
            })();
        </script>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100">
        {{-- Toast Notifications --}}
        <div x-data="toasts()" @notify.window="add($event.detail)"
             class="fixed bottom-4 right-4 z-[100] flex flex-col gap-2 pointer-events-none">
            <template x-for="toast in items" :key="toast.id">
                <div x-show="toast.visible"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 translate-y-2"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 translate-y-0"
                     x-transition:leave-end="opacity-0 translate-y-2"
                     class="pointer-events-auto px-4 py-2.5 rounded-lg shadow-lg text-sm font-medium max-w-sm"
                     :class="toast.type === 'error'
                         ? 'bg-red-600 text-white'
                         : 'bg-green-600 text-white'">
                    <span x-text="toast.message"></span>
                </div>
            </template>
        </div>

        <script>
            function toasts() {
                return {
                    items: [],
                    add(detail) {
                        const id = Date.now();
                        this.items.push({ id, message: detail.message || detail[0]?.message || '', type: detail.type || detail[0]?.type || 'success', visible: true });
                        setTimeout(() => {
                            const t = this.items.find(i => i.id === id);
                            if (t) t.visible = false;
                            setTimeout(() => { this.items = this.items.filter(i => i.id !== id); }, 200);
                        }, 3000);
                    }
                };
            }
        </script>

        <div class="min-h-screen flex flex-col">
            <nav class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-4 h-14 flex items-center justify-between shrink-0">
                <div class="flex items-center gap-6">
                    <a href="{{ route('dashboard') }}" wire:navigate class="text-lg font-bold text-indigo-600 dark:text-indigo-400 tracking-tight">URGE</a>

                    <div class="hidden sm:flex items-center gap-1">
                        <a href="{{ route('dashboard') }}" wire:navigate
                           class="px-3 py-1.5 rounded-md text-sm font-medium {{ request()->routeIs('dashboard') ? 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-300' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                            Dashboard
                        </a>
                        <a href="{{ route('browse') }}" wire:navigate
                           class="px-3 py-1.5 rounded-md text-sm font-medium {{ request()->routeIs('browse') ? 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-300' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                            Browse
                        </a>
                        @if(auth()->user()?->isAdmin())
                        <a href="{{ route('settings') }}" wire:navigate
                           class="px-3 py-1.5 rounded-md text-sm font-medium {{ request()->routeIs('settings') ? 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-300' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                            Settings
                        </a>
                        @endif
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <button
                        x-data="{ dark: localStorage.theme === 'dark' }"
                        x-on:click="dark = !dark; localStorage.theme = dark ? 'dark' : 'light'; document.documentElement.classList.toggle('dark', dark)"
                        class="p-1.5 rounded-md text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition"
                        title="Toggle dark mode">
                        <svg x-show="!dark" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                        </svg>
                        <svg x-show="dark" x-cloak xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </button>
                    <span class="text-sm text-gray-500 dark:text-gray-400">{{ Auth::user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-sm text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300">Logout</button>
                    </form>
                </div>
            </nav>

            <main class="flex-1 overflow-hidden">
                {{ $slot }}
            </main>
        </div>
    </body>
</html>
