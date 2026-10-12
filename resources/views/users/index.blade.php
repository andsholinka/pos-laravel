<x-app-layout title="Pengguna" subtitle="Kelola akun admin dan kasir yang bisa masuk ke aplikasi.">
    <x-slot name="actions">
        <a href="{{ route('users.create') }}" class="btn btn-primary">
            <x-icon name="plus" class="h-4 w-4" />
            Tambah Pengguna
        </a>
    </x-slot>

    <x-filter-bar :action="route('users.index')">
        <div class="sm:col-span-2">
            <x-input-label for="q" value="Cari pengguna" />
            <x-text-input id="q" name="q" type="search" :value="$filters['q']" placeholder="Nama atau email" />
        </div>
        <div>
            <x-input-label for="role" value="Peran" />
            <x-select-input id="role" name="role">
                <option value="">Semua peran</option>
                @foreach ($roles as $role)
                    <option value="{{ $role->value }}" @selected($filters['role'] === $role->value)>{{ $role->label() }}</option>
                @endforeach
            </x-select-input>
        </div>
    </x-filter-bar>

    <x-card :padding="false">
        @if ($users->isEmpty())
            <x-empty-state icon="users" title="Pengguna tidak ditemukan" description="Coba kata kunci atau filter lain." />
        @else
            <div class="overflow-x-auto">
                <table class="table-base">
                    <thead>
                        <tr>
                            <th scope="col">Pengguna</th>
                            <th scope="col">Peran</th>
                            <th scope="col">Status</th>
                            <th scope="col" class="text-right">Transaksi</th>
                            <th scope="col">Login Terakhir</th>
                            <th scope="col" class="text-right"><span class="sr-only">Aksi</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $u)
                            @php($isSelf = auth()->user()->is($u))
                            <tr>
                                <td>
                                    <div class="flex items-center gap-3">
                                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary-100 text-xs font-bold text-primary-700" aria-hidden="true">
                                            {{ \Illuminate\Support\Str::upper(mb_substr($u->name, 0, 1)) }}
                                        </span>
                                        <div class="min-w-0">
                                            <p class="truncate font-semibold text-slate-900">
                                                {{ $u->name }}
                                                @if ($isSelf)
                                                    <span class="ml-1 text-xs font-medium text-slate-500">(Anda)</span>
                                                @endif
                                            </p>
                                            <p class="truncate text-xs text-slate-500">{{ $u->email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td><x-badge :color="$u->role->color()">{{ $u->role->label() }}</x-badge></td>
                                <td>
                                    @if ($u->is_active)
                                        <x-badge color="green">Aktif</x-badge>
                                    @else
                                        <x-badge color="red">Nonaktif</x-badge>
                                    @endif
                                </td>
                                <td class="text-right tabular-nums">{{ number_format($u->sales_count, 0, ',', '.') }}</td>
                                <td class="whitespace-nowrap text-slate-600">{{ $u->last_login_at?->translatedFormat('d M Y H:i') ?? 'Belum pernah' }}</td>
                                <td>
                                    <div class="flex items-center justify-end gap-1">
                                        @unless ($isSelf)
                                            <form method="POST" action="{{ route('users.toggle-active', $u) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-ghost btn-sm {{ $u->is_active ? 'text-amber-700 hover:bg-amber-50' : 'text-emerald-700 hover:bg-emerald-50' }}">
                                                    <x-icon :name="$u->is_active ? 'no-symbol' : 'check-circle'" class="h-4 w-4" />
                                                    {{ $u->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                                    <span class="sr-only">{{ $u->name }}</span>
                                                </button>
                                            </form>
                                        @endunless
                                        <a href="{{ route('users.edit', $u) }}" class="btn btn-ghost btn-icon" title="Edit">
                                            <x-icon name="pencil-square" class="h-5 w-5" />
                                            <span class="sr-only">Edit {{ $u->name }}</span>
                                        </a>
                                        @if (! $isSelf && $u->sales_count === 0)
                                            <x-confirm-delete :action="route('users.destroy', $u)"
                                                              icon-only
                                                              title="Hapus pengguna {{ $u->name }}?"
                                                              message="Akun ini akan dihapus permanen dan tidak bisa masuk lagi."
                                                              label="Hapus {{ $u->name }}" />
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($users->hasPages())
                <div class="border-t border-slate-100 px-4 py-3">{{ $users->links() }}</div>
            @endif
        @endif
    </x-card>
</x-app-layout>
