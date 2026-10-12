{{--
    GET filter form with a responsive grid and Terapkan/Reset buttons. Put labelled fields in the slot.
    Props: action (defaults to the current URL), reset (URL, defaults to action).
--}}
@props(['action' => null, 'reset' => null])

@php
    $action ??= url()->current();
    $reset ??= $action;
@endphp

<form method="GET" action="{{ $action }}" role="search" {{ $attributes->merge(['class' => 'card mb-6 p-4']) }}>
    <div class="grid grid-cols-1 items-end gap-4 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5">
        {{ $slot }}

        <div class="flex gap-2">
            <button type="submit" class="btn btn-primary flex-1 sm:flex-none">
                <x-icon name="magnifying-glass" class="h-4 w-4" />
                Terapkan
            </button>
            <a href="{{ $reset }}" class="btn btn-secondary">Reset</a>
        </div>
    </div>
</form>
