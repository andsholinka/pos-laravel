<x-guest-layout>
    <x-slot name="title">Masuk</x-slot>

    <div x-data="{
            email: @js(old('email', '')),
            password: '',
            fill(email) {
                this.email = email;
                this.password = 'password';
                this.$nextTick(() => this.$refs.submit.focus());
            },
        }">
        <div class="mb-6">
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Selamat datang kembali</h1>
            <p class="mt-1 text-sm text-slate-600">Masuk untuk mulai berjualan di {{ $store->store_name }}.</p>
        </div>

        <!-- Session Status -->
        <x-auth-session-status class="mb-4" :status="session('status')" />

        @if (session('error'))
            <div class="mb-4 flex items-start gap-2 rounded-lg bg-rose-50 px-3 py-2 text-sm font-medium text-rose-800 ring-1 ring-inset ring-rose-600/20" role="alert">
                <x-icon name="exclamation-triangle" class="h-5 w-5 text-rose-600" />
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="space-y-5">
            @csrf

            <!-- Email Address -->
            <div>
                <x-input-label for="email" value="Email" />
                <x-text-input id="email" type="email" name="email" x-model="email" :value="old('email')"
                              required autofocus autocomplete="username" placeholder="nama@toko.com" />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <!-- Password -->
            <div>
                <div class="flex items-center justify-between">
                    <x-input-label for="password" value="Kata Sandi" />
                    @if (Route::has('password.request'))
                        <a class="mb-1 rounded text-sm font-medium text-primary-700 hover:text-primary-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500" href="{{ route('password.request') }}">
                            Lupa kata sandi?
                        </a>
                    @endif
                </div>
                <x-text-input id="password" type="password" name="password" x-model="password"
                              required autocomplete="current-password" placeholder="••••••••" />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <!-- Remember Me -->
            <x-checkbox id="remember_me" name="remember" label="Ingat saya" />

            <button type="submit" class="btn btn-primary w-full py-2.5" x-ref="submit">
                Masuk
            </button>
        </form>

        @if (config('pos.show_demo_accounts'))
            <div class="mt-8 rounded-xl border border-dashed border-primary-300 bg-primary-50/60 p-4">
                <div class="flex items-center gap-2">
                    <x-icon name="key" class="h-5 w-5 text-primary-700" />
                    <h2 class="text-sm font-semibold text-primary-900">Akun Demo</h2>
                </div>
                <p class="mt-1 text-xs text-slate-600">Klik salah satu akun untuk mengisi form secara otomatis (kata sandi: <span class="font-mono font-semibold">password</span>).</p>
                <div class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-2">
                    <button type="button" class="btn btn-secondary justify-start text-left" @click="fill('admin@pos.test')">
                        <x-icon name="shield-check" class="h-5 w-5 text-violet-600" />
                        <span class="min-w-0">
                            <span class="block text-sm">Admin</span>
                            <span class="block truncate text-xs font-normal text-slate-500">admin@pos.test</span>
                        </span>
                    </button>
                    <button type="button" class="btn btn-secondary justify-start text-left" @click="fill('kasir@pos.test')">
                        <x-icon name="shopping-cart" class="h-5 w-5 text-sky-600" />
                        <span class="min-w-0">
                            <span class="block text-sm">Kasir</span>
                            <span class="block truncate text-xs font-normal text-slate-500">kasir@pos.test</span>
                        </span>
                    </button>
                </div>
            </div>
        @endif
    </div>
</x-guest-layout>
