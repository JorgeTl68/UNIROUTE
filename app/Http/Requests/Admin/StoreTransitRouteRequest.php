<?php

namespace App\Http\Requests\Admin;

use App\Models\TransitRoute;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreTransitRouteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', TransitRoute::class) ?? false;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'campus_id' => ['required', 'integer', 'exists:campuses,id'],
            'number' => ['required', 'integer', 'min:1', 'max:65535', Rule::unique('routes', 'number')->where(fn ($query) => $query->where('campus_id', $this->input('campus_id')))],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:65535'],
            'color' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'frequency_minutes' => ['required', 'integer', 'min:1', 'max:65535'],
            'service_starts_at' => ['required', 'date_format:H:i'],
            'service_ends_at' => ['required', 'date_format:H:i', 'after:service_starts_at'],
            'stops' => ['required', 'array', 'min:2', 'max:65535'],
            'stops.*.id' => ['required', 'integer', 'distinct', 'exists:stops,id'],
            'stops.*.minutes_from_start' => ['required', 'integer', 'min:0', 'max:65535'],
            'preview_token' => ['required', 'uuid'],
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
            'required' => 'El campo :attribute es obligatorio.',
            'campus_id.exists' => 'Selecciona un campus válido.',
            'number.integer' => 'El número de ruta debe ser entero.',
            'number.min' => 'El número de ruta debe ser mayor que cero.',
            'number.max' => 'El número de ruta no puede superar 65535.',
            'number.unique' => 'Ya existe una ruta con este número en el campus seleccionado.',
            'name.max' => 'El nombre no puede superar 255 caracteres.',
            'description.max' => 'La descripción es demasiado extensa.',
            'color.regex' => 'Selecciona un color hexadecimal válido.',
            'frequency_minutes.integer' => 'La frecuencia debe expresarse en minutos enteros.',
            'frequency_minutes.min' => 'La frecuencia debe ser mayor que cero.',
            'frequency_minutes.max' => 'La frecuencia no puede superar 65535 minutos.',
            'service_starts_at.date_format' => 'La hora de inicio debe tener formato HH:mm.',
            'service_ends_at.date_format' => 'La hora de fin debe tener formato HH:mm.',
            'service_ends_at.after' => 'La hora de fin debe ser posterior a la hora de inicio el mismo día.',
            'stops.min' => 'Selecciona al menos dos paradas.',
            'stops.*.id.distinct' => 'Una parada no puede repetirse en el recorrido.',
            'stops.*.id.exists' => 'Una de las paradas seleccionadas ya no existe.',
            'stops.*.minutes_from_start.integer' => 'Los tiempos deben ser minutos enteros.',
            'stops.*.minutes_from_start.min' => 'Los tiempos no pueden ser negativos.',
            'stops.*.minutes_from_start.max' => 'Los tiempos no pueden superar 65535 minutos.',
            'preview_token.required' => 'Calcula el trazado antes de guardar la ruta.',
            'preview_token.uuid' => 'La vista previa del trazado no es válida. Vuelve a calcularla.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'campus_id' => 'campus',
            'number' => 'número',
            'name' => 'nombre',
            'description' => 'descripción',
            'color' => 'color',
            'frequency_minutes' => 'frecuencia',
            'service_starts_at' => 'hora de inicio',
            'service_ends_at' => 'hora de fin',
            'stops' => 'paradas',
            'preview_token' => 'trazado',
        ];
    }
}
