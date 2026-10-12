{{--
    Toast for session flashes: success / error / status.
    Breeze status keys (profile-updated, ...) are mapped to Indonesian messages.
--}}
@php
    $statusMessages = [
        'profile-updated' => 'Profil berhasil diperbarui.',
        'password-updated' => 'Kata sandi berhasil diubah.',
        'verification-link-sent' => 'Tautan verifikasi baru telah dikirim ke email Anda.',
    ];

    $toasts = collect([
        ['type' => 'success', 'message' => session('success')],
        ['type' => 'error', 'message' => session('error')],
        ['type' => 'success', 'message' => session('status') ? ($statusMessages[session('status')] ?? session('status')) : null],
    ])->filter(fn ($t) => filled($t['message']))->values();
@endphp

@if ($toasts->isNotEmpty())
    <div class="pointer-events-none fixed inset-x-0 top-4 z-[60] flex flex-col items-center gap-2 px-4 sm:items-end sm:px-6"
         role="status" aria-live="polite">
        @foreach ($toasts as $toast)
            <div x-data="{ show: true }"
                 x-init="setTimeout(() => show = false, 4000)"
                 x-show="show"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-2"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @class([
                     'pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-xl bg-white p-4 shadow-lg ring-1',
                     'ring-emerald-200' => $toast['type'] === 'success',
                     'ring-rose-200' => $toast['type'] === 'error',
                 ])>
                @if ($toast['type'] === 'success')
                    <x-icon name="check-circle" class="h-6 w-6 text-emerald-600" />
                @else
                    <x-icon name="exclamation-triangle" class="h-6 w-6 text-rose-600" />
                @endif
                <p class="flex-1 pt-0.5 text-sm font-medium text-slate-800">
                    <span class="sr-only">{{ $toast['type'] === 'success' ? 'Berhasil:' : 'Gagal:' }}</span>
                    {{ $toast['message'] }}
                </p>
                <button type="button" @click="show = false" class="rounded-md p-1 text-slate-400 hover:text-slate-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">
                    <span class="sr-only">Tutup notifikasi</span>
                    <x-icon name="x-mark" class="h-4 w-4" />
                </button>
            </div>
        @endforeach
    </div>
@endif
