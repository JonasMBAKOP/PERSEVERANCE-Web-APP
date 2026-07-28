@extends('layouts.app')

@section('title', ($previousEnrollment ?? null) ? 'Renouveler ' . $student->full_name : 'Inscrire ' . $student->full_name)
@section('page-title', ($previousEnrollment ?? null) ? 'Renouvellement d\'inscription' : 'Inscrire un élève')
@section('page-subtitle'){{ $student->full_name }}@endsection

@section('breadcrumb')
    <a href="{{ route('students.index') }}" class="hover:text-gray-700">
        Élèves
    </a>
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
    </svg>
    <a href="{{ route('students.show', $student) }}" class="hover:text-gray-700">{{ $student->full_name }}</a>
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
    </svg>
    <span style="color:#1A3A6B;" class="font-medium">
        {{ ($previousEnrollment ?? null) ? 'Renouvellement' : 'Inscription' }}
    </span>
@endsection

@section('content')

<div class="max-w-5xl mx-auto" x-data="enrollForm()">

    {{-- ═══════════════════════════════════════════════════════════════ --}}
    {{-- CARTES D'ALERTE --}}
    {{-- ═══════════════════════════════════════════════════════════════ --}}

    @if($previousEnrollment)
    <div class="mb-8 p-6 bg-gradient-to-r from-blue-50 to-blue-100 
                border-l-4 border-blue-500 rounded-xl shadow-md">
        <div class="flex items-start gap-4">
            <div class="flex-shrink-0 pt-0.5">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="flex-1">
                <h3 class="font-bold text-blue-900 mb-1 text-lg">
                    Renouvellement pour {{ $activeYear?->label }}
                </h3>
                <div class="text-sm text-blue-800 space-y-2">
                    <p>
                        <strong>Année précédente:</strong> {{ $previousEnrollment->classGroup->full_name }}
                        ({{ $previousEnrollment->academicYear->label }})
                    </p>
                    <p>
                        Choisissez la <strong>même classe</strong> si redoublant(e), 
                        ou une classe du <strong>niveau supérieur</strong> si promu(e).
                    </p>
                    @if($repeatClasses->isNotEmpty() || $promotionClasses->isNotEmpty())
                    <div class="pt-2 mt-2 border-t border-blue-200 flex flex-wrap gap-3">
                        @if($repeatClasses->isNotEmpty())
                        <div class="text-blue-700">
                            <span class="font-semibold">Redoublement suggéré:</span>
                            {{ $repeatClasses->pluck('full_name')->join(', ') }}
                        </div>
                        @endif
                        @if($promotionClasses->isNotEmpty())
                        <div class="text-blue-700">
                            <span class="font-semibold">Promotion suggérée:</span>
                            {{ $promotionClasses->pluck('full_name')->join(', ') }}
                        </div>
                        @endif
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif

    @if($existingEnrollment)
    <div class="mb-8 p-6 bg-gradient-to-r from-amber-50 to-amber-100 
                border-l-4 border-amber-500 rounded-xl shadow-md">
        <div class="flex items-start gap-4">
            <div class="flex-shrink-0 pt-0.5">
                <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <div class="flex-1">
                <h3 class="font-bold text-amber-900 mb-1 text-lg">
                    <svg class="inline h-4 w-4 mr-1 align-[-2px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>Déjà inscrit(e) cette année
                </h3>
                <div class="text-sm text-amber-800 space-y-2">
                    <p>
                        {{ $student->full_name }} est actuellement inscrit(e) en
                        <strong>{{ $existingEnrollment->classGroup->full_name }}</strong>
                        pour {{ $activeYear?->label }}.
                    </p>
                    <p class="text-amber-700 font-semibold">
                        Utilisez le <strong>transfert</strong> pour changer de classe.
                    </p>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════ --}}
    {{-- FICHE ÉLÈVE --}}
    {{-- ═══════════════════════════════════════════════════════════════ --}}

    <div class="mb-10 bg-white rounded-2xl shadow-lg border border-gray-100 p-8 
                hover:shadow-xl transition-shadow">
        <div class="flex items-center gap-8">
            {{-- Avatar --}}
            <div class="flex-shrink-0">
                @if($student->photo)
                <img src="{{ $student->photo_url }}"
                     class="w-24 h-24 rounded-xl object-cover ring-4 ring-blue-100 shadow-md">
                @else
                <div class="w-24 h-24 rounded-xl flex items-center justify-center 
                            text-white font-bold text-2xl ring-4 ring-blue-100 shadow-md"
                     style="background: linear-gradient(135deg, #1A3A6B, #2563eb);">
                    {{ strtoupper(substr($student->last_name, 0, 1))
                       . strtoupper(substr($student->first_name, 0, 1)) }}
                </div>
                @endif
            </div>
            {{-- Infos --}}
            <div class="flex-1">
                <h2 class="text-3xl font-bold mb-3" style="color: #1A3A6B;">
                    {{ $student->full_name }}
                </h2>
                <div class="flex flex-wrap gap-6 text-sm text-gray-700">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                        </svg>
                        <div>
                            <p class="text-xs text-gray-500">Matricule</p>
                            <p class="font-bold text-gray-900">{{ $student->matricule }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        <div>
                            <p class="text-xs text-gray-500">Genre</p>
                            <p class="font-bold text-gray-900">{{ $student->gender === 'M' ? 'Garçon' : 'Fille' }}</p>
                        </div>
                    </div>
                    @if($student->date_of_birth)
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <div>
                            <p class="text-xs text-gray-500">Âge</p>
                            <p class="font-bold text-gray-900">{{ $student->date_of_birth->age }} ans</p>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════ --}}
    {{-- FORMULAIRE --}}
    {{-- ═══════════════════════════════════════════════════════════════ --}}

    <form method="POST" action="{{ route('students.enroll.store', $student) }}"
          class="space-y-10">
        @csrf

        {{-- 1. SÉLECTION CLASSE --}}
        <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-10">
            <div class="mb-10 flex items-start gap-4">
                <div class="flex items-center justify-center w-10 h-10 rounded-lg text-lg font-bold text-white flex-shrink-0"
                     style="background-color: #1A3A6B;">
                    1
                </div>
                <div>
                    <h3 class="text-2xl font-bold text-gray-900">
                        Sélectionner la classe
                    </h3>
                    <p class="text-sm text-gray-500 mt-1">Choisissez la section puis la classe</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                {{-- Année (affichage) --}}
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2.5">
                        Année scolaire
                    </label>
                    <div class="px-5 py-3.5 bg-gradient-to-r from-blue-50 to-blue-100 
                                border-2 border-blue-300 rounded-xl text-sm font-bold text-blue-900
                                flex items-center justify-between">
                        <span>{{ $activeYear?->label }}</span>
                        <span class="px-3 py-1 bg-green-200 text-green-800 text-xs font-bold rounded-full">
                            ACTIVE
                        </span>
                    </div>
                    <input type="hidden" name="academic_year_id" value="{{ $activeYear?->id }}">
                </div>

                {{-- Section --}}
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2.5">
                        Section <span class="text-red-500">*</span>
                    </label>
                    <select x-model="selectedSection"
                            @change="selectedClass = ''"
                            class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl 
                                   text-sm font-medium focus:outline-none focus:border-blue-500
                                   bg-white transition-colors">
                        <option value="">Sélectionner...</option>
                        @foreach($sectionsJson as $section)
                        <option value="{{ $section['id'] }}">
                            {{ $section['name'] }}
                        </option>
                        @endforeach
                    </select>
                </div>

            </div>

            {{-- Classe (full width) --}}
            <div class="mt-8">
                <label class="block text-sm font-bold text-gray-700 mb-2.5">
                    Classe <span class="text-red-500">*</span>
                </label>
                <select name="class_group_id"
                        x-model="selectedClass"
                        class="w-full px-4 py-3 border-2 rounded-xl text-sm font-medium 
                               focus:outline-none transition-colors bg-white"
                        :class="!selectedSection ? 'border-gray-200 opacity-50 cursor-not-allowed' : 'border-gray-300 focus:border-blue-500 @error(\"class_group_id\") border-red-500 @enderror'"
                        :disabled="!selectedSection">
                    <option value="">Sélectionner une classe...</option>
                    <template x-for="cls in filteredClasses" :key="cls.id">
                        <option :value="cls.id"
                                x-text="`${cls.full_name} (${cls.students_count}/${cls.max_students})` + 
                                        (cls.is_repeat ? ' — redoublement' : '') +
                                        (cls.is_promotion ? ' — promotion' : '')">
                        </option>
                    </template>
                </select>
                @error('class_group_id')
                <div class="mt-3 p-3 bg-red-50 border border-red-200 rounded-lg flex items-start gap-2">
                    <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    <p class="text-sm text-red-700 font-medium">{{ $message }}</p>
                </div>
                @enderror
            </div>
        </div>

        {{-- 2. DÉTAILS INSCRIPTION --}}
        <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-10">
            <div class="mb-10 flex items-start gap-4">
                <div class="flex items-center justify-center w-10 h-10 rounded-lg text-lg font-bold text-white flex-shrink-0"
                     style="background-color: #E87722;">
                    2
                </div>
                <div>
                    <h3 class="text-2xl font-bold text-gray-900">
                        Détails de l'inscription
                    </h3>
                    <p class="text-sm text-gray-500 mt-1">Complétez les informations d'inscription</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2.5">Date d'inscription <span class="text-red-500">*</span></label>
                    <input type="date" name="enrollment_date" value="{{ old('enrollment_date', date('Y-m-d')) }}"
                           class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl text-sm font-medium focus:outline-none focus:border-blue-500 @error('enrollment_date') border-red-500 @enderror">
                    @error('enrollment_date')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <p class="block text-sm font-bold text-gray-700 mb-2.5">Situation scolaire</p>
                    <div class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl text-sm font-semibold bg-gray-50 text-gray-700"
                         x-text="renewalSituation""></div>
                </div>
                <div>
                    <p class="block text-sm font-bold text-gray-700 mb-2.5">École d'origine</p>
                    <div class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl text-sm font-semibold bg-gray-50 text-gray-700">{{ $school->full_name }}</div>
                </div>
                <div>
                    <p class="block text-sm font-bold text-gray-700 mb-2.5">Classe précédente</p>
                    <div class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl text-sm font-semibold bg-gray-50 text-gray-700">{{ $previousEnrollment?->classGroup?->full_name ?? 'Non renseignée' }}</div>
                </div>
            </div>
        </div>
        {{-- ACTIONS --}}
        {{-- ═══════════════════════════════════════════════════════════════ --}}

        <div class="flex gap-4 items-center justify-between">
            <a href="{{ route('students.show', $student) }}"
               class="px-8 py-3.5 border-2 border-gray-300 rounded-xl
                      text-sm font-bold text-gray-700 text-center
                      hover:bg-gray-100 hover:border-gray-400 transition-all">
                ← Annuler
            </a>
            <button type="submit"
                    class="px-10 py-3.5 rounded-xl text-white text-sm font-bold 
                           flex items-center justify-center gap-3 transition-all
                           hover:shadow-lg hover:scale-105"
                    style="background: linear-gradient(135deg, #1A5C2A, #22c55e);">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Confirmer {{ ($previousEnrollment ?? null) ? 'le renouvellement' : 'l\'inscription' }}
            </button>
        </div>

    </form>

