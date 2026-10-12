{{--
    Main navigation. Items render only when their route exists (Route::has) and,
    for admin items, when the user passes @can('admin').
    Expects the parent to own Alpine state `sidebarOpen` (layouts.app <body>).
    $store is shared by the view composer in AppServiceProvider.
--}}
@php
    $groups = [
        'Utama' => [
            ['label' => 'Dashboard', 'route' => 'dashboard', 'active' => 'dashboard', 'icon' => 'home', 'admin' => true],
            ['label' => 'Kasir', 'route' => 'pos.index', 'active' => 'pos.*', 'icon' => 'shopping-cart', 'admin' => false],
        ],
        'Master Data' => [
            ['label' => 'Produk', 'route' => 'products.index', 'active' => 'products.*', 'icon' => 'cube', 'admin' => false],
            ['label' => 'Kategori', 'route' => 'categories.index', 'active' => 'categories.*', 'icon' => 'tag', 'admin' => true],
            ['label' => 'Stok', 'route' => 'stock.index', 'active' => 'stock.*', 'icon' => 'archive-box', 'admin' => true],
        ],
        'Transaksi' => [
            ['label' => 'Riwayat Transaksi', 'route' => 'sales.index', 'active' => 'sales.*', 'icon' => 'receipt-percent', 'admin' => false],
            ['label' => 'Laporan', 'route' => 'reports.index', 'active' => 'reports.*', 'icon' => 'chart-bar', 'admin' => true],
        ],
        'Sistem' => [
            ['label' => 'Pengguna', 'route' => 'users.index', 'active' => 'users.*', 'icon' => 'users', 'admin' => true],
            ['label' => 'Pengaturan Toko', 'route' => 'settings.edit', 'active' => 'settings.*', 'icon' => 'cog-6-tooth', 'admin' => true],
        ],
    ];

    $isAdmin = auth()->user()?->can('admin') ?? false;

    // Drop items whose route does not exist yet or that the user may not see, then empty groups.
    $groups = collect($groups)
        ->map(fn ($items) => collect($items)->filter(
            fn ($item) => Route::has($item['route']) && (! $item['admin'] || $isAdmin)
        ))
        ->filter(fn ($items) => $items->isNotEmpty());

    $storeInitials = \Illuminate\Support\Str::upper(
        collect(preg_split('/\s+/', trim($store->store_name)) ?: [])->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->implode('')
    );
@endphp

{{-- Below lg the closed sidebar is `invisible` (not just translated) so its links leave the tab order;
     visibility is transitioned so the slide-out animation still plays. --}}
<aside id="app-sidebar"
       class="invisible fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col border-r border-slate-200 bg-white transition-[transform,visibility] duration-200 ease-out lg:visible lg:translate-x-0"
       :class="{ '-translate-x-full invisible lg:visible': ! sidebarOpen }"
       x-init="$watch('sidebarOpen', open => $nextTick(() => {
           if (open) { $refs.sidebarClose.focus() }
           else if ($el.contains(document.activeElement)) { $refs.sidebarToggle.focus() }
       }))"
       aria-label="Navigasi utama">
    {{-- Brand --}}
    <div class="flex h-16 shrink-0 items-center gap-3 border-b border-slate-100 px-5">
        @if ($store->logo_url)
            <img src="{{ $store->logo_url }}" alt="" class="h-9 w-9 rounded-xl object-cover ring-1 ring-slate-200">
        @else
            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-primary-600 text-sm font-bold text-white" aria-hidden="true">{{ $storeInitials }}</span>
        @endif
        <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-bold text-slate-900">{{ $store->store_name }}</p>
            <p class="text-xs text-slate-500">Aplikasi Kasir</p>
        </div>
        <button type="button" class="btn btn-ghost btn-icon lg:hidden" x-ref="sidebarClose" @click="sidebarOpen = false">
            <x-icon name="x-mark" class="h-5 w-5" />
            <span class="sr-only">Tutup menu</span>
        </button>
    </div>

    {{-- Navigation --}}
    <nav class="flex-1 space-y-6 overflow-y-auto px-3 py-5">
        @foreach ($groups as $group => $items)
            <div>
                <p class="px-3 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ $group }}</p>
                <ul class="mt-2 space-y-1">
                    @foreach ($items as $item)
                        @php($active = request()->routeIs($item['active']))
                        <li>
                            <a href="{{ route($item['route']) }}"
                               @if ($active) aria-current="page" @endif
                               @class([
                                   'group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500',
                                   'bg-primary-50 text-primary-700' => $active,
                                   'text-slate-600 hover:bg-slate-50 hover:text-slate-900' => ! $active,
                               ])>
                                <x-icon :name="$item['icon']" @class([
                                    'h-5 w-5',
                                    'text-primary-600' => $active,
                                    'text-slate-400 group-hover:text-slate-600' => ! $active,
                                ]) />
                                {{ $item['label'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </nav>

    {{-- Signed-in user --}}
    @auth
        <div class="shrink-0 border-t border-slate-100 p-4">
            <div class="flex items-center gap-3">
                <span class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-100 text-slate-500">
                    <x-icon name="user-circle" class="h-6 w-6" />
                </span>
                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-slate-800">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-slate-500">{{ auth()->user()->role->label() }}</p>
                </div>
            </div>
        </div>
    @endauth
</aside>
