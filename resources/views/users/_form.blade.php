{{-- Shared create/edit fields. Expects $user (App\Models\User) and $roles (Role[]). --}}
@php
    $editing = $user->exists;
    $isSelf = $editing && auth()->user()->is($user);
    $selectedRole = old('role', $user->role?->value);
@endphp
<div class="grid gap-6 lg:grid-cols-3">
    <x-card title="Data Pengguna" class="lg:col-span-2">
        <div class="grid gap-5 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <x-input-label for="name" value="Nama Lengkap" />
                <x-text-input id="name" name="name" type="text" :value="old('name', $user->name)" required maxlength="100" autofocus autocomplete="off" />
                <x-input-error class="mt-2" :messages="$errors->get('name')" />
            </div>
            <div class="sm:col-span-2">
                <x-input-label for="email" value="Email" />
                <x-text-input id="email" name="email" type="email" :value="old('email', $user->email)" required maxlength="150" autocomplete="off" placeholder="nama@toko.com" />
                <x-input-error class="mt-2" :messages="$errors->get('email')" />
            </div>
            <div>
                <x-input-label for="password" value="{{ $editing ? 'Kata Sandi Baru' : 'Kata Sandi' }}" />
                <x-text-input id="password" name="password" type="password" :required="! $editing" autocomplete="new-password" aria-describedby="password-hint" />
                <p id="password-hint" class="form-hint">{{ $editing ? 'Kosongkan jika tidak ingin mengganti kata sandi.' : 'Minimal 8 karakter.' }}</p>
                <x-input-error class="mt-2" :messages="$errors->get('password')" />
            </div>
            <div>
                <x-input-label for="password_confirmation" value="Konfirmasi Kata Sandi" />
                <x-text-input id="password_confirmation" name="password_confirmation" type="password" :required="! $editing" autocomplete="new-password" />
            </div>
        </div>
    </x-card>

    <x-card title="Peran & Status">
        <fieldset>
            <legend class="form-label">Peran</legend>
            <div class="mt-2 space-y-2">
                @foreach ($roles as $role)
                    <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 p-3 text-sm transition hover:bg-slate-50 has-[:checked]:border-primary-500 has-[:checked]:bg-primary-50 has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-primary-500">
                        <input type="radio" name="role" value="{{ $role->value }}" class="mt-0.5 h-4 w-4 border-slate-300 text-primary-600 focus:ring-primary-500" @checked($selectedRole === $role->value)>
                        <span>
                            <span class="block font-semibold text-slate-900">{{ $role->label() }}</span>
                            <span class="block text-slate-600">{{ $role === \App\Enums\Role::Admin ? 'Akses penuh: produk, stok, laporan, pengguna, pengaturan.' : 'Kasir, riwayat transaksi sendiri, dan daftar produk.' }}</span>
                        </span>
                    </label>
                @endforeach
            </div>
            <x-input-error class="mt-2" :messages="$errors->get('role')" />
        </fieldset>

        <div class="mt-5 border-t border-slate-100 pt-5">
            <x-checkbox id="is_active" name="is_active" label="Akun aktif" hint="Akun nonaktif tidak bisa masuk ke aplikasi."
                        :checked="(bool) old('is_active', $user->is_active ?? true)" />
            <x-input-error class="mt-2" :messages="$errors->get('is_active')" />
        </div>

        @if ($isSelf)
            <p class="mt-4 rounded-lg bg-sky-50 p-3 text-xs text-sky-800">Ini akun Anda sendiri. Anda tidak dapat menurunkan peran atau menonaktifkannya.</p>
        @endif
    </x-card>
</div>