</div>

<script>
    const _sectionsData = {!! json_encode($sectionsJson) !!};
    const _classesData  = {!! json_encode($classesJson) !!};

    function enrollForm() {
        return {
            sections: _sectionsData,
            selectedYear: '{{ $activeYear?->id }}',
            selectedSection: '',
            selectedClass: '',
            allClasses: _classesData,
            previousClass: {
                levelId: {{ $previousEnrollment?->classGroup?->level_id ?? 'null' }},
                sectionId: {{ $previousEnrollment?->classGroup?->level?->section_id ?? 'null' }},
                levelOrder: {{ $previousEnrollment?->classGroup?->level?->order_index ?? 'null' }},
            },

            get renewalSituation() {
                if (!this.selectedClass) return 'Sélectionnez une classe';

                const selected = this.allClasses.find(
                    classGroup => String(classGroup.id) === String(this.selectedClass)
                );
                if (!selected || !this.previousClass.levelId) return 'Classe non admissible';
                if (String(selected.level_id) === String(this.previousClass.levelId)) return 'Redoublant(e)';
                if (String(selected.section_id) === String(this.previousClass.sectionId)
                    && Number(selected.level_order) > Number(this.previousClass.levelOrder)) {
                    return 'Promu(e)';
                }

                return 'Classe non admissible';
            },
            get filteredClasses() {
                if (!this.selectedSection) return [];
                return this.allClasses.filter(
                    classGroup => String(classGroup.section_id) === String(this.selectedSection)
                );
            }
        }
    }
</script>

@endsection
