<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class VoidSaleRequest extends FormRequest
{
    /** Admin only, and only for sales that are not already void (SalePolicy::void). */
    public function authorize(): bool
    {
        return $this->user()->can('void', $this->route('sale'));
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
