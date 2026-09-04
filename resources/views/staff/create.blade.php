@extends('layouts.app')

@section('title', 'Nouveau membre du personnel')
@section('page-title', 'Nouveau Membre du Personnel')
@section('page-subtitle', 'Créer et configurer un dossier personnel dans l\'établissement')

@section('breadcrumb')
    <a href="{{ route('staff.index') }}" class="hover:text-gray-700 transition-colors">
        Personnel
    </a>
    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
    </svg>
    <span class="font-semibold text-[#1A3A6B]">Nouveau membre</span>
@endsection

@section('content')

<form id="create-staff-form" method="POST" action="{{ route('staff.store') }}" enctype="multipart/form-data"
      x-data="salaryContractForm('{{ old('contract_type', 'permanent') }}')">
    @csrf

    <div class="space-y-6">

        {{-- ── SECTION HAUTE : 2 colonnes principales (Gauche: Identité & Pro, Droite: Photo, Statut & Action) ── --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">

            {{-- ── COLONNE GAUCHE (2/3) : Identité & Infos Professionnelles ── --}}
            <div class="lg:col-span-2 space-y-6">

                {{-- Card 1 : Identité & Contacts --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 transition-all">
                    <div class="flex items-center justify-between mb-5 pb-3 border-b border-gray-100">
                        <h3 class="text-sm font-semibold uppercase tracking-wider text-[#1A3A6B] flex items-center gap-2">
                            <span class="flex items-center justify-center w-7 h-7 rounded-lg bg-blue-50 text-[#1A3A6B]">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                            </span>
                            Identité &amp; Contacts
                        </h3>
                        <span class="text-xs text-gray-400">Champs requis marqués d'un <span class="text-red-500">*</span></span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        {{-- Nom --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Nom <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="last_name"
                                   value="{{ old('last_name') }}"
                                   placeholder="Ex: KAMGA"
                                   class="w-full px-3.5 py-2.5 border rounded-xl text-sm uppercase focus:outline-none focus:ring-2 focus:ring-blue-200 transition-all {{ $errors->has('last_name') ? 'border-red-400 bg-red-50/20' : 'border-gray-200 focus:border-blue-500' }}">
                            @error('last_name')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Prénom(s) --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Prénom(s) <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="first_name"
                                   value="{{ old('first_name') }}"
                                   placeholder="Ex: Jean-Paul"
                                   class="w-full px-3.5 py-2.5 border rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-200 transition-all {{ $errors->has('first_name') ? 'border-red-400 bg-red-50/20' : 'border-gray-200 focus:border-blue-500' }}">
                            @error('first_name')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Genre --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Sexe / Genre <span class="text-red-500">*</span>
                            </label>
                            <div class="flex gap-3">
                                @foreach(['M' => 'Masculin', 'F' => 'Féminin'] as $val => $lbl)
                                <label class="flex items-center justify-center gap-2 px-4 py-2.5 border rounded-xl cursor-pointer transition-all flex-1 text-sm font-medium {{ old('gender') === $val ? 'border-blue-400 bg-blue-50/80 text-blue-900 shadow-sm' : 'border-gray-200 hover:bg-gray-50 text-gray-700' }}">
                                    <input type="radio" name="gender"
                                           value="{{ $val }}"
                                           {{ old('gender') === $val ? 'checked' : '' }}
                                           style="accent-color:#1A3A6B;">
                                    {{ $lbl }}
                                </label>
                                @endforeach
                            </div>
                            @error('gender')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Date de naissance --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Date de naissance
                            </label>
                            <input type="date" name="date_of_birth"
                                   value="{{ old('date_of_birth') }}"
                                   class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-500 transition-all">
                        </div>

                        {{-- Téléphone --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Numéro de téléphone
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-3.5 flex items-center text-gray-400 pointer-events-none">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                    </svg>
                                </span>
                                <input type="text" name="phone"
                                       value="{{ old('phone') }}"
                                       placeholder="+237 6XX XXX XXX"
                                       class="w-full pl-10 pr-3.5 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-500 transition-all">
                            </div>
                        </div>

                        {{-- Email --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                E-mail professionnel
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-3.5 flex items-center text-gray-400 pointer-events-none">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                </span>
                                <input type="email" name="email"
                                       value="{{ old('email') }}"
                                       placeholder="prenom.nom@coptan.cm"
                                       class="w-full pl-10 pr-3.5 py-2.5 border rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-200 transition-all {{ $errors->has('email') ? 'border-red-400 bg-red-50/20' : 'border-gray-200 focus:border-blue-500' }}">
                            </div>
                            @error('email')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Card 2 : Informations Professionnelles & Rémunération --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 transition-all">
                    <div class="flex items-center justify-between mb-5 pb-3 border-b border-gray-100">
                        <h3 class="text-sm font-semibold uppercase tracking-wider text-[#1A3A6B] flex items-center gap-2">
                            <span class="flex items-center justify-center w-7 h-7 rounded-lg bg-blue-50 text-[#1A3A6B]">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M12 14l9-5-9-5-9 5 9 5z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0112 20.055a11.952 11.952 0 01-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/>
                                </svg>
                            </span>
                            Informations Professionnelles &amp; Contrat
                        </h3>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-6 gap-4">
                        {{-- Diplôme / Qualification --}}
                        <div class="sm:col-span-3">
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Diplôme / Qualification
                            </label>
                            <input type="text" name="diploma"
                                   value="{{ old('diploma') }}"
                                   placeholder="Ex: Licence / Master en Mathématiques"
                                   class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-500 transition-all">
                        </div>

                        {{-- Établissement d'obtention --}}
                        <div class="sm:col-span-3">
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Établissement d'obtention
                            </label>
                            <input type="text" name="origin_school"
                                   value="{{ old('origin_school') }}"
                                   placeholder="Ex: ENSET Douala, Univ. Yaoundé I"
                                   class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-500 transition-all">
                        </div>

                        {{-- Date d'embauche --}}
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Date d'embauche
                            </label>
                            <input type="date" name="start_date"
                                   value="{{ old('start_date') }}"
                                   class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-500 transition-all">
                        </div>

                        {{-- Type de contrat --}}
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Type de contrat <span class="text-red-500">*</span>
                            </label>
                            <select name="contract_type"
                                    x-model="contractType"
                                    class="w-full px-3.5 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-500 bg-white transition-all">
                                @foreach([
                                    'permanent' => 'Permanent',
                                    'vacataire' => 'Vacataire',
                                    'semi_permanent' => 'Semi Permanent',
                                ] as $val => $lbl)
                                <option value="{{ $val }}" {{ old('contract_type') === $val ? 'selected' : '' }}>
                                    {{ $lbl }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Salaire mensuel (permanent / semi permanent) --}}
                        <div class="sm:col-span-2" x-show="contractType === 'permanent' || contractType === 'semi_permanent'" x-cloak>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Salaire mensuel
                            </label>
                            <div class="relative">
                                <input type="number" name="monthly_salary"
                                       x-bind:disabled="contractType === 'vacataire'"
                                       value="{{ old('monthly_salary') }}"
                                       placeholder="Ex: 150 000"
                                       min="0" step="25"
                                       class="w-full pl-3.5 pr-14 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-500 transition-all">
                                <span class="absolute inset-y-0 right-3.5 flex items-center text-xs font-semibold text-gray-400 pointer-events-none">
                                    FCFA
                                </span>
                            </div>
                        </div>

                        {{-- Tarif horaire (vacataire) --}}
                        <div class="sm:col-span-2" x-show="contractType === 'vacataire'" x-cloak>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Tarif horaire
                            </label>
                            <div class="relative">
                                <input type="number" name="hourly_rate"
                                       x-bind:disabled="contractType !== 'vacataire'"
                                       value="{{ old('hourly_rate') }}"
                                       placeholder="Ex: 2 500"
                                       min="0" step="25"
                                       class="w-full pl-3.5 pr-16 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-500 transition-all">
                                <span class="absolute inset-y-0 right-3.5 flex items-center text-xs font-semibold text-gray-400 pointer-events-none">
                                    FCFA/h
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            {{-- ── COLONNE DROITE (1/3) : Photo, Statut Dossier & Actions ── --}}
            <div class="space-y-6">

                {{-- Photo de profil --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 transition-all" x-data="photoUpload()">
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-[#1A3A6B] mb-4 pb-2 border-b border-gray-100">
                        Photo de profil
                    </h3>
                    <div class="flex flex-col items-center gap-4">
                        <template x-if="preview">
                            <img :src="preview" alt="Aperçu" class="w-28 h-28 rounded-full object-cover ring-4 ring-blue-50 shadow-md">
                        </template>
                        <template x-if="!preview">
                            <div class="w-28 h-28 rounded-full flex items-center justify-center bg-blue-50 text-[#1A3A6B] ring-4 ring-gray-50 shadow-inner">
                                <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                            </div>
                        </template>

                        <div class="w-full border-2 border-dashed border-gray-200 rounded-xl p-4 text-center cursor-pointer hover:border-blue-400 hover:bg-blue-50/40 transition-all group"
                             @click="$refs.photoInput.click()">
                            <input type="file" name="photo" x-ref="photoInput" class="hidden" accept="image/*" @change="handleFile($event)">
                            <p class="text-xs font-semibold text-gray-600 group-hover:text-blue-700">Choisir une image</p>
                            <p class="text-[11px] text-gray-400 mt-0.5">JPG, PNG — Max 2 Mo</p>
                        </div>
                    </div>
                </div>

                {{-- Statut du dossier --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-semibold text-gray-800">Dossier actif</p>
                            <p class="text-xs text-gray-400 mt-0.5">Permet l'accès aux opérations</p>
                        </div>
                        <label class="relative inline-flex cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" checked class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-checked:bg-emerald-600 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-full shadow-inner"></div>
                        </label>
                    </div>
                </div>

                {{-- Boutons d'action --}}
                <div class="space-y-3">
                    <button type="submit"
                            class="w-full py-3.5 rounded-xl text-white font-bold text-sm flex items-center justify-center gap-2 shadow-sm transition-all hover:shadow-md hover:bg-opacity-95"
                            style="background-color:#1A5C2A;">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Créer le dossier du personnel
                    </button>

                    <a href="{{ route('staff.index') }}"
                       class="block w-full py-2.5 rounded-xl text-center text-sm font-medium text-gray-600 border border-gray-200 hover:bg-gray-50 transition-colors">
                        Annuler
                    </a>
                </div>

            </div>

        </div>

        {{-- ── SECTION BASSE (PLEINE LARGEUR 100%) : Postes, Affectation Préfet & Compte ── --}}
        <div class="pt-2">
            @include('staff._positions_and_user')
        </div>

    </div>
</form>



<script>
function photoUpload() {
    return {
        preview: null,
        handleFile(e) {
            const file = e.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = (ev) => this.preview = ev.target.result;
            reader.readAsDataURL(file);
        }
    }
}

function salaryContractForm(initialContractType) {
    return {
        contractType: initialContractType || 'permanent',
    }
}
</script>

@endsection
