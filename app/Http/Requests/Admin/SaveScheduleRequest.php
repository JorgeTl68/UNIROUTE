<?php

namespace App\Http\Requests\Admin;

use App\Models\Schedule;
use App\Models\TransitRoute;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveScheduleRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $time = $this->input('departs_at');
        if (is_string($time) && preg_match('/^\d{2}:\d{2}$/', $time) === 1) {
            $this->merge(['departs_at' => $time.':00']);
        }
    }

    public function authorize(): bool
    {
        $schedule = $this->route('schedule');

        return $schedule instanceof Schedule
            ? ($this->user()?->can('update', $schedule) ?? false)
            : ($this->user()?->can('create', Schedule::class) ?? false);
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $route = $this->route('transitRoute');
        $schedule = $this->route('schedule');
        $routeId = $route instanceof TransitRoute ? $route->id : 0;

        return [
            'weekday' => ['required', 'integer', 'between:1,7'],
            'departs_at' => [
                'required',
                'date_format:H:i:s',
                Rule::unique('schedules', 'departs_at')
                    ->where(fn ($query) => $query
                        ->where('route_id', $routeId)
                        ->where('weekday', $this->input('weekday')))
                    ->ignore($schedule instanceof Schedule ? $schedule->id : null),
            ],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'weekday.required' => 'Selecciona un día.',
            'weekday.integer' => 'Selecciona un día válido.',
            'weekday.between' => 'Selecciona un día entre lunes y domingo.',
            'departs_at.required' => 'Indica la hora de salida.',
            'departs_at.date_format' => 'La hora debe tener formato HH:mm.',
            'departs_at.unique' => 'Esta ruta ya tiene una salida registrada para ese día y hora.',
        ];
    }
}
