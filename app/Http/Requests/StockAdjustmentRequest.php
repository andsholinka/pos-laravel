<?php

namespace App\Http\Requests;

use App\Models\StockMovement;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StockAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('admin');
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', Rule::exists('products', 'id')->whereNull('deleted_at')],
            'actual_stock' => ['required', 'integer', 'min:0', 'max:100000'],
            'reason' => ['required', Rule::in(array_keys(StockMovement::ADJUSTMENT_REASONS))],
            'note' => ['nullable', 'required_if:reason,lainnya', 'string', 'max:255'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'note.required_if' => 'Catatan wajib diisi jika alasan "Lainnya".',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'product_id' => 'produk',
            'actual_stock' => 'stok fisik',
            'reason' => 'alasan',
            'note' => 'catatan',
        ];
    }
}
