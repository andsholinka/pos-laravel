{{--
    Delete button + confirmation modal (Breeze x-modal) posting DELETE to $action.
    Props: action (URL), title, message, label (button text), icon (bool: icon-only trigger).
--}}
@props([
    'action',
    'title' => 'Hapus data?',
    'message' => 'Data yang dihapus tidak dapat dikembalikan.',
    'label' => 'Hapus',
    'iconOnly' => false,
])

@php($modalName = 'confirm-delete-'.md5($action))

@if ($iconOnly)
    <button type="button"
            x-data
            @click.prevent="$dispatch('open-modal', '{{ $modalName }}')"
            {{ $attributes->merge(['class' => 'btn btn-ghost btn-icon text-rose-600 hover:bg-rose-50 hover:text-rose-700']) }}>
        <x-icon name="trash" class="h-5 w-5" />
        <span class="sr-only">{{ $label }}</span>
    </button>
@else
    <button type="button"
            x-data
            @click.prevent="$dispatch('open-modal', '{{ $modalName }}')"
            {{ $attributes->merge(['class' => 'btn btn-danger']) }}>
        <x-icon name="trash" class="h-4 w-4" />
        {{ $label }}
    </button>
@endif

<x-modal :name="$modalName" maxWidth="md" focusable>
    <form method="POST" action="{{ $action }}" class="p-6">
        @csrf
        @method('DELETE')

        <div class="flex items-start gap-4">
            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-rose-100 text-rose-600">
                <x-icon name="exclamation-triangle" class="h-6 w-6" />
            </span>
            <div>
                <h2 class="text-lg font-semibold text-slate-900">{{ $title }}</h2>
                <p class="mt-1 text-sm text-slate-600">{{ $message }}</p>
            </div>
        </div>

        <div class="mt-6 flex justify-end gap-2">
            <x-secondary-button x-on:click="$dispatch('close')">Batal</x-secondary-button>
            <x-danger-button>{{ $label }}</x-danger-button>
        </div>
    </form>
</x-modal>
