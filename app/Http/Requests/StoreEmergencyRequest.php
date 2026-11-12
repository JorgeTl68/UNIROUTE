<?php

namespace App\Http\Requests;

use App\Models\Incident;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmergencyRequest extends FormRequest
{
    /**
     * "¿Qué pasó?": se guarda como prefijo del mensaje de la incidencia.
     */
    public const array CATEGORIES = [
        'accident' => 'Accidente',
        'breakdown' => 'Falla de la unidad',
        'safety' => 'Seguridad o acoso',
        'health' => 'Salud',
        'other' => 'Otro',
    ];

    public function authorize(): bool
    {
        return $this->user()?->can('create', Incident::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'category' => ['required', Rule::in(array_keys(self::CATEGORIES))],
            'route_id' => ['nullable', 'integer', Rule::exists('routes', 'id')],
            'message' => ['required', 'string', 'min:10', 'max:1000'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'category.required' => 'Elige qué pasó.',
            'message.required' => 'Cuéntanos qué está pasando.',
            'message.min' => 'Describe la emergencia con un poco más de detalle.',
        ];
    }
}
