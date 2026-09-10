<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreGradeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        return $user && $user->hasAnyRole([
            'super-admin','directeur','censeur','secretaire','enseignant','assistant-direction'
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'grades'                     => ['nullable', 'array'],
            'grades.*.grade'             => ['nullable', 'numeric', 'min:0', 'max:20'],
            'grades.*.is_absent'         => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'grades.*.grade.min'  => 'Une note ne peut pas être inférieure à 0.',
            'grades.*.grade.max'  => 'Une note ne peut pas dépasser 20.',
        ];
    }
}
