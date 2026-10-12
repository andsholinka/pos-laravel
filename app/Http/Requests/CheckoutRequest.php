<?php

namespace App\Http\Requests;

use App\Enums\DiscountType;
use App\Enums\PaymentMethod;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Cart posted by the POS screen (JSON). Prices are NOT accepted here:
 * CheckoutService recomputes everything from the database.
 */
class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:9999'],
            'discount_type' => ['nullable', Rule::enum(DiscountType::class)],
            'discount_value' => [
                'nullable', 'numeric', 'min:0',
                Rule::when($this->input('discount_type') === DiscountType::Percent->value, 'max:100'),
            ],
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
            'paid_amount' => ['nullable', 'integer', 'min:0', 'required_if:payment_method,tunai'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'items' => 'keranjang',
            'items.*.product_id' => 'produk',
            'items.*.quantity' => 'jumlah',
            'discount_type' => 'jenis diskon',
            'discount_value' => 'nilai diskon',
            'payment_method' => 'metode pembayaran',
            'paid_amount' => 'uang diterima',
            'note' => 'catatan',
        ];
    }
}
