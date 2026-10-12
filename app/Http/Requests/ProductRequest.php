<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('admin');
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'remove_image' => $this->boolean('remove_image'),
        ]);
    }

    /**
     * Unique rules check soft-deleted products too (the DB unique index covers them).
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $product = $this->route('product');

        return [
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'sku' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9\-_.]+$/', Rule::unique('products', 'sku')->ignore($product)],
            'barcode' => ['nullable', 'string', 'max:50', Rule::unique('products', 'barcode')->ignore($product)],
            'name' => ['required', 'string', 'max:150'],
            'unit' => ['required', Rule::in(Product::UNITS)],
            'cost_price' => ['required', 'integer', 'min:0'],
            'price' => ['required', 'integer', 'min:0', 'gte:cost_price'],
            'min_stock' => ['required', 'integer', 'min:0', 'max:100000'],
            'initial_stock' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_image' => ['boolean'],
            'is_active' => ['boolean'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'price.gte' => 'Harga jual tidak boleh lebih kecil dari harga beli.',
            'sku.regex' => 'SKU hanya boleh berisi huruf, angka, tanda hubung, garis bawah, dan titik.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'category_id' => 'kategori',
            'sku' => 'SKU',
            'barcode' => 'barcode',
            'name' => 'nama produk',
            'unit' => 'satuan',
            'cost_price' => 'harga beli',
            'price' => 'harga jual',
            'min_stock' => 'stok minimum',
            'initial_stock' => 'stok awal',
            'image' => 'gambar',
            'remove_image' => 'hapus gambar',
            'is_active' => 'status aktif',
        ];
    }
}
