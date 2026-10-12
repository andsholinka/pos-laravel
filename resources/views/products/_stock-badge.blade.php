{{-- Stock pill for a product: red "Habis", amber "Sisa N" (at/below min stock) or neutral "N unit". Expects $product. --}}
@if ($product->stock <= 0)
    <x-badge color="red">Habis</x-badge>
@elseif ($product->is_low_stock)
    <x-badge color="amber">Sisa {{ $product->stock }} {{ $product->unit }}</x-badge>
@else
    <x-badge color="green">{{ $product->stock }} {{ $product->unit }}</x-badge>
@endif
