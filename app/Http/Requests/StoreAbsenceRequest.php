<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreAbsenceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        return (bool) $user?->can('manage-absences');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'class_group_id'    => ['required', 'exists:class_groups,id'],
            'absence_date'      => ['required', 'date', 'before_or_equal:today'],
            'absences'          => ['required', 'array'],
            'absences.*.enrollment_id' => ['required', 'integer', 'exists:student_enrollments,id'],
            'absences.*.absent' => ['nullable', 'boolean'],
        ];
    }
}
