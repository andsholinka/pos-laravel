<x-app-layout title="Tambah Produk" subtitle="Lengkapi data produk agar bisa dijual di kasir.">
    <form method="POST" action="{{ route('products.store') }}" enctype="multipart/form-data">
        @csrf
        @include('products._form')
        <div class="mt-6 flex justify-end gap-2">
            <a href="{{ route('products.index') }}" class="btn btn-secondary">Batal</a>
            <x-primary-button>
                <x-icon name="check" class="h-4 w-4" />
                Simpan Produk
            </x-primary-button>
        </div>
    </form>
</x-app-layout>
