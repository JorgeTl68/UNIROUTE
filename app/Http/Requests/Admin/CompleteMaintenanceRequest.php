<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CompleteMaintenanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'resolution' => ['required', 'string', 'min:10', 'max:3000'],
            'cost' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99', 'decimal:0,2'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'resolution.required' => 'Describe la reparación realizada.',
            'resolution.min' => 'Escribe al menos 10 caracteres sobre la reparación.',
            'resolution.max' => 'La resolución no puede superar 3000 caracteres.',
            'cost.numeric' => 'El costo debe ser un número.',
            'cost.min' => 'El costo no puede ser negativo.',
            'cost.max' => 'El costo supera el máximo permitido.',
            'cost.decimal' => 'Usa como máximo dos decimales para el costo.',
        ];
    }
}
