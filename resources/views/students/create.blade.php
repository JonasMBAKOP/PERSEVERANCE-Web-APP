@extends('layouts.app')

@section('title', 'Nouvel élève')
@section('page-title', 'Inscrire un nouvel élève')
@section('page-subtitle', 'Formulaire d\'inscription pour un nouvel élève dans l\'établissement')

@section('breadcrumb')
    <a href="{{ route('students.index') }}" class="hover:text-gray-700">Élèves</a>
    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
    </svg>
    <span class="font-medium text-gray-700">Nouvelle inscription</span>
@endsection

@section('content')
<div x-data="wizardForm()" class="max-w-4xl mx-auto space-y-6">

    {{-- Titre de la page --}}
    <h1 class="text-2xl font-bold text-gray-900">Inscrire un nouvel élève</h1>

    {{-- Messages d'erreur et succès --}}
    @if ($errors->any())
        <div class="bg-red-50 border border-red-200 rounded-xl p-4 shadow-sm">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4v.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div class="flex-1">
                    <h3 class="font-bold text-red-900 mb-2">Erreur lors de la création</h3>
                    <ul class="space-y-1 text-sm text-red-800">
                        @foreach ($errors->all() as $error)
                            <li>• {{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    @if (session('error'))
        <div class="bg-red-50 border border-red-200 rounded-xl p-4 shadow-sm">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4v.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div class="flex-1">
                    <p class="font-bold text-red-900">{{ session('error') }}</p>
                </div>
            </div>
        </div>
    @endif

    @if (session('success'))
        <div class="bg-green-50 border border-green-200 rounded-xl p-4 shadow-sm">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-green-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m7 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="font-bold text-green-900">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    {{-- Progress Steps --}}
    <div class="flex items-center justify-between bg-white border border-gray-200 rounded-xl p-6 shadow-sm">
        {{-- Étape 1 (Scolarité) --}}
        <div class="flex flex-col items-center flex-1 relative">
            <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm border-2 transition-all"
                 :class="step === 1 ? 'bg-[#9c4005] border-[#9c4005] text-white shadow-sm' : (step > 1 ? 'bg-green-600 border-green-600 text-white' : 'bg-white border-gray-300 text-gray-400')">
                <template x-if="step > 1">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </template>
                <template x-if="step <= 1"><span>1</span></template>
            </div>
            <span class="text-xs font-semibold mt-2 transition-colors" :class="step === 1 ? 'text-[#9c4005]' : 'text-gray-500'">Scolarité</span>
        </div>

        <div class="flex-1 h-0.5 bg-gray-200 -mt-6" :class="step > 1 ? 'bg-green-500' : ''"></div>

        {{-- Étape 2 (Identité) --}}
        <div class="flex flex-col items-center flex-1 relative">
            <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm border-2 transition-all"
                 :class="step === 2 ? 'bg-[#9c4005] border-[#9c4005] text-white shadow-sm' : (step > 2 ? 'bg-green-600 border-green-600 text-white' : 'bg-white border-gray-300 text-gray-400')">
                <template x-if="step > 2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </template>
                <template x-if="step <= 2"><span>2</span></template>
            </div>
            <span class="text-xs font-semibold mt-2 transition-colors" :class="step === 2 ? 'text-[#9c4005]' : 'text-gray-500'">Identité</span>
        </div>

        <div class="flex-1 h-0.5 bg-gray-200 -mt-6" :class="step > 2 ? 'bg-green-500' : ''"></div>

        {{-- Étape 3 --}}
        <div class="flex flex-col items-center flex-1 relative">
            <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm border-2 transition-all"
                 :class="step === 3 ? 'bg-[#9c4005] border-[#9c4005] text-white shadow-sm' : (step > 3 ? 'bg-green-600 border-green-600 text-white' : 'bg-white border-gray-300 text-gray-400')">
                <template x-if="step > 3">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </template>
                <template x-if="step <= 3"><span>3</span></template>
            </div>
            <span class="text-xs font-semibold mt-2 transition-colors" :class="step === 3 ? 'text-[#9c4005]' : 'text-gray-500'">Parents/Tuteurs</span>
        </div>

        <div class="flex-1 h-0.5 bg-gray-200 -mt-6" :class="step > 3 ? 'bg-green-500' : ''"></div>

        {{-- Étape 4 --}}
        <div class="flex flex-col items-center flex-1 relative">
            <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm border-2 transition-all"
                 :class="step === 4 ? 'bg-[#9c4005] border-[#9c4005] text-white shadow-sm' : 'bg-white border-gray-300 text-gray-400'">
                <span>4</span>
            </div>
            <span class="text-xs font-semibold mt-2 transition-colors" :class="step === 4 ? 'text-[#9c4005]' : 'text-gray-500'">Confirmation</span>
        </div>
    </div>

    {{-- Formulaire Principal --}}
    <form method="POST" action="{{ route('students.store') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf

        {{-- Conteneur Card --}}
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 overflow-hidden">
            
            {{-- Entête Section (Change en fonction de l'étape) --}}
            <div class="bg-[#F8FAFC] border-b border-gray-200 -mx-6 -mt-6 px-6 py-4 flex items-center gap-2.5 rounded-t-2xl mb-6">
                <svg class="w-5 h-5 text-[#1A3A6B]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <h2 class="text-sm font-bold uppercase tracking-wider text-[#1A3A6B]"
                    x-text="step === 1 ? 'Scolarité de l\'élève' : (step === 2 ? 'Identité de l\'élève' : (step === 3 ? 'Parents & Tuteurs' : 'Confirmation des informations'))">
                </h2>
            </div>


            {{-- ÉTAPE 1 : SCOLARITÉ --}}
            <div x-show="step === 1" class="space-y-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    
                    {{-- Année Scolaire en Cours (Lecture seule) --}}
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Année Scolaire Active</label>
                        <div class="px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-700 font-semibold flex items-center justify-between">
                            <span>{{ $activeYear?->label }}</span>
                            <span class="bg-green-100 text-green-800 text-xs px-2 py-0.5 rounded-full font-bold">ACTIVE</span>
                            <input type="hidden" name="academic_year_id" value="{{ $activeYear?->id }}">
                        </div>
                    </div>

                    {{-- Section --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Section <span class="text-red-500">*</span></label>
                        <select x-model="selectedSection" @change="selectedClass = ''; previousClassLabel = ''"
                                class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm bg-white text-gray-700 focus:outline-none focus:ring-1 focus:ring-[#1A3A6B] focus:border-[#1A3A6B]">
                            <option value="">Sélectionner la section...</option>
                            <template x-for="sec in sections" :key="sec.id">
                                <option :value="sec.id" x-text="sec.name"></option>
                            </template>
                        </select>
                        <span class="text-xs text-red-500 mt-1 block" x-text="errors.selectedSection"></span>
                    </div>


                    {{-- Classe (section) --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Classe <span class="text-red-500">*</span></label>
                        <select name="class_group_id" x-model="selectedClass"
                                @change="updatePreviousClassLabel()"
                                :disabled="!selectedSection"
                                class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm bg-white text-gray-700 focus:outline-none focus:ring-1 focus:ring-[#1A3A6B] focus:border-[#1A3A6B] disabled:bg-gray-100 disabled:text-gray-400">
                            <option value="">Sélectionner une classe...</option>
                            <template x-for="cls in filteredClasses" :key="cls.id">
                                {{-- <option :value="cls.id" x-text="cls.full_name + (cls.max_students ? ` (${cls.students_count}/${cls.max_students} élèves)` : '')"></option> --}}
                                <option :value="cls.id" x-text="cls.full_name"></option>
                            </template>
                        </select>
                        <span class="text-xs text-red-500 mt-1 block" x-text="errors.selectedClass"></span>
                    </div>

                    {{-- Date d'inscription --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Date d'inscription <span class="text-red-500">*</span></label>
                        <input type="date" name="enrollment_date" x-model="enrollmentDate"
                               class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm bg-white focus:outline-none focus:ring-1 focus:ring-[#1A3A6B] focus:border-[#1A3A6B]">
                        <span class="text-xs text-red-500 mt-1 block" x-text="errors.enrollmentDate"></span>
                    </div>

                    {{-- Situation (Nouveau vs Redoublant vs Promu) --}}
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Situation <span class="text-red-500">*</span></label>
                        <div class="flex gap-3">
                            <label class="flex items-center gap-2 px-4 py-2.5 border rounded-lg cursor-pointer flex-1 text-sm bg-white hover:bg-gray-50 transition-all"
                                   :class="situation === 'nouveau' ? 'border-[#9c4005] bg-orange-50/20 text-[#9c4005] font-semibold' : 'border-gray-200'">
                                <input type="radio" name="is_repeating" value="0"
                                       :checked="situation === 'nouveau'"
                                       @change="situation = 'nouveau'; updatePreviousClassLabel()"
                                       class="text-[#9c4005] focus:ring-[#9c4005]">
                                Nouveau / Promu
                            </label>
                            <label class="flex items-center gap-2 px-4 py-2.5 border rounded-lg cursor-pointer flex-1 text-sm bg-white hover:bg-gray-50 transition-all"
                                   :class="situation === 'redoublant' ? 'border-[#9c4005] bg-orange-50/20 text-[#9c4005] font-semibold' : 'border-gray-200'">
                                <input type="radio" name="is_repeating" value="1"
                                       :checked="situation === 'redoublant'"
                                       @change="situation = 'redoublant'; updatePreviousClassLabel()"
                                       class="text-[#9c4005] focus:ring-[#9c4005]">
                                Redoublant
                            </label>
                        </div>
                    </div>

                    {{-- École d'origine --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">École d'origine (si venant d'ailleurs)</label>
                        <input type="text" name="origin_school" x-model="originSchool" placeholder="Ex: Collège de la Retraite"
                               class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm bg-white focus:outline-none focus:ring-1 focus:ring-[#1A3A6B] focus:border-[#1A3A6B]">
                    </div>

                    {{-- Classe précédente (auto selon la classe et la situation) --}}
                    <template x-if="situation === 'nouveau'">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Classe précédente</label>
                            <input type="text" readonly x-model="previousClassLabel"
                                   placeholder="Sélectionnez d'abord une classe"
                                   class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm bg-gray-50 text-gray-700">
                            <input type="hidden" name="previous_class_label" :value="previousClassLabel">
                            <p class="text-xs text-gray-400 mt-1">Déterminée automatiquement selon la classe d'inscription.</p>
                        </div>
                    </template>
                    <template x-if="situation === 'redoublant'">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Classe précédente</label>
                            <input type="text" readonly x-model="previousClassLabel"
                                   placeholder="Identique à la classe sélectionnée"
                                   class="w-full px-3 py-2.5 border border-amber-200 rounded-lg text-sm bg-amber-50 text-amber-900">
                            <input type="hidden" name="previous_class_label" :value="previousClassLabel">
                            <p class="text-xs text-amber-700 mt-1">Pour un redoublant, la classe précédente est la même que la classe actuelle.</p>
                        </div>
                    </template>

                </div>
            </div>

            {{-- ÉTAPE 2 : IDENTITÉ --}}
            <div x-show="step === 2" class="space-y-6">
                {{-- Suite Infos Identité --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    {{-- Nom --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Nom de famille <span class="text-red-500">*</span></label>
                        <input type="text" name="last_name" x-model="lastName" @input="lastName = lastName.toUpperCase()" placeholder="Entrez le nom"
                               class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm bg-white focus:outline-none focus:ring-1 focus:ring-[#1A3A6B] focus:border-[#1A3A6B]">
                        <span class="text-xs text-red-500 mt-1 block" x-text="errors.lastName"></span>
                    </div>

                    {{-- Prénom --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Prénom(s) <span class="text-red-500">*</span></label>
                        <input type="text" name="first_name" x-model="firstName" placeholder="Entrez le prénom"
                               class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm bg-white focus:outline-none focus:ring-1 focus:ring-[#1A3A6B] focus:border-[#1A3A6B]">
                        <span class="text-xs text-red-500 mt-1 block" x-text="errors.firstName"></span>
                    </div>

                    {{-- Date de naissance --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Date de naissance <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <input type="date" name="date_of_birth" x-model="dateOfBirth"
                                   class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm bg-white focus:outline-none focus:ring-1 focus:ring-[#1A3A6B] focus:border-[#1A3A6B]">
                        </div>
                        <span class="text-xs text-red-500 mt-1 block" x-text="errors.dateOfBirth"></span>
                    </div>

                    {{-- Lieu de naissance --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Lieu de naissance <span class="text-red-500">*</span></label>
                        <input type="text" name="place_of_birth" x-model="placeOfBirth" placeholder="Ville"
                               class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm bg-white focus:outline-none focus:ring-1 focus:ring-[#1A3A6B] focus:border-[#1A3A6B]">
                        <span class="text-xs text-red-500 mt-1 block" x-text="errors.placeOfBirth"></span>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 border-t border-gray-100 pt-6">
                    
                    {{-- Colonne Gauche : Upload Photo --}}
                    <div class="flex flex-col items-center justify-center border-2 border-dashed border-gray-200 rounded-xl p-6 bg-gray-50/50 hover:bg-gray-50 transition-all cursor-pointer relative"
                         @click="$refs.photoInput.click()">
                        <input type="file" name="photo" x-ref="photoInput" class="hidden" accept="image/*" @change="handlePhotoUpload($event)">
                        
                        <template x-if="photoPreview">
                            <img :src="photoPreview" class="w-32 h-32 rounded-full object-cover border-4 border-white shadow-md">
                        </template>
                        
                        <template x-if="!photoPreview">
                            <div class="flex flex-col items-center">
                                <div class="w-16 h-16 rounded-full bg-blue-50 flex items-center justify-center text-blue-600 mb-3">
                                    <svg class="w-8 h-8 text-[#1A3A6B]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                </div>
                                <span class="text-sm font-semibold text-gray-700">Cliquer pour ajouter une photo</span>
                                <span class="text-xs text-gray-400 mt-1">Format JPG/PNG, Max 2Mo</span>
                            </div>
                        </template>
                    </div>

                    {{-- Colonne Droite : Infos Identité rapides --}}
                    <div class="md:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        
                        {{-- Genre --}}
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Sexe <span class="text-red-500">*</span></label>
                            <div class="flex gap-4">
                                <label class="flex items-center gap-2 px-4 py-2.5 border rounded-lg cursor-pointer flex-1 text-sm bg-white hover:bg-gray-50 transition-all"
                                       :class="gender === 'M' ? 'border-[#9c4005] bg-orange-50/20 text-[#9c4005] font-semibold' : 'border-gray-200'">
                                    <input type="radio" name="gender" value="M" x-model="gender" class="text-[#9c4005] focus:ring-[#9c4005]">
                                    Masculin
                                </label>
                                <label class="flex items-center gap-2 px-4 py-2.5 border rounded-lg cursor-pointer flex-1 text-sm bg-white hover:bg-gray-50 transition-all"
                                       :class="gender === 'F' ? 'border-[#9c4005] bg-orange-50/20 text-[#9c4005] font-semibold' : 'border-gray-200'">
                                    <input type="radio" name="gender" value="F" x-model="gender" class="text-[#9c4005] focus:ring-[#9c4005]">
                                    Féminin
                                </label>
                            </div>
                            <span class="text-xs text-red-500 mt-1 block" x-text="errors.gender"></span>
                        </div>

                        {{-- Nationalité --}}
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Nationalité <span class="text-red-500">*</span></label>
                            <input type="text" name="nationality" x-model="nationality"
                                   class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm bg-white focus:outline-none focus:ring-1 focus:ring-[#1A3A6B] focus:border-[#1A3A6B]">
                            <span class="text-xs text-red-500 mt-1 block" x-text="errors.nationality"></span>
                        </div>

                        {{-- Numéro Matricule --}}
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Numéro Matricule</label>
                            <div class="flex items-center gap-2">
                                <button type="button" 
                                        @click="matriculeMode = matriculeMode === 'auto' ? 'manual' : 'auto'"
                                        :class="matriculeMode === 'auto' ? 'border-[#9c4005] bg-orange-50' : 'border-gray-200 bg-white'"
                                        class="px-3 py-2 border rounded-lg text-xs font-semibold whitespace-nowrap transition-all">
                                    <span x-show="matriculeMode === 'auto'">Généré</span>
                                    <span x-show="matriculeMode === 'manual'">Saisir</span>
                                </button>
                                <template x-if="matriculeMode === 'auto'">
                                    <div class="flex-1 flex items-center gap-2 bg-gray-50 px-3 py-2 rounded-lg border border-gray-200">
                                        <span class="font-mono text-sm font-bold text-[#1A3A6B] tracking-wider" x-text="matricule"></span>
                                        <input type="hidden" name="matricule" :value="matricule">
                                    </div>
                                </template>
                                <template x-if="matriculeMode === 'manual'">
                                    <input type="text" name="matricule" x-model="matricule" placeholder="Ex: CP-2026-0001"
                                           class="flex-1 px-3 py-2 border border-gray-200 rounded-lg text-sm bg-white focus:outline-none focus:ring-1 focus:ring-[#1A3A6B] focus:border-[#1A3A6B]">
                                </template>
                            </div>
                        </div>

                        {{-- Numéro Acte de Naissance --}}
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Numéro de l'acte de naissance</label>
                            <input type="text" name="birth_certificate_number" x-model="birthCertificateNumber" placeholder="Ex: 1234567890"
                                   class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm bg-white focus:outline-none focus:ring-1 focus:ring-[#1A3A6B] focus:border-[#1A3A6B]">
                            <p class="text-xs text-gray-400 mt-1">Optionnel</p>
                        </div>

                    </div>
                </div>
            </div>

            
            {{-- ÉTAPE 3 : PARENTS / TUTEURS --}}
            <div x-show="step === 3" class="space-y-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    
                    {{-- Père --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Nom complet du père</label>
                        <input type="text" name="father_name" x-model="fatherName" placeholder="Ex: NTANKEU Joseph"
                               class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm bg-white focus:outline-none focus:ring-1 focus:ring-[#1A3A6B] focus:border-[#1A3A6B]">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Téléphone du père</label>
                        <input type="text" name="father_phone" x-model="fatherPhone" placeholder="+237 6XX XX XX XX"
                               class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm bg-white focus:outline-none focus:ring-1 focus:ring-[#1A3A6B] focus:border-[#1A3A6B]">
                    </div>

                    {{-- Mère --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Nom complet de la mère</label>
                        <input type="text" name="mother_name" x-model="motherName" placeholder="Ex: MEKOU Denise"
                               class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm bg-white focus:outline-none focus:ring-1 focus:ring-[#1A3A6B] focus:border-[#1A3A6B]">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Téléphone de la mère</label>
                        <input type="text" name="mother_phone" x-model="motherPhone" placeholder="+237 6XX XX XX XX"
                               class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm bg-white focus:outline-none focus:ring-1 focus:ring-[#1A3A6B] focus:border-[#1A3A6B]">
                    </div>

                    {{-- Tuteur / Contact d'urgence --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Nom du tuteur / contact d'urgence</label>
                        <input type="text" name="guardian_name" x-model="guardianName" placeholder="Ex: Tagne Roger"
                               class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm bg-white focus:outline-none focus:ring-1 focus:ring-[#1A3A6B] focus:border-[#1A3A6B]">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Téléphone du tuteur</label>
                        <input type="text" name="guardian_phone" x-model="guardianPhone" placeholder="+237 6XX XX XX XX"
                               class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm bg-white focus:outline-none focus:ring-1 focus:ring-[#1A3A6B] focus:border-[#1A3A6B]">
                    </div>
                    
                    {{-- Lien de parenté --}}
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Lien avec l'élève</label>
                        <input type="text" name="guardian_relationship" x-model="guardianRelationship" placeholder="Ex: Oncle, Tante, Tuteur légal..."
                               class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm bg-white focus:outline-none focus:ring-1 focus:ring-[#1A3A6B] focus:border-[#1A3A6B]">
                    </div>

                    {{-- Adresse domicile --}}
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Adresse domicile</label>
                        <textarea name="address" x-model="address" rows="3" placeholder="Quartier, Ville, Rue..."
                                  class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm bg-white focus:outline-none focus:ring-1 focus:ring-[#1A3A6B] focus:border-[#1A3A6B] resize-none"></textarea>
                    </div>

                </div>
            </div>

            {{-- ÉTAPE 4 : CONFIRMATION --}}
            <div x-show="step === 4" class="space-y-6">
                
                <div class="flex items-center gap-3 p-4 bg-green-50 border border-green-200 text-green-800 rounded-xl">
                    <svg class="w-5 h-5 text-green-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-sm">
                        Veuillez relire attentivement les informations ci-dessous avant de valider l'inscription.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    
                    {{-- Fiche Identité --}}
                    <div class="border border-gray-100 rounded-xl p-4 bg-gray-50/50">
                        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3 pb-2 border-b border-gray-200 flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-[#1A3A6B]"></span>
                            Identité de l'élève
                        </h3>
                        <div class="flex items-center gap-4 mb-4">
                            <template x-if="photoPreview">
                                <img :src="photoPreview" class="w-16 h-16 rounded-full object-cover border-2 border-white shadow-sm">
                            </template>
                            <template x-if="!photoPreview">
                                <div class="w-16 h-16 rounded-full bg-blue-100 text-[#1A3A6B] flex items-center justify-center font-bold text-lg" x-text="gender === 'M' ? 'G' : 'F'"></div>
                            </template>
                            <div>
                                <h4 class="font-bold text-gray-800" x-text="lastName + ' ' + firstName"></h4>
                                <p class="text-xs text-gray-500 font-semibold" x-text="'Matricule : ' + matricule"></p>
                            </div>
                        </div>
                        <dl class="grid grid-cols-2 gap-y-2 text-xs">
                            <dt class="text-gray-400">Genre :</dt>
                            <dd class="text-gray-800 font-semibold" x-text="gender === 'M' ? 'Masculin' : 'Féminin'"></dd>

                            <dt class="text-gray-400">Date de naissance :</dt>
                            <dd class="text-gray-800 font-semibold" x-text="formatDate(dateOfBirth)"></dd>

                            <dt class="text-gray-400">Lieu de naissance :</dt>
                            <dd class="text-gray-800 font-semibold" x-text="placeOfBirth"></dd>

                            <dt class="text-gray-400">Nationalité :</dt>
                            <dd class="text-gray-800 font-semibold" x-text="nationality"></dd>

                            <dt class="text-gray-400">N° acte de naissance :</dt>
                            <dd class="text-gray-800 font-semibold" x-text="birthCertificateNumber || '—'"></dd>
                        </dl>
                    </div>

                    {{-- Fiche Scolarité --}}
                    <div class="border border-gray-100 rounded-xl p-4 bg-gray-50/50">
                        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3 pb-2 border-b border-gray-200 flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-[#1A3A6B]"></span>
                            Scolarité
                        </h3>
                        <dl class="grid grid-cols-2 gap-y-2.5 text-xs">
                            <dt class="text-gray-400">Année Scolaire :</dt>
                            <dd class="text-gray-800 font-bold">{{ $activeYear?->label }}</dd>

                            <dt class="text-gray-400">Section :</dt>
                            <dd class="text-gray-800 font-semibold" x-text="getSectionName()"></dd>

                            <dt class="text-gray-400">Classe affectée :</dt>
                            <dd class="font-bold text-sm text-[#9c4005]" x-text="getClassName()"></dd>

                            <dt class="text-gray-400">Date d'inscription :</dt>
                            <dd class="text-gray-800 font-semibold" x-text="formatDate(enrollmentDate)"></dd>

                            <dt class="text-gray-400">Situation :</dt>
                            <dd class="text-gray-800 font-semibold">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold"
                                      :class="situation === 'redoublant' ? 'bg-amber-100 text-amber-800' : 'bg-green-100 text-green-800'"
                                      x-text="situation === 'redoublant' ? 'Redoublant(e)' : 'Nouveau / Promu(e)'"></span>
                            </dd>

                            <dt class="text-gray-400">Classe précédente :</dt>
                            <dd class="text-gray-800 font-semibold" x-text="previousClassLabel || '—'"></dd>

                            <dt class="text-gray-400">École d'origine :</dt>
                            <dd class="text-gray-800 font-semibold" x-text="originSchool || '—'"></dd>
                        </dl>
                    </div>

                    {{-- Fiche Parents --}}
                    <div class="md:col-span-2 border border-gray-100 rounded-xl p-4 bg-gray-50/50">
                        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3 pb-2 border-b border-gray-200 flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-[#1A3A6B]"></span>
                            Parents & Tuteurs
                        </h3>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
                            <div>
                                <p class="text-gray-400 mb-1">Père :</p>
                                <p class="font-bold text-gray-800" x-text="fatherName || '—'"></p>
                                <p class="text-gray-500 mt-0.5" x-text="fatherPhone"></p>
                            </div>
                            <div>
                                <p class="text-gray-400 mb-1">Mère :</p>
                                <p class="font-bold text-gray-800" x-text="motherName || '—'"></p>
                                <p class="text-gray-500 mt-0.5" x-text="motherPhone"></p>
                            </div>
                            <div>
                                <p class="text-gray-400 mb-1">Tuteur / Urgence :</p>
                                <p class="font-bold text-gray-800" x-text="guardianName || '—'"></p>
                                <p class="text-gray-500 mt-0.5" x-text="guardianPhone"></p>
                                <p class="text-gray-400 mt-0.5 italic" x-text="guardianRelationship ? '(' + guardianRelationship + ')' : ''"></p>
                            </div>
                            <div class="sm:col-span-3 border-t border-gray-100 pt-3">
                                <p class="text-gray-400 mb-1">Adresse de résidence :</p>
                                <p class="font-semibold text-gray-800" x-text="address || '—'"></p>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

        </div>

        {{-- Boutons de Navigation Wizard --}}
        <div class="flex items-center justify-between">
            <div>
                {{-- Annuler (Étape 1) ou Précédent --}}
                <template x-if="step === 1">
                    <a href="{{ route('students.index') }}"
                       class="inline-flex items-center justify-center px-6 py-2.5 border border-[#1A3A6B] text-[#1A3A6B] bg-white text-sm font-semibold rounded-lg hover:bg-gray-50 shadow-sm transition-all">
                        Annuler
                    </a>
                </template>
                <template x-if="step > 1">
                    <button type="button" @click="step--"
                            class="inline-flex items-center justify-center px-6 py-2.5 border border-[#1A3A6B] text-[#1A3A6B] bg-white text-sm font-semibold rounded-lg hover:bg-gray-50 shadow-sm transition-all">
                        Retour
                    </button>
                </template>
            </div>

            <div>
                {{-- Suivant ou Confirmer l'inscription --}}
                <template x-if="step < 4">
                    <button type="button" @click="nextStep()"
                            class="inline-flex items-center gap-1.5 justify-center px-6 py-2.5 bg-[#9c4005] hover:bg-[#853604] text-white text-sm font-semibold rounded-lg shadow-sm transition-all">
                        Étape suivante
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>
                </template>
                <template x-if="step === 4">
                    <button type="submit"
                            class="inline-flex items-center gap-1.5 justify-center px-6 py-2.5 bg-[#9c4005] hover:bg-[#853604] text-white text-sm font-bold rounded-lg shadow-sm transition-all">
                        Confirmer l'inscription
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </button>
                </template>
            </div>
        </div>

    </form>
</div>

<script>
    // Chargement des données injectées depuis PHP
    const _sectionsData = {!! json_encode($sectionsJson) !!};
    const _classesData  = {!! json_encode($classesJson) !!};

    function wizardForm() {
        return {
            step: 1,
            photoPreview: null,
            
            // États des formulaires - Étape 1
            gender: '{{ old('gender', '') }}',
            nationality: '{{ old('nationality', 'Camerounaise') }}',
            matricule: '{{ $suggestedMatricule }}',
            matriculeMode: '{{ old('matricule') ? 'manual' : 'auto' }}',
            birthCertificateNumber: '{{ old('birth_certificate_number', '') }}',
            lastName: '{{ old('last_name', '') }}',
            firstName: '{{ old('first_name', '') }}',
            dateOfBirth: '{{ old('date_of_birth', '') }}',
            placeOfBirth: '{{ old('place_of_birth', '') }}',
            
            // Scolarité - Étape 2
            sections: _sectionsData,
            classes: _classesData,
            selectedSection: '{{ old('section_id', '') }}',
            selectedClass: '{{ old('class_group_id', $preSelectedClass?->id ?? '') }}',
            enrollmentDate: '{{ old('enrollment_date', date('Y-m-d')) }}',
            situation: '{{ old('is_repeating') == 1 ? 'redoublant' : 'nouveau' }}',
            originSchool: '{{ old('origin_school', '') }}',
            previousClassLabel: '{{ old('previous_class_label', '') }}',

            // Parents - Étape 3
            fatherName: '{{ old('father_name', '') }}',
            fatherPhone: '{{ old('father_phone', '') }}',
            motherName: '{{ old('mother_name', '') }}',
            motherPhone: '{{ old('mother_phone', '') }}',
            guardianName: '{{ old('guardian_name', '') }}',
            guardianPhone: '{{ old('guardian_phone', '') }}',
            guardianRelationship: '{{ old('guardian_relationship', '') }}',
            address: '{{ old('address', '') }}',

            // Gestion erreurs (charger les erreurs Laravel et convertir en camelCase)
            errors: {
@php
$errorMap = [
    'first_name' => 'firstName',
    'last_name' => 'lastName',
    'date_of_birth' => 'dateOfBirth',
    'place_of_birth' => 'placeOfBirth',
    'birth_certificate_number' => 'birthCertificateNumber',
    'section_id' => 'selectedSection',
    'class_group_id' => 'selectedClass',
    'previous_class_label' => 'previousClassLabel',
    'enrollment_date' => 'enrollmentDate',
];
foreach($errors->toArray() as $field => $messages) {
    $key = $errorMap[$field] ?? $field;
    $message = $messages[0] ?? '';
    echo "$key: '$message',\n";
}
@endphp
            },

            // Alpine init
            init() {
                if (this.selectedClass) {
                    const foundClass = this.classes.find(c => String(c.id) === String(this.selectedClass));
                    if (foundClass) {
                        this.selectedSection = foundClass.section_id;
                    }
                }
                this.updatePreviousClassLabel();
            },

            get filteredClasses() {
                if (!this.selectedSection) return [];
                return this.classes.filter(c =>
                    String(c.section_id) === String(this.selectedSection)
                );
            },

            resolvePreviousClassLabel(cls) {
                if (!cls?.level_name) return '';

                const level = cls.level_name;

                const defaults = {
                    'Petite Section': '',
                    'Moyenne Section': 'Petite Section',
                    'Grande Section': 'Moyenne Section',
                    'SIL': 'Grande Section',
                    'CP': 'SIL',
                    'CE1': 'CP',
                    'CE2': 'CE1',
                    'CM1': 'CE2',
                    'CM2': 'CM1',
                    'Pre-Nursery': '',
                    'Nursery 1': 'Pre-Nursery',
                    'Nursery 2': 'Nursery 1',
                    'Class 1': 'Nursery 2',
                    'Class 2': 'Class 1',
                    'Class 3': 'Class 2',
                    'Class 4': 'Class 3',
                    'Class 5': 'Class 4',
                    'Class 6': 'Class 5',
                };

                return defaults[level] || '';
            },

            updatePreviousClassLabel() {
                const cls = this.classes.find(c => String(c.id) === String(this.selectedClass));

                if (!cls) {
                    this.previousClassLabel = '';
                    return;
                }

                if (this.situation === 'redoublant') {
                    this.previousClassLabel = cls.full_name;
                    return;
                }

                this.previousClassLabel = this.resolvePreviousClassLabel(cls);
            },

            // Uploader Photo
            handlePhotoUpload(e) {
                const file = e.target.files[0];
                if (!file) return;
                const reader = new FileReader();
                reader.onload = ev => this.photoPreview = ev.target.result;
                reader.readAsDataURL(file);
            },

            // Navigation Wizard et Validation
            validateStep(s) {
                this.errors = {};
                if (s === 1) {
                    if (!this.selectedSection) this.errors.selectedSection = "La section est requise.";
                    if (!this.selectedClass) this.errors.selectedClass = "La classe est requise.";
                    if (!this.enrollmentDate) this.errors.enrollmentDate = "La date d'inscription est requise.";
                    if (!this.situation) this.errors.situation = "La situation est requise.";
                    this.updatePreviousClassLabel();
                } else if (s === 2) {
                    if (!this.lastName.trim()) this.errors.lastName = "Le nom de famille est requis.";
                    if (!this.firstName.trim()) this.errors.firstName = "Le prénom est requis.";
                    if (!this.gender) this.errors.gender = "Le genre est requis.";
                    if (!this.dateOfBirth) this.errors.dateOfBirth = "La date de naissance est requise.";
                    if (!this.placeOfBirth.trim()) this.errors.placeOfBirth = "Le lieu de naissance est requis.";
                    if (!this.nationality.trim()) this.errors.nationality = "La nationalité est requise.";
                }
                return Object.keys(this.errors).length === 0;
            },

            nextStep() {
                if (this.validateStep(this.step)) {
                    this.step++;
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
            },

            // Affichage dans Confirmation
            getSectionName() {
                const s = this.sections.find(x => String(x.id) === String(this.selectedSection));
                return s ? s.name : '—';
            },

            getClassName() {
                const c = this.classes.find(x => String(x.id) === String(this.selectedClass));
                return c ? c.full_name : '—';
            },

            formatDate(dStr) {
                if (!dStr) return '—';
                const parts = dStr.split('-');
                if (parts.length === 3) {
                    return `${parts[2]}/${parts[1]}/${parts[0]}`;
                }
                return dStr;
            }
        }
    }
</script>
@endsection