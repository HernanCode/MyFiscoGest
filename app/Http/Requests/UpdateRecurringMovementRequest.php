<?php

namespace App\Http\Requests;

use App\Models\RecurringMovement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRecurringMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var RecurringMovement $recurringMovement */
        $recurringMovement = $this->route('recurringMovement');

        return [
            'concept' => ['required', 'string', 'max:255'],
            'counterparty' => ['nullable', 'string', 'max:255'],
            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->where('type', $recurringMovement->type),
            ],
            'base_amount' => ['required', 'numeric', 'min:0'],
            'vat_rate' => ['required', 'numeric', 'min:0'],
            'irpf_rate' => [$recurringMovement->type === 'income' ? 'required' : 'nullable', 'numeric', 'min:0'],
            'deductible_pct' => [$recurringMovement->type === 'expense' ? 'required' : 'nullable', 'integer', 'min:0', 'max:100'],
            'day_of_month' => ['required', 'integer', 'min:1', 'max:31'],
            'ends_on' => ['nullable', 'date'],
            'active' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
