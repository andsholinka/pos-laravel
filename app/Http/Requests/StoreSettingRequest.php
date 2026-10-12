<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('admin');
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['remove_logo' => $this->boolean('remove_logo')]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'store_name' => ['required', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'tax_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'receipt_footer' => ['nullable', 'string', 'max:255'],
            'receipt_paper' => ['required', 'in:58,80'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:1024'],
            'remove_logo' => ['boolean'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'store_name' => 'nama toko',
            'address' => 'alamat',
            'phone' => 'nomor telepon',
            'tax_percent' => 'pajak',
            'receipt_footer' => 'catatan kaki struk',
            'receipt_paper' => 'ukuran kertas struk',
            'logo' => 'logo',
            'remove_logo' => 'hapus logo',
        ];
    }
}
