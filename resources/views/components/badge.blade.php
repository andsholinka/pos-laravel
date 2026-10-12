{{-- Status pill. Always carries text, so color is never the only signal. --}}
@props(['color' => 'slate'])

@php
    // Literal class strings so Tailwind keeps them (see plan D15).
    $classes = match ($color) {
        'green', 'emerald' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
        'red', 'rose' => 'bg-rose-50 text-rose-700 ring-rose-600/20',
        'amber' => 'bg-amber-50 text-amber-800 ring-amber-600/20',
        'blue' => 'bg-blue-50 text-blue-700 ring-blue-600/20',
        'violet' => 'bg-violet-50 text-violet-700 ring-violet-600/20',
        'sky' => 'bg-sky-50 text-sky-700 ring-sky-600/20',
        default => 'bg-slate-100 text-slate-700 ring-slate-500/20',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1 whitespace-nowrap rounded-full px-2 py-0.5 text-xs font-semibold ring-1 ring-inset {$classes}"]) }}>
    {{ $slot }}
</span>
