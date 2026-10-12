<x-app-layout title="Edit Produk" subtitle="{{ $product->name }} · {{ $product->sku }}">
    <x-slot name="actions">
        <a href="{{ route('products.show', $product) }}" class="btn btn-secondary">
            <x-icon name="eye" class="h-4 w-4" />
            Lihat Detail
        </a>
        <x-confirm-delete :action="route('products.destroy', $product)"
                          title="Hapus produk {{ $product->name }}?"
                          message="Produk tidak lagi tampil di kasir. Riwayat penjualan dan stok tetap tersimpan." />
    </x-slot>

    <form method="POST" action="{{ route('products.update', $product) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        @include('products._form')
        <div class="mt-6 flex justify-end gap-2">
            <a href="{{ route('products.show', $product) }}" class="btn btn-secondary">Batal</a>
            <x-primary-button>
                <x-icon name="check" class="h-4 w-4" />
                Simpan Perubahan
            </x-primary-button>
        </div>
    </form>
</x-app-layout>
