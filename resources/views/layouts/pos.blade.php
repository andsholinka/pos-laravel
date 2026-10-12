{{--
    Full-height cashier layout: compact top bar + $slot filling the rest of the screen.
    $store comes from the view composer.
--}}
@php
    $user = auth()->user();
    $backUrl = $user->isAdmin()
        ? route('dashboard')
        : (Route::has('sales.index') ? route('sales.index') : null);
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Kasir · {{ $store->store_name }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="h-full font-sans text-slate-800 antialiased">
        <div class="flex h-screen flex-col overflow-hidden bg-slate-100">
            <header class="flex h-14 shrink-0 items-center gap-3 border-b border-slate-200 bg-white px-3 sm:px-4">
                @if ($backUrl)
                    <a href="{{ $backUrl }}" class="btn btn-ghost btn-sm">
                        <x-icon name="arrow-left" class="h-4 w-4" />
                        <span class="hidden sm:inline">Kembali</span>
                        <span class="sr-only sm:hidden">Kembali</span>
                    </a>
                @endif

                <div class="flex min-w-0 items-center gap-2">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-primary-600 text-white" aria-hidden="true">
                        <x-icon name="shopping-cart" class="h-4 w-4" />
                    </span>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-bold text-slate-900">{{ $store->store_name }}</p>
                        <p class="hidden text-xs text-slate-500 sm:block">Mode Kasir</p>
                    </div>
                </div>

                <div class="ml-auto flex items-center gap-2 sm:gap-4">
                    <div class="hidden items-center gap-1.5 rounded-lg bg-slate-100 px-3 py-1.5 text-sm font-semibold tabular-nums text-slate-700 md:flex"
                         x-data="{ now: new Date() }"
                         x-init="setInterval(() => now = new Date(), 1000)">
                        <x-icon name="clock" class="h-4 w-4 text-slate-500" />
                        <span x-text="now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' })">{{ now()->format('H:i:s') }}</span>
                    </div>

                    <div class="hidden items-center gap-2 text-sm sm:flex">
                        <x-icon name="user-circle" class="h-5 w-5 text-slate-400" />
                        <span class="max-w-[10rem] truncate font-medium text-slate-700">{{ $user->name }}</span>
                    </div>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn btn-secondary btn-sm">
                            <x-icon name="arrow-left-on-rectangle" class="h-4 w-4" />
                            <span class="hidden sm:inline">Keluar</span>
                            <span class="sr-only sm:hidden">Keluar</span>
                        </button>
                    </form>
                </div>
            </header>

            <main id="main" class="min-h-0 flex-1">
                {{ $slot }}
            </main>
        </div>

        @stack('scripts')
    </body>
</html>
