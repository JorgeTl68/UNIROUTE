<?php

namespace App\Http\Requests\Driver;

use App\Enums\TripStatus;
use App\Models\Trip;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateOccupancyRequest extends FormRequest
{
    public function authorize(): bool
    {
        $trip = $this->route('trip');

        return $trip instanceof Trip
            && $trip->status === TripStatus::InProgress
            && ($this->user()?->can('update', $trip) ?? false);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Trip $trip */
        $trip = $this->route('trip');

        return [
            'occupancy' => ['required', 'integer', 'min:0', 'max:'.($trip->vehicle->capacity ?? 0)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'occupancy.max' => 'La ocupación no puede pasar de la capacidad de la unidad.',
        ];
    }
}
