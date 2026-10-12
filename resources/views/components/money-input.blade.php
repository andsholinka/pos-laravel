{{-- Whole-Rupiah number input with an "Rp" prefix. Attributes go on the <input>. --}}
@props(['disabled' => false])

<div class="relative">
    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm font-medium text-slate-500" aria-hidden="true">Rp</span>
    <input type="number"
           inputmode="numeric"
           min="0"
           step="1"
           @disabled($disabled)
           {{ $attributes->merge(['class' => 'form-input pl-10 tabular-nums']) }}>
</div>
