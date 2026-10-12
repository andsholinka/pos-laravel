<x-app-layout title="Edit Kategori" subtitle="Perbarui nama, deskripsi, atau warna kategori.">
    <form method="POST" action="{{ route('categories.update', $category) }}" class="max-w-3xl">
        @csrf
        @method('PUT')
        <x-card>
            @include('categories._form')
        </x-card>
        <div class="mt-6 flex justify-end gap-2">
            <a href="{{ route('categories.index') }}" class="btn btn-secondary">Batal</a>
            <x-primary-button>
                <x-icon name="check" class="h-4 w-4" />
                Simpan Perubahan
            </x-primary-button>
        </div>
    </form>
</x-app-layout>
