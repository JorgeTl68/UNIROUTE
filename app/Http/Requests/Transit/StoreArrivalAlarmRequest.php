<?php

namespace App\Http\Requests\Transit;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreArrivalAlarmRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'route_id' => ['required', 'integer', Rule::exists('routes', 'id')],
            'stop_id' => [
                'required',
                'integer',
                Rule::exists('route_stop', 'stop_id')->where('route_id', $this->integer('route_id')),
            ],
            'minutes_before' => ['required', 'integer', 'between:1,30'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'stop_id.exists' => 'Elige una parada de esta ruta.',
            'minutes_before.between' => 'El aviso debe ser de 1 a 30 minutos antes.',
        ];
    }
}
