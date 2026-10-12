{{--
    Main app shell: sidebar + topbar + page header.
    Usage:
        <x-app-layout title="Produk" subtitle="Kelola daftar produk">
            <x-slot name="actions"><a class="btn btn-primary" ...>Tambah</a></x-slot>
            ...
        </x-app-layout>
    `title` / `subtitle` may also be passed as named slots.
--}}
@php
    $pageTitle = isset($title) ? trim(strip_tags((string) $title)) : '';
    $user = auth()->user();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $pageTitle !== '' ? $pageTitle.' · ' : '' }}{{ $store->store_name }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @stack('head')
    </head>
    <body class="h-full bg-slate-100 font-sans text-slate-800 antialiased"
          x-data="{ sidebarOpen: false }"
          @keydown.escape.window="sidebarOpen = false">
        <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[70] focus:rounded-lg focus:bg-white focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-primary-700 focus:shadow-lg focus:ring-2 focus:ring-primary-500">
            Lewati ke konten utama
        </a>

        {{-- Mobile overlay --}}
        <div x-show="sidebarOpen" x-cloak
             x-transition.opacity
             class="fixed inset-0 z-30 bg-slate-900/50 lg:hidden"
             @click="sidebarOpen = false"
             aria-hidden="true"></div>

        <x-app-sidebar />

        <div class="flex min-h-full flex-col lg:pl-64">
            {{-- Topbar --}}
            <header class="sticky top-0 z-20 flex h-16 shrink-0 items-center gap-3 border-b border-slate-200 bg-white/95 px-4 backdrop-blur sm:px-6 lg:px-8">
                <button type="button" class="btn btn-ghost btn-icon -ml-2 lg:hidden"
                        @click="sidebarOpen = true"
                        :aria-expanded="sidebarOpen.toString()"
                        aria-controls="app-sidebar"
                        aria-label="Buka menu">
                    <x-icon name="bars-3" class="h-6 w-6" />
                </button>

                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-semibold text-slate-900">{{ $pageTitle !== '' ? $pageTitle : $store->store_name }}</p>
                    <p class="hidden text-xs text-slate-500 sm:block">
                        <x-icon name="calendar" class="-mt-0.5 mr-1 inline h-3.5 w-3.5" />{{ now()->translatedFormat('l, d F Y') }}
                    </p>
                </div>

                @if (Route::has('pos.index') && ! request()->routeIs('pos.*'))
                    <a href="{{ route('pos.index') }}" class="btn btn-primary">
                        <x-icon name="shopping-cart" class="h-4 w-4" />
                        <span class="hidden sm:inline">Buka Kasir</span>
                        <span class="sr-only sm:hidden">Buka Kasir</span>
                    </a>
                @endif

                @if ($user)
                    <x-dropdown align="right" width="w-60">
                        <x-slot name="trigger">
                            <button type="button" class="flex items-center gap-2 rounded-lg p-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">
                                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-primary-100 text-xs font-bold text-primary-700" aria-hidden="true">
                                    {{ \Illuminate\Support\Str::upper(mb_substr($user->name, 0, 1)) }}
                                </span>
                                <span class="hidden max-w-[10rem] truncate md:inline">{{ $user->name }}</span>
                                <x-icon name="chevron-down" class="h-4 w-4 text-slate-400" />
                                <span class="sr-only">Menu pengguna</span>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <div class="border-b border-slate-100 px-4 py-3">
                                <p class="truncate text-sm font-semibold text-slate-900">{{ $user->name }}</p>
                                <p class="truncate text-xs text-slate-500">{{ $user->email }}</p>
                                <x-badge :color="$user->role->color()" class="mt-2">{{ $user->role->label() }}</x-badge>
                            </div>
                            <x-dropdown-link :href="route('profile.edit')">
                                <x-icon name="user-circle" class="h-4 w-4 text-slate-400" />
                                Profil
                            </x-dropdown-link>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <x-dropdown-link :href="route('logout')"
                                        onclick="event.preventDefault(); this.closest('form').submit();">
                                    <x-icon name="arrow-left-on-rectangle" class="h-4 w-4 text-slate-400" />
                                    Keluar
                                </x-dropdown-link>
                            </form>
                        </x-slot>
                    </x-dropdown>
                @endif
            </header>

            <main id="main" tabindex="-1" class="flex-1 p-4 focus:outline-none sm:p-6 lg:p-8">
                <x-flash />

                @if ($pageTitle !== '' || isset($actions))
                    <x-page-header :title="$pageTitle" :subtitle="isset($subtitle) ? (string) $subtitle : null">
                        @isset($actions)
                            <x-slot name="actions">{{ $actions }}</x-slot>
                        @endisset
                    </x-page-header>
                @endif

                {{ $slot }}
            </main>
        </div>

        @stack('scripts')
    </body>
</html>
