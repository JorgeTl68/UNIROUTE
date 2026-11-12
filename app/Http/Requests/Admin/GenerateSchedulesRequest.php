<?php

namespace App\Http\Requests\Admin;

use App\Models\Schedule;
use Illuminate\Foundation\Http\FormRequest;

class GenerateSchedulesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Schedule::class) ?? false;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'weekdays' => ['required', 'array', 'min:1', 'max:7'],
            'weekdays.*' => ['required', 'integer', 'distinct', 'between:1,7'],
            'starts_at' => ['required', 'date_format:H:i'],
            'ends_at' => ['required', 'date_format:H:i', 'after_or_equal:starts_at'],
            'interval_minutes' => ['required', 'integer', 'min:1'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'weekdays.required' => 'Selecciona al menos un día.',
            'weekdays.array' => 'Selecciona días válidos.',
            'weekdays.min' => 'Selecciona al menos un día.',
            'weekdays.max' => 'Solo puedes seleccionar los siete días de la semana.',
            'weekdays.*.integer' => 'Selecciona días válidos.',
            'weekdays.*.distinct' => 'No repitas días en la generación.',
            'weekdays.*.between' => 'Selecciona días entre lunes y domingo.',
            'starts_at.required' => 'Indica la hora inicial.',
            'starts_at.date_format' => 'La hora inicial debe tener formato HH:mm.',
            'ends_at.required' => 'Indica la hora final.',
            'ends_at.date_format' => 'La hora final debe tener formato HH:mm.',
            'ends_at.after_or_equal' => 'La hora final debe ser igual o posterior a la inicial el mismo día.',
            'interval_minutes.required' => 'Indica el intervalo entre salidas.',
            'interval_minutes.integer' => 'El intervalo debe ser un número entero de minutos.',
            'interval_minutes.min' => 'El intervalo debe ser mayor que cero.',
        ];
    }
}
