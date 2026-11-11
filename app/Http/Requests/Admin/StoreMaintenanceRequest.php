<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMaintenanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'vehicle_id' => ['required', 'integer', 'exists:vehicles,id'],
            'severity' => ['required', Rule::in(['minor', 'major', 'critical'])],
            'workshop' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'min:10', 'max:3000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'vehicle_id.required' => 'Selecciona una unidad.',
            'vehicle_id.exists' => 'La unidad seleccionada no existe.',
            'severity.in' => 'Selecciona una prioridad válida.',
            'required' => 'Completa el campo :attribute.',
            'description.min' => 'Describe la falla con al menos 10 caracteres.',
            'description.max' => 'La descripción no puede superar 3000 caracteres.',
            'workshop.max' => 'El nombre del taller no puede superar 255 caracteres.',
        ];
    }
}
