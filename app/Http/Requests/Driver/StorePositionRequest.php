<?php

namespace App\Http\Requests\Driver;

use App\Enums\TripStatus;
use App\Models\Trip;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePositionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $trip = $this->route('trip');

        return $trip instanceof Trip
            && $trip->status === TripStatus::InProgress
            && ($this->user()?->can('update', $trip) ?? false);
    }

    /**
     * Lo que manda el navegador con watchPosition.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'speed_kmh' => ['nullable', 'numeric', 'min:0', 'max:200'],
            'heading' => ['nullable', 'numeric', 'min:0', 'max:360'],
        ];
    }
}
