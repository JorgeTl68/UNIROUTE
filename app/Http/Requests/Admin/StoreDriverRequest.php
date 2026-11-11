<?php

namespace App\Http\Requests\Admin;

use App\Models\DriverProfile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreDriverRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', DriverProfile::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('license_number'))) {
            $this->merge(['license_number' => mb_strtoupper(trim($this->input('license_number')))]);
        }
        if (is_string($this->input('email'))) {
            $this->merge(['email' => mb_strtolower(trim($this->input('email')))]);
        }
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults(), 'min:8'],
            'license_number' => ['required', 'string', 'max:255', 'unique:driver_profiles,license_number'],
            'hired_at' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'campus_id' => ['nullable', 'integer', 'exists:campuses,id'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'required' => 'El campo :attribute es obligatorio.',
            'email.unique' => 'Este correo ya tiene una cuenta.',
            'email.email' => 'Escribe un correo válido.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'license_number.unique' => 'Esta licencia ya está registrada.',
            'hired_at.before_or_equal' => 'La fecha de contratación no puede ser futura.',
            'hired_at.date_format' => 'Selecciona una fecha válida.',
            'campus_id.exists' => 'Selecciona un campus válido.',
            'max.string' => 'El campo :attribute no puede superar 255 caracteres.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['name' => 'nombre', 'email' => 'correo', 'password' => 'contraseña', 'license_number' => 'licencia', 'hired_at' => 'fecha de contratación'];
    }
}
