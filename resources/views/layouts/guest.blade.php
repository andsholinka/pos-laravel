{{-- Split-screen layout for login / password pages. $store comes from the view composer. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ isset($title) ? $title.' · ' : '' }}{{ $store->store_name }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="h-full bg-white font-sans text-slate-800 antialiased">
        <div class="flex min-h-full">
            {{-- Brand panel (desktop) --}}
            <div class="relative hidden w-1/2 overflow-hidden bg-gradient-to-br from-emerald-600 via-emerald-700 to-teal-800 lg:flex lg:flex-col lg:justify-between lg:p-12 xl:p-16">
                <div class="pointer-events-none absolute -right-24 -top-24 h-96 w-96 rounded-full bg-white/10 blur-2xl" aria-hidden="true"></div>
                <div class="pointer-events-none absolute -bottom-32 -left-16 h-96 w-96 rounded-full bg-emerald-400/20 blur-3xl" aria-hidden="true"></div>

                <div class="relative flex items-center gap-3 text-white">
                    @if ($store->logo_url)
                        <img src="{{ $store->logo_url }}" alt="" class="h-11 w-11 rounded-xl bg-white object-cover">
                    @else
                        <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-white/15 ring-1 ring-white/30">
                            <x-icon name="building-storefront" class="h-6 w-6" />
                        </span>
                    @endif
                    <span class="text-lg font-bold">{{ $store->store_name }}</span>
                </div>

                <div class="relative max-w-md text-white">
                    <h2 class="text-4xl font-extrabold leading-tight tracking-tight">Kasir pintar untuk usaha Anda.</h2>
                    <p class="mt-4 text-lg text-emerald-50">Kelola penjualan, stok, dan laporan toko Anda dalam satu aplikasi.</p>

                    <ul class="mt-10 space-y-5">
                        <li class="flex items-start gap-4">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/15 ring-1 ring-white/20">
                                <x-icon name="shopping-cart" class="h-5 w-5" />
                            </span>
                            <div>
                                <p class="font-semibold">Transaksi cepat</p>
                                <p class="text-sm text-emerald-50">Cari produk, scan barcode, dan cetak struk dalam hitungan detik.</p>
                            </div>
                        </li>
                        <li class="flex items-start gap-4">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/15 ring-1 ring-white/20">
                                <x-icon name="archive-box" class="h-5 w-5" />
                            </span>
                            <div>
                                <p class="font-semibold">Stok selalu terpantau</p>
                                <p class="text-sm text-emerald-50">Setiap barang masuk dan keluar tercatat, lengkap dengan peringatan stok menipis.</p>
                            </div>
                        </li>
                        <li class="flex items-start gap-4">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/15 ring-1 ring-white/20">
                                <x-icon name="chart-bar" class="h-5 w-5" />
                            </span>
                            <div>
                                <p class="font-semibold">Laporan laba rugi</p>
                                <p class="text-sm text-emerald-50">Pantau omzet dan laba kotor harian maupun bulanan, siap ekspor PDF dan Excel.</p>
                            </div>
                        </li>
                    </ul>
                </div>

                <p class="relative text-sm text-emerald-100">&copy; {{ now()->year }} {{ $store->store_name }}</p>
            </div>

            {{-- Form panel --}}
            <main class="flex w-full flex-1 flex-col justify-center bg-slate-50 px-4 py-12 sm:px-6 lg:w-1/2 lg:bg-white lg:px-16">
                <div class="mx-auto w-full max-w-md">
                    <div class="mb-8 flex items-center gap-3 lg:hidden">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary-600 text-white">
                            <x-icon name="building-storefront" class="h-5 w-5" />
                        </span>
                        <span class="text-lg font-bold text-slate-900">{{ $store->store_name }}</span>
                    </div>

                    <div class="card p-6 sm:p-8 lg:shadow-none lg:ring-0">
                        {{ $slot }}
                    </div>
                </div>
            </main>
        </div>
    </body>
</html>
