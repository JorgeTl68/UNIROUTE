<?php

namespace App\Http\Requests\Admin;

use App\Models\TransitRoute;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class TraceTransitRouteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', TransitRoute::class) ?? false;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'stops' => ['required', 'array', 'min:2', 'max:65535'],
            'stops.*.id' => ['required', 'integer', 'distinct', 'exists:stops,id'],
            'stops.*.minutes_from_start' => ['required', 'integer', 'min:0', 'max:65535'],
        ];
    }

    /** @return array<int, callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $stops = $this->input('stops');
            if (! is_array($stops) || $validator->errors()->has('stops')) {
                return;
            }

            $previous = null;
            foreach ($stops as $index => $stop) {
                if (! is_array($stop) || ! isset($stop['minutes_from_start']) || ! is_numeric($stop['minutes_from_start'])) {
                    continue;
                }

                $minutes = (int) $stop['minutes_from_start'];
                if ($index === 0 && $minutes !== 0) {
                    $validator->errors()->add('stops.0.minutes_from_start', 'La primera parada debe iniciar en 0 minutos.');
                } elseif ($previous !== null && $minutes <= $previous) {
                    $validator->errors()->add("stops.{$index}.minutes_from_start", 'El tiempo debe ser mayor que el de la parada anterior.');
                }
                $previous = $minutes;
            }
        }];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'stops.required' => 'Selecciona las paradas del recorrido.',
            'stops.min' => 'Selecciona al menos dos paradas.',
            'stops.*.id.required' => 'Selecciona una parada válida.',
            'stops.*.id.distinct' => 'Una parada no puede repetirse en el recorrido.',
            'stops.*.id.exists' => 'Una de las paradas seleccionadas ya no existe.',
            'stops.*.minutes_from_start.required' => 'Indica el tiempo acumulado de cada parada.',
            'stops.*.minutes_from_start.integer' => 'Los tiempos deben ser minutos enteros.',
            'stops.*.minutes_from_start.min' => 'Los tiempos no pueden ser negativos.',
            'stops.*.minutes_from_start.max' => 'Los tiempos no pueden superar 65535 minutos.',
        ];
    }
}
