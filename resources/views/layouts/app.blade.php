<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?? 'URGE' }} - URGE</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-gray-50 text-gray-900">
        <div class="min-h-screen flex flex-col">
            <nav class="bg-white border-b border-gray-200 px-4 h-14 flex items-center justify-between shrink-0">
                <div class="flex items-center gap-6">
                    <a href="{{ route('dashboard') }}" wire:navigate class="text-lg font-bold text-indigo-600 tracking-tight">URGE</a>

                    <div class="hidden sm:flex items-center gap-1">
                        <a href="{{ route('dashboard') }}" wire:navigate
                           class="px-3 py-1.5 rounded-md text-sm font-medium {{ request()->routeIs('dashboard') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100' }}">
                            Dashboard
                        </a>
                        <a href="{{ route('browse') }}" wire:navigate
                           class="px-3 py-1.5 rounded-md text-sm font-medium {{ request()->routeIs('browse') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100' }}">
                            Browse
                        </a>
                        @if(auth()->user()?->isAdmin())
                        <a href="{{ route('settings') }}" wire:navigate
                           class="px-3 py-1.5 rounded-md text-sm font-medium {{ request()->routeIs('settings') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100' }}">
                            Settings
                        </a>
                        @endif
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <span class="text-sm text-gray-500">{{ Auth::user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-sm text-gray-400 hover:text-gray-600">Logout</button>
                    </form>
                </div>
            </nav>

            <main class="flex-1 overflow-hidden">
                {{ $slot }}
            </main>
        </div>
    </body>
</html>
