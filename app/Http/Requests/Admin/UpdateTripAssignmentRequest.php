<?php

namespace App\Http\Requests\Admin;

use App\Models\Trip;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTripAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $trip = $this->route('trip');

        return $trip instanceof Trip && ($this->user()?->isAdmin() ?? false) && $this->user()->can('update', $trip);
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'driver_id' => ['required', 'integer', 'exists:users,id'],
            'vehicle_id' => ['required', 'integer', 'exists:vehicles,id'],
            'route_id' => ['required', 'integer', 'exists:routes,id'],
            'reason' => ['required', 'string', 'max:500'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['required' => 'Selecciona o completa :attribute.', 'exists' => 'La selección de :attribute no es válida.', 'reason.max' => 'El motivo no puede superar 500 caracteres.'];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['driver_id' => 'conductor', 'vehicle_id' => 'unidad', 'route_id' => 'ruta', 'reason' => 'el motivo del cambio'];
    }
}
