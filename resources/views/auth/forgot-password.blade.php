<x-guest-layout>
    <x-slot name="title">Lupa Kata Sandi</x-slot>

    <div class="mb-6">
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">Lupa kata sandi?</h1>
        <p class="mt-1 text-sm text-slate-600">
            Masukkan alamat email akun Anda. Kami akan mengirimkan tautan untuk membuat kata sandi baru.
        </p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" value="Email" />
            <x-text-input id="email" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <button type="submit" class="btn btn-primary w-full py-2.5">Kirim Tautan Reset</button>

        <p class="text-center text-sm">
            <a href="{{ route('login') }}" class="font-medium text-primary-700 hover:text-primary-800">Kembali ke halaman masuk</a>
        </p>
    </form>
</x-guest-layout>
