<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['income', 'expense'])],
            'date' => ['required', 'date'],
            'concept' => ['required', 'string', 'max:255'],
            'counterparty' => ['nullable', 'string', 'max:255'],
            'invoice_number' => ['nullable', 'string', 'max:50'],
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')->where('type', $this->input('type'))],
            'base_amount' => ['required', 'numeric', 'min:0'],
            'vat_rate' => ['required', 'numeric', 'min:0'],
            'irpf_rate' => ['required_if:type,income', 'numeric', 'min:0'],
            'deductible_pct' => ['required_if:type,expense', 'integer', 'min:0', 'max:100'],
            'payment_status' => ['required', Rule::in(['pending', 'paid'])],
            'invoice' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'notes' => ['nullable', 'string'],

            // Recurrencia (sección 8 de docs/spec.md): solo se valida el día
            // cuando la casilla viene marcada, para no rechazar el resto.
            'recurring' => ['sometimes', 'boolean'],
            'recurring_day' => $this->boolean('recurring')
                ? ['required', 'integer', 'min:1', 'max:31']
                : ['nullable'],
        ];
    }

    public function messages(): array
    {
        return [
            'invoice.mimes' => 'La factura debe ser un archivo PDF, JPG o PNG.',
            'invoice.max' => 'La factura no puede superar los 10 MB.',
        ];
    }
}
