<?php

namespace App\Http\Requests\Admin;

use App\Enums\VehicleAmenity;
use App\Enums\VehicleStatus;
use App\Models\Vehicle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Vehicle::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $normalized = [];
        foreach (['code', 'plate'] as $field) {
            if (is_string($this->input($field))) {
                $normalized[$field] = mb_strtoupper(trim($this->input($field)));
            }
        }
        $this->merge($normalized);
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:255', Rule::unique('vehicles', 'code')],
            'plate' => ['required', 'string', 'max:255', Rule::unique('vehicles', 'plate')],
            'model' => ['required', 'string', 'max:255'],
            'capacity' => ['required', 'integer', 'min:1', 'max:65535'],
            'status' => ['required', Rule::enum(VehicleStatus::class)],
            'amenities' => ['nullable', 'array', 'max:3'],
            'amenities.*' => ['required', 'distinct', Rule::enum(VehicleAmenity::class)],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'required' => 'El campo :attribute es obligatorio.',
            'string' => 'El campo :attribute debe ser texto.',
            'code.unique' => 'Ya existe una unidad con este código.',
            'plate.unique' => 'Ya existe una unidad con estas placas.',
            'capacity.integer' => 'La capacidad debe ser un número entero.',
            'capacity.min' => 'La capacidad debe ser mayor que cero.',
            'capacity.max' => 'La capacidad no puede superar 65535 pasajeros.',
            'status.enum' => 'Selecciona un estado válido.',
            'amenities.array' => 'Selecciona las amenidades de la lista.',
            'amenities.max' => 'Solo hay tres amenidades disponibles.',
            'amenities.*.enum' => 'Selecciona una amenidad válida.',
            'amenities.*.distinct' => 'Las amenidades no deben repetirse.',
            'max.string' => 'El campo :attribute no puede superar 255 caracteres.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['code' => 'código', 'plate' => 'placas', 'model' => 'modelo', 'capacity' => 'capacidad', 'status' => 'estado'];
    }
}
