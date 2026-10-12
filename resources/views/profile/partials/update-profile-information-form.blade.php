<section>
    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="space-y-5">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" value="Nama" />
            <x-text-input id="name" name="name" type="text" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" value="Email" />
            <x-text-input id="email" name="email" type="email" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div class="mt-2 rounded-lg bg-amber-50 p-3 text-sm text-amber-800">
                    <p>
                        Alamat email Anda belum diverifikasi.
                        <button form="send-verification" class="font-semibold underline hover:text-amber-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500">
                            Kirim ulang email verifikasi.
                        </button>
                    </p>
                </div>
            @endif
        </div>

        <div class="flex items-center justify-between gap-4 rounded-lg bg-slate-50 px-4 py-3 text-sm">
            <span class="text-slate-600">Peran</span>
            <x-badge :color="$user->role->color()">{{ $user->role->label() }}</x-badge>
        </div>

        <div class="flex justify-end">
            <x-primary-button>
                <x-icon name="check" class="h-4 w-4" />
                Simpan
            </x-primary-button>
        </div>
    </form>
</section>
