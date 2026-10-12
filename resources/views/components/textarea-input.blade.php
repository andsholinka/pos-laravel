{{-- <textarea> styled like the other form controls. Pass the value via :value or the slot. --}}
@props(['disabled' => false, 'value' => null, 'rows' => 3])

<textarea rows="{{ $rows }}" @disabled($disabled) {{ $attributes->merge(['class' => 'form-textarea']) }}>{{ $value ?? $slot }}</textarea>
