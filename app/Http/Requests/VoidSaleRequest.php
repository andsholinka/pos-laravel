<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class VoidSaleRequest extends FormRequest
{
    /**
     * Admin only. An already-void sale is not a permission problem: SaleController::void
     * reports it with CheckoutService's "Transaksi sudah dibatalkan." message instead of a 403.
     */
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
            'reason' => ['required', 'string', 'min:5', 'max:255'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'reason' => 'alasan pembatalan',
        ];
    }
}
