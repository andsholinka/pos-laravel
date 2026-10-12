<x-guest-layout>
    <x-slot name="title">Konfirmasi Kata Sandi</x-slot>

    <div class="mb-6">
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">Konfirmasi kata sandi</h1>
        <p class="mt-1 text-sm text-slate-600">
            Ini adalah area aman aplikasi. Silakan konfirmasi kata sandi Anda sebelum melanjutkan.
        </p>
    </div>

    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-5">
        @csrf

        <!-- Password -->
        <div>
            <x-input-label for="password" value="Kata Sandi" />
            <x-text-input id="password" type="password" name="password" required autofocus autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <button type="submit" class="btn btn-primary w-full py-2.5">Konfirmasi</button>
    </form>
</x-guest-layout>
