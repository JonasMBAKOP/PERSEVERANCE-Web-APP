<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class AssignSubjectsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        return Auth::check() && $user->can('manage-subjects');
    }


    protected function prepareForValidation(): void
    {
        $subjects = $this->input('subjects', []);

        if (is_array($subjects)) {
            foreach ($subjects as $key => $subject) {
                if (array_key_exists('coefficient', $subject)) {
                    $subjects[$key]['coefficient'] = str_replace(',', '.', (string) $subject['coefficient']);
                }

                if (array_key_exists('grading_scale', $subject)) {
                    $subjects[$key]['grading_scale'] = str_replace(',', '.', (string) $subject['grading_scale']);
                }

                if (array_key_exists('hours_per_week', $subject) && $subject['hours_per_week'] !== null) {
                    $subjects[$key]['hours_per_week'] = str_replace(',', '.', (string) $subject['hours_per_week']);
                }
            }

            $this->merge(['subjects' => $subjects]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'subjects'                    => ['nullable', 'array'],
            'subjects.*.subject_id'       => ['required', 'exists:subjects,id'],
            'subjects.*.grading_scale'    => ['required', 'numeric', 'gt:0', 'max:1000'],
            'subjects.*.coefficient'      => ['required', 'numeric',
                                              'min:0.5', 'max:9'],
            'subjects.*.hours_per_week'   => ['nullable', 'numeric',
                                              'min:0.5', 'max:30'],
            'subjects.*.is_active'        => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'subjects.*.coefficient.required' =>
                'Le coefficient est obligatoire.',
            'subjects.*.coefficient.min' =>
                'Le coefficient minimum est 0,5.',
            'subjects.*.coefficient.max' =>
                'Le coefficient maximum est 9.',
        ];
    }
}
