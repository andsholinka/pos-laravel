{{-- Placeholder: the full dashboard (stats, charts, low stock, latest sales) arrives in FEAT-004. --}}
<x-app-layout title="Dashboard" subtitle="Ringkasan penjualan dan stok toko Anda.">
    <x-card>
        <x-empty-state icon="chart-bar"
                       title="Selamat datang, {{ auth()->user()->name }}"
                       description="Ringkasan penjualan, grafik, dan peringatan stok akan tampil di sini.">
            <x-slot name="action">
                <a href="{{ route('pos.index') }}" class="btn btn-primary">
                    <x-icon name="shopping-cart" class="h-4 w-4" />
                    Buka Kasir
                </a>
            </x-slot>
        </x-empty-state>
    </x-card>
</x-app-layout>
