<x-app-layout title="Tambah Kategori" subtitle="Buat kategori baru untuk mengelompokkan produk.">
    <form method="POST" action="{{ route('categories.store') }}" class="max-w-3xl">
        @csrf
        <x-card>
            @include('categories._form')
        </x-card>
        <div class="mt-6 flex justify-end gap-2">
            <a href="{{ route('categories.index') }}" class="btn btn-secondary">Batal</a>
            <x-primary-button>
                <x-icon name="check" class="h-4 w-4" />
                Simpan Kategori
            </x-primary-button>
        </div>
    </form>
</x-app-layout>
