<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $employee = $this->user()?->employee;

        return [
            'name' => ['required', 'string', 'max:255'],
            'notification_email' => $employee
                ? ['required', 'email', 'max:255', Rule::unique('employees', 'notification_email')->ignore($employee->id)]
                : ['nullable', 'email', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('notification_email')) {
            $this->merge([
                'notification_email' => strtolower(trim((string) $this->input('notification_email'))),
            ]);
        }
    }
}
