<x-app-layout title="Edit Pengguna" subtitle="{{ $user->name }} · {{ $user->email }}">
    <form method="POST" action="{{ route('users.update', $user) }}">
        @csrf
        @method('PUT')
        @include('users._form')
        <div class="mt-6 flex justify-end gap-2">
            <a href="{{ route('users.index') }}" class="btn btn-secondary">Batal</a>
            <x-primary-button>
                <x-icon name="check" class="h-4 w-4" />
                Simpan Perubahan
            </x-primary-button>
        </div>
    </form>
</x-app-layout>
