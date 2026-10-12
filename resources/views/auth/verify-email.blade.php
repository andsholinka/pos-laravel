<x-guest-layout>
    <x-slot name="title">Verifikasi Email</x-slot>

    <div class="mb-6">
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">Verifikasi email Anda</h1>
        <p class="mt-1 text-sm text-slate-600">
            Kami telah mengirimkan tautan verifikasi ke email Anda. Klik tautan tersebut untuk melanjutkan.
            Jika belum menerima email, kami bisa mengirimkannya lagi.
        </p>
    </div>

    @if (session('status') == 'verification-link-sent')
        <x-auth-session-status class="mb-4" status="Tautan verifikasi baru telah dikirim ke alamat email Anda." />
    @endif

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="btn btn-primary w-full sm:w-auto">Kirim Ulang Email Verifikasi</button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-ghost w-full sm:w-auto">Keluar</button>
        </form>
    </div>
</x-guest-layout>
