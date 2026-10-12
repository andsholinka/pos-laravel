<x-app-layout title="Profil Saya" subtitle="Kelola nama, email, dan kata sandi akun Anda.">
    <div class="grid max-w-5xl grid-cols-1 gap-6 xl:grid-cols-2">
        <x-card title="Informasi Profil" subtitle="Perbarui nama dan alamat email akun Anda.">
            @include('profile.partials.update-profile-information-form')
        </x-card>

        <x-card title="Ubah Kata Sandi" subtitle="Gunakan kata sandi yang panjang dan acak agar akun tetap aman.">
            @include('profile.partials.update-password-form')
        </x-card>
    </div>
</x-app-layout>
