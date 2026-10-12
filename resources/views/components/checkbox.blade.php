{{--
    Checkbox with an optional label and hint.
    Props: label, hint, checked (bool). Other attributes (id, name, value, x-model...) go on the <input>.
--}}
@props(['label' => null, 'hint' => null, 'checked' => false])

@php($id = $attributes->get('id') ?? 'cb-'.\Illuminate\Support\Str::random(6))

<div class="flex items-start gap-3">
    <input type="checkbox"
           id="{{ $id }}"
           @checked($checked)
           {{ $attributes->except('id')->merge(['value' => '1', 'class' => 'mt-0.5 h-4 w-4 rounded border-slate-300 text-primary-600 focus:ring-primary-500']) }}>
    @if ($label)
        <div class="text-sm">
            <label for="{{ $id }}" class="font-medium text-slate-700">{{ $label }}</label>
            @if ($hint)
                <p class="text-slate-500">{{ $hint }}</p>
            @endif
        </div>
    @endif
</div>
