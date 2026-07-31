<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreSubjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        return Auth::check() && $user->can('manage-subjects');
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'section_id' => ['required', 'exists:sections,id'],
            'subject_category_id' => [
                'required',
                Rule::exists('subject_categories', 'id')->where(
                    fn ($query) => $query->where('section_id', $this->input('section_id'))
                ),
            ],
            'name' => ['required', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'section_id.required' => 'La section est obligatoire.',
            'subject_category_id.required' => 'La categorie est obligatoire.',
            'subject_category_id.exists' => 'La categorie doit appartenir a la section choisie.',
            'name.required' => 'Le nom de la matiere est obligatoire.',
        ];
    }
}
