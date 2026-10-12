@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'flex items-start gap-2 rounded-lg bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-800 ring-1 ring-inset ring-emerald-600/20']) }} role="status">
        <x-icon name="check-circle" class="h-5 w-5 text-emerald-600" />
        <span>{{ $status }}</span>
    </div>
@endif
