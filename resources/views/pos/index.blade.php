{{-- Placeholder: the full POS screen (product grid, cart, payment) arrives in FEAT-003. --}}
<x-pos-layout>
    <div class="flex h-full items-center justify-center p-6">
        <x-card class="w-full max-w-lg">
            <x-empty-state icon="shopping-cart"
                           title="Layar kasir sedang disiapkan"
                           description="Halo {{ auth()->user()->name }}, fitur transaksi akan segera tersedia di halaman ini." />
        </x-card>
    </div>
</x-pos-layout>
