{{--
    Summary metric tile.
    Props: label, value, icon (x-icon name), color (emerald|sky|amber|rose),
           hint (small text), trend (float % vs previous period, null = hidden), href (makes it a link).
--}}
@props(['label', 'value', 'icon' => 'chart-bar', 'color' => 'emerald', 'hint' => null, 'trend' => null, 'href' => null])

@php
    $iconClasses = match ($color) {
        'sky' => 'bg-sky-100 text-sky-700',
        'amber' => 'bg-amber-100 text-amber-700',
        'rose' => 'bg-rose-100 text-rose-700',
        default => 'bg-emerald-100 text-emerald-700',
    };
    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }} @if ($href) href="{{ $href }}" @endif
    {{ $attributes->merge(['class' => 'card block p-5'.($href ? ' transition hover:ring-primary-300 hover:shadow-md focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500' : '')]) }}>
    <div class="flex items-start justify-between gap-4">
        <div class="min-w-0">
            <p class="text-sm font-medium text-slate-600">{{ $label }}</p>
            <p class="mt-2 truncate text-2xl font-bold tracking-tight text-slate-900">{{ $value }}</p>
        </div>
        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl {{ $iconClasses }}">
            <x-icon :name="$icon" class="h-6 w-6" />
        </span>
    </div>

    @if ($trend !== null || $hint)
        <div class="mt-3 flex flex-wrap items-center gap-2 text-xs">
            @if ($trend !== null)
                @if ($trend >= 0)
                    <span class="inline-flex items-center gap-1 font-semibold text-emerald-700">
                        <x-icon name="arrow-trending-up" class="h-4 w-4" />
                        <span><span class="sr-only">Naik </span>{{ percent(abs($trend)) }}</span>
                    </span>
                @else
                    <span class="inline-flex items-center gap-1 font-semibold text-rose-700">
                        <x-icon name="arrow-trending-down" class="h-4 w-4" />
                        <span><span class="sr-only">Turun </span>{{ percent(abs($trend)) }}</span>
                    </span>
                @endif
            @endif
            @if ($hint)
                <span class="text-slate-500">{{ $hint }}</span>
            @endif
        </div>
    @endif
</{{ $tag }}>
