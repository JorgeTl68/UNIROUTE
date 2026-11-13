<?php

namespace App\Http\Requests\Admin;

use App\Models\Stop;
use Illuminate\Foundation\Http\FormRequest;

class StoreStopRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Stop::class) ?? false;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:255'], 'latitude' => ['required', 'numeric', 'between:-90,90'], 'longitude' => ['required', 'numeric', 'between:-180,180'], 'has_shelter' => ['sometimes', 'boolean']];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['name.required' => 'Escribe el nombre de la parada.', 'latitude.between' => 'La latitud no es válida.', 'longitude.between' => 'La longitud no es válida.'];
    }
}
