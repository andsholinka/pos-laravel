{{-- <select> styled like the other form controls. Options go in the slot. --}}
@props(['disabled' => false])

<select @disabled($disabled) {{ $attributes->merge(['class' => 'form-select']) }}>
    {{ $slot }}
</select>
