<?php

namespace App\Http\Requests\Admin;

use App\Enums\IncidentSeverity;
use App\Enums\IncidentType;
use App\Models\Incident;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIncidentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Incident::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(IncidentType::class)->except([IncidentType::Emergency])],
            'severity' => ['required', Rule::enum(IncidentSeverity::class)],
            'route_id' => ['required', 'integer', Rule::exists('routes', 'id')],
            'vehicle_id' => ['nullable', 'integer', Rule::exists('vehicles', 'id')],
            'message' => ['required', 'string', 'min:5', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'route_id.required' => 'Elige la ruta afectada.',
            'message.required' => 'Describe la incidencia.',
        ];
    }
}
