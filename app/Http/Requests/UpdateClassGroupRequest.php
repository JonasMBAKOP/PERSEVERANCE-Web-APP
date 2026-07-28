<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateClassGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        return $user && $user->can('manage-classes');
    }

    public function rules(): array
    {
        return [
            'level_id'         => ['required', 'exists:levels,id'],
            'name'             => ['sometimes', 'string', 'max:50'],
            'max_students'     => ['required', 'integer', 'min:1', 'max:200'],
            'titular_staff_id' => ['nullable', 'exists:staff,id'],
            'room'             => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'level_id.required'     => 'Le niveau est obligatoire.',
            'name.required'         => 'Le nom de la classe est obligatoire.',
            'max_students.required' => 'La capacité maximale est obligatoire.',
        ];
    }
}
