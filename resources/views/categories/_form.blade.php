{{-- Shared create/edit fields. Expects $category (App\Models\Category). --}}
@php($selectedColor = old('color', $category->color ?? 'emerald'))
<div class="space-y-5">
    <div>
        <x-input-label for="name" value="Nama Kategori" />
        <x-text-input id="name" name="name" type="text" :value="old('name', $category->name)" required maxlength="100" autofocus placeholder="Contoh: Minuman" />
        <x-input-error class="mt-2" :messages="$errors->get('name')" />
    </div>

    <div>
        <x-input-label for="description" value="Deskripsi (opsional)" />
        <x-textarea-input id="description" name="description" rows="3" maxlength="255" :value="old('description', $category->description)" placeholder="Keterangan singkat kategori" />
        <x-input-error class="mt-2" :messages="$errors->get('description')" />
    </div>

    <fieldset>
        <legend class="form-label">Warna Label</legend>
        <p class="form-hint -mt-0.5 mb-3">Dipakai untuk badge kategori dan ikon produk tanpa gambar.</p>
        <div class="grid grid-cols-2 gap-2 sm:grid-cols-5">
            @foreach (\App\Models\Category::COLORS as $key => $c)
                <label class="relative flex cursor-pointer items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm transition hover:bg-slate-50 has-[:checked]:border-primary-500 has-[:checked]:bg-primary-50 has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-primary-500">
                    <input type="radio" name="color" value="{{ $key }}" class="sr-only" @checked($selectedColor === $key)>
                    <span class="h-5 w-5 shrink-0 rounded-full {{ $c['solid'] }} ring-2 ring-white ring-offset-1 ring-offset-slate-200" aria-hidden="true"></span>
                    <span class="truncate font-medium text-slate-700">{{ $c['label'] }}</span>
                </label>
            @endforeach
        </div>
        <x-input-error class="mt-2" :messages="$errors->get('color')" />
    </fieldset>
</div>
