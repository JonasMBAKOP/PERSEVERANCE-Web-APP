<?php

namespace App\Http\Requests;

use App\Models\Staff;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        return $user && $user->can('manage-staff');
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name'    => ['required', 'string', 'max:100'],
            'last_name'     => ['required', 'string', 'max:100'],
            'gender'        => ['required', 'in:M,F'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'phone'         => ['nullable', 'string', 'max:30'],
            'email'         => ['nullable', 'email', 'max:191',
                                'unique:staff,email'],
            'photo'         => ['nullable', 'image',
                                'mimes:jpg,jpeg,png', 'max:2048'],
            'diploma'       => ['nullable', 'string', 'max:255'],
            'origin_school' => ['nullable', 'string', 'max:200'],
            'start_date'    => ['nullable', 'date'],
            'contract_type' => ['required',
                                'in:permanent,vacataire,semi_permanent'],
            'monthly_salary' => ['nullable', 'numeric', 'min:0'],
            'hourly_rate'    => ['nullable', 'numeric', 'min:0'],
            'period_rate'    => ['nullable', 'numeric', 'min:0'],
            'is_active'     => ['nullable', 'boolean'],
            'user_id'       => ['nullable', 'exists:users,id'],

            // Postes (checkboxes simples)
            'positions'          => ['required', 'array', 'min:1'],
            'positions.*'        => ['string', Rule::in(Staff::POSITIONS)],
            'primary_position'   => ['required', 'string', Rule::in(Staff::POSITIONS)],

            // Nouveau compte
            'user_option'       => ['nullable', 'in:none,existing,create'],
            'new_user_name'     => ['required_if:user_option,create', 'nullable',
                                    'string', 'max:191'],
            'new_user_email'    => ['required_if:user_option,create', 'nullable',
                                    'email', 'unique:users,email'],
            'new_user_password' => ['required_if:user_option,create', 'nullable',
                                    'string', 'min:8'],
            'new_user_role'     => ['nullable', 'exists:roles,name'],
        ];
        // return array_merge($this->profileRules(), [
        //     'create_account'   => ['nullable', 'boolean'],
        //     'account_email'    => [
        //         'nullable', 'required_if:create_account,1,true',
        //         'email', 'max:191', 'unique:users,email',
        //     ],
        //     'account_password' => [
        //         'nullable', 'required_if:create_account,1,true',
        //         'string', 'min:8', 'confirmed',
        //     ],
        //     'account_roles'    => ['nullable', 'array'],
        //     'account_roles.*'  => ['string', 'exists:roles,name'],
        // ]);
    }

    public function messages(): array
    {
        return [
            'first_name.required'          => 'Le prénom est obligatoire.',
            'last_name.required'           => 'Le nom est obligatoire.',
            'gender.required'              => 'Le genre est obligatoire.',
            'contract_type.required'       => 'Le type de contrat est obligatoire.',
            'positions.required'           => 'Veuillez sélectionner au moins un poste.',
            // 'positions.min'                => 'Veuillez sélectionner au moins un poste.',
            // 'primary_position.required'    => 'Veuillez indiquer le poste principal.',
            // 'user_id.unique'               => 'Ce compte utilisateur est déjà lié à un autre membre du personnel.',
            // 'user_id.prohibited_if'        => 'Impossible de lier un compte existant lors de la création d\'un nouveau compte.',
            // 'account_email.required_if'    => 'L\'e-mail du compte est obligatoire.',
            'account_email.unique'         => 'Cette adresse e-mail est déjà utilisée.',
            // 'account_password.required_if' => 'Le mot de passe du compte est obligatoire.',
            // 'account_password.min'         => 'Le mot de passe doit contenir au moins 8 caractères.',
            // 'photo.image'                  => 'La photo doit être une image.',
            // 'photo.max'                    => 'La photo ne doit pas dépasser 2 Mo.',
        ];
    }

    // public function withValidator($validator): void
    // {
    //     $validator->after(function ($validator) {
    //         $positions = (array) $this->input('positions', []);
    //         $primary   = $this->input('primary_position');

    //         if ($primary && ! in_array($primary, $positions, true)) {
    //             $validator->errors()->add(
    //                 'primary_position',
    //                 'Le poste principal doit faire partie des postes sélectionnés.'
    //             );
    //         }
    //     });
    // }

    // protected function prepareForValidation(): void
    // {
    //     $positions = array_values(array_unique(array_filter(
    //         (array) $this->input('positions', [])
    //     )));

    //     if ($this->boolean('create_account')) {
    //         $this->merge(['user_id' => null]);
    //     }

    //     if ($this->input('user_id') === '' || $this->input('user_id') === null) {
    //         $this->merge(['user_id' => null]);
    //     }

    //     $primary = $this->input('primary_position');
    //     if ((! $primary || ! in_array($primary, $positions, true)) && count($positions) > 0) {
    //         $primary = $positions[0];
    //     }

    //     $this->merge([
    //         'positions'        => $positions,
    //         'primary_position' => $primary,
    //     ]);
    // }

    // protected function profileRules(): array
    // {
    //     return [
    //         'first_name'       => ['required', 'string', 'max:100'],
    //         'last_name'        => ['required', 'string', 'max:100'],
    //         'gender'           => ['required', Rule::in(['M', 'F'])],
    //         'date_of_birth'    => ['nullable', 'date', 'before:today'],
    //         'phone'            => ['nullable', 'string', 'max:20'],
    //         'email'            => ['nullable', 'email', 'max:191'],
    //         'photo'            => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
    //         'diploma'          => ['nullable', Rule::in(Staff::DIPLOMAS)],
    //         'start_date'       => ['nullable', 'date'],
    //         'contract_type'    => ['required', Rule::in(Staff::CONTRACT_TYPES)],
    //         'positions'        => ['required', 'array', 'min:1'],
    //         'positions.*'      => ['string', Rule::in(Staff::POSITIONS)],
    //         'primary_position' => ['required', 'string', Rule::in(Staff::POSITIONS)],
    //         'user_id'          => [
    //             'nullable',
    //             'prohibited_if:create_account,1,true',
    //             'exists:users,id',
    //             Rule::unique('staff', 'user_id'),
    //         ],
    //     ];
    // }
}
