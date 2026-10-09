<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreStudentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        return $user && $user->can('manage-students');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name'            => ['required', 'string', 'max:100'],
            'last_name'             => ['required', 'string', 'max:100'],
            'gender'                => ['required', 'in:M,F'],
            'date_of_birth'         => ['required', 'date', 'before:today'],
            'place_of_birth'        => ['nullable', 'string', 'max:150'],
            'birth_certificate_number' => ['nullable', 'string', 'max:50'],
            'nationality'           => ['nullable', 'string', 'max:100'],
            'photo'                 => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'address'               => ['nullable', 'string'],
            // Parents / tuteur
            'father_name'           => ['nullable', 'string', 'max:150'],
            'father_phone'          => ['nullable', 'string', 'max:255'],
            'mother_name'           => ['nullable', 'string', 'max:150'],
            'mother_phone'          => ['nullable', 'string', 'max:255'],
            'guardian_name'         => ['nullable', 'string', 'max:150'],
            'guardian_phone'        => ['nullable', 'string', 'max:255'],
            'guardian_relationship' => ['nullable', 'string', 'max:100'],
            // Matricule
            'matricule'             => ['nullable', 'string', 'max:30'],
            // Scolarité (Inscription directe)
            'academic_year_id'      => ['required', 'exists:academic_years,id'],
            'class_group_id'        => ['required', 'exists:class_groups,id'],
            'enrollment_date'       => ['required', 'date'],
            'is_repeating'          => ['boolean'],
            'previous_class_group_id' => ['nullable', 'exists:class_groups,id'],
            'previous_class_label'    => ['nullable', 'string', 'max:150'],
            'origin_school'         => ['nullable', 'string', 'max:200'],
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required'    => 'Le prénom est obligatoire.',
            'last_name.required'     => 'Le nom est obligatoire.',
            'gender.required'        => 'Le genre est obligatoire.',
            'date_of_birth.required' => 'La date de naissance est obligatoire.',
            'date_of_birth.before'   => 'La date de naissance doit être dans le passé.',
        ];
    }
}
