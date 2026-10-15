<?php

namespace App\Http\Requests;

use App\Models\Movement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Movement $movement */
        $movement = $this->route('movement');
        $type = $movement->type;

        $dateRules = ['required', 'date'];

        // Un movimiento generado no puede pisar la fecha de otro de su misma
        // recurrencia: si no, el índice único (recurring_movement_id, date)
        // rechazaría el guardado con un error interno. Se compara con
        // whereDate porque la columna almacena fecha y hora.
        if ($movement->recurring_movement_id !== null) {
            $dateRules[] = function (string $attribute, mixed $value, \Closure $fail) use ($movement): void {
                $collision = Movement::where('recurring_movement_id', $movement->recurring_movement_id)
                    ->whereDate('date', $value)
                    ->whereKeyNot($movement->id)
                    ->exists();

                if ($collision) {
                    $fail('Ya existe un movimiento generado con esa fecha en esta recurrencia.');
                }
            };
        }

        return [
            'date' => $dateRules,
            'concept' => ['required', 'string', 'max:255'],
            'counterparty' => ['nullable', 'string', 'max:255'],
            'invoice_number' => ['nullable', 'string', 'max:50'],
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')->where('type', $type)],
            'base_amount' => ['required', 'numeric', 'min:0'],
            'vat_rate' => ['required', 'numeric', 'min:0'],
            'irpf_rate' => [$type === 'income' ? 'required' : 'nullable', 'numeric', 'min:0'],
            'deductible_pct' => [$type === 'expense' ? 'required' : 'nullable', 'integer', 'min:0', 'max:100'],
            'payment_status' => ['required', Rule::in(['pending', 'paid'])],
            'invoice' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'notes' => ['nullable', 'string'],

            // Convertir en recurrente al editar (sección 8 de docs/spec.md).
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
