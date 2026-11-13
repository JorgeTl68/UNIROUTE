<?php

namespace App\Http\Requests\Admin;

use App\Models\Incident;
use Illuminate\Foundation\Http\FormRequest;

class ApplyDetourRequest extends FormRequest
{
    public function authorize(): bool
    {
        $incident = $this->route('incident');

        return $incident instanceof Incident && ($this->user()?->can('update', $incident) ?? false);
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return ['skipped_stop_ids' => ['required', 'array', 'min:1', 'max:100'], 'skipped_stop_ids.*' => ['required', 'integer', 'distinct', 'exists:stops,id']];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['skipped_stop_ids.required' => 'Selecciona las paradas afectadas.', 'skipped_stop_ids.min' => 'Selecciona al menos una parada.', 'skipped_stop_ids.*.exists' => 'Una parada ya no existe.'];
    }
}
