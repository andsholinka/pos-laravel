<x-app-layout title="Tambah Pengguna" subtitle="Buat akun untuk admin atau kasir baru.">
    <form method="POST" action="{{ route('users.store') }}">
        @csrf
        @include('users._form')
        <div class="mt-6 flex justify-end gap-2">
            <a href="{{ route('users.index') }}" class="btn btn-secondary">Batal</a>
            <x-primary-button>
                <x-icon name="check" class="h-4 w-4" />
                Simpan Pengguna
            </x-primary-button>
        </div>
    </form>
</x-app-layout>
