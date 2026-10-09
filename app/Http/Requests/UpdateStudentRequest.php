<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateStudentRequest extends FormRequest
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
        $id = $this->route('student')->id;

        return [
            'first_name'           => ['required', 'string', 'max:100'],
            'last_name'            => ['required', 'string', 'max:100'],
            'gender'               => ['required', 'in:M,F'],
            'date_of_birth'        => ['required', 'date', 'before:today'],
            'place_of_birth'       => ['nullable', 'string', 'max:150'],
            'nationality'          => ['nullable', 'string', 'max:100'],
            'photo'                => ['nullable', 'image',
                                       'mimes:jpg,jpeg,png', 'max:2048'],
            'address'              => ['nullable', 'string'],
            'father_name'          => ['nullable', 'string', 'max:150'],
            'father_phone'         => ['nullable', 'string', 'max:255'],
            'mother_name'          => ['nullable', 'string', 'max:150'],
            'mother_phone'         => ['nullable', 'string', 'max:255'],
            'guardian_name'        => ['nullable', 'string', 'max:150'],
            'guardian_phone'       => ['nullable', 'string', 'max:255'],
            'guardian_relationship'=> ['nullable', 'string', 'max:100'],
            'matricule'            => ['nullable', 'string', 'max:30',
                                       "unique:students,matricule,{$id}"],
        ];
    }
}
