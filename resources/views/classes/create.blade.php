@extends('layouts.app')

@section('title', 'Nouvelle classe')
@section('page-title', 'Nouvelle Classe')
@section('page-subtitle')
    Création d'un groupe d'élèves pour l'année scolaire active : {{ $activeYear->label }}
@endsection

{{-- @section('breadcrumb')
    <a href="{{ route('classes.index') }}" class="hover:text-gray-700">Classes</a>
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
    </svg>
    <span class="font-medium" style="color: #1A3A6B;">Nouvelle classe</span>
@endsection --}}

@section('content')

<div class="max-w-5xl"
     x-data="classForm({{ json_encode($sections) }},
                        '{{ $selectedSectionId }}',
                        '{{ $selectedLevelId }}',
                        {{ json_encode(old('sub_group', '')) }})">

    {{-- Titre --}}
    {{-- <div class="mb-6">
        <h2 class="text-2xl font-extrabold" style="color: #1A3A6B;">Nouvelle Classe</h2>
        <p class="text-sm text-gray-500 mt-1">
            Création d'un groupe d'élèves pour l'année scolaire active :
            <span class="font-bold text-gray-700">{{ $activeYear->label }}</span>
        </p>
    </div> --}}

    <form method="POST" action="{{ route('classes.store') }}">
        @csrf
        <input type="hidden" name="academic_year_id" value="{{ $activeYear->id }}">
        <input type="hidden" name="name" :value="previewName">
        {{-- On met max_students à une valeur par défaut fixe côté serveur --}}
        <input type="hidden" name="max_students" value="60">

        {{-- ═══════════════════════════════════════════════════════════════
             LIGNE DU HAUT : Colonne gauche (infos) + Colonne droite (aperçu + actions)
        ════════════════════════════════════════════════════════════════════ --}}
        <div class="grid grid-cols-1 lg:grid-cols-5 gap-6 mb-6">

            {{-- ── COLONNE GAUCHE (3/5) — Informations de la classe --}}
            <div class="lg:col-span-3 bg-white rounded-2xl shadow-sm border border-gray-150 p-6">
                <h3 class="text-sm font-bold uppercase tracking-wider text-gray-400 mb-5 pb-2 border-b border-gray-100 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Informations de la classe
                </h3>

                <div class="space-y-4">
                    {{-- Section --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                            Section <span class="text-red-500">*</span>
                        </label>
                        <select x-model="selectedSection" @change="selectedLevel = ''"
                                class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-100 bg-white"
                                style="color: #1A3A6B;">
                            <option value="">Sélectionner une section...</option>
                            @foreach($sections as $section)
                            <option value="{{ $section->id }}">{{ $section->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {{-- Niveau --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                            Niveau <span class="text-red-500">*</span>
                        </label>
                        <select name="level_id" x-model="selectedLevel"
                                class="w-full px-3 py-2.5 border rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-100 bg-white
                                       @error('level_id') border-red-400 @else border-gray-200 @enderror"
                                style="color: #1A3A6B;">
                            <option value="" x-text="selectedSection ? 'Sélectionner le niveau...' : 'Choisir une section d\'abord'"></option>
                            <template x-for="level in filteredLevels" :key="level.id">
                                <option :value="level.id" x-text="level.name"></option>
                            </template>
                        </select>
                        @error('level_id')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Sous-groupe <span class="text-gray-400 font-normal">(optionnel)</span></label>
                        <input type="text" name="sub_group" x-model="subGroup" maxlength="50" placeholder="Ex. Spécial"
                               class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-100"
                               style="color: #1A3A6B;">
                        @error('sub_group')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>                    </div>

                    {{-- Titulaire de classe --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                            <span class="flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                Professeur titulaire responsable
                            </span>
                        </label>
                        <select name="titular_staff_id"
                                class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none bg-white"
                                style="color: #1A3A6B;">
                            <option value="">Aucun enseignant pour l'instant</option>
                            @foreach($staffList as $staff)
                            <option value="{{ $staff->id }}" {{ old('titular_staff_id') == $staff->id ? 'selected' : '' }}>
                                {{ $staff->full_name }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                </div>
            </div>

            {{-- ── COLONNE DROITE (2/5) — Aperçu + Actions --}}
            <div class="lg:col-span-2 flex flex-col gap-4">

                {{-- Aperçu du label --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-150 p-6 flex flex-col flex-1" style="min-height: 200px;">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-4">Aperçu du Label</p>

                    {{-- Badge de section --}}
                    <div class="mb-4">
                        <p class="text-xs text-gray-500 mb-1.5">Section</p>
                        <div class="px-3 py-1.5 rounded-lg border border-gray-200 text-sm font-semibold text-gray-700 min-h-[36px] flex items-center"
                             x-text="sectionName || '—'"></div>
                    </div>

                    {{-- Aperçu du nom complet --}}
                    <div class="flex-1 flex flex-col items-center justify-center rounded-2xl py-6"
                         style="background-color: #EBF3FB;">
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-3">Nom de la classe</p>
                        <div class="bg-white px-6 py-3 rounded-full shadow-sm border border-blue-50 font-black text-xl text-center"
                             style="color: #1A3A6B;"
                             x-text="previewName || '—'">
                        </div>
                    </div>
                </div>

                {{-- Boutons d'action --}}
                <div class="flex flex-col gap-3">
                    <button type="submit"
                            class="w-full py-3 rounded-xl text-white text-sm font-bold shadow-sm transition-all duration-200 hover:shadow-md hover:brightness-110 active:scale-[0.99] flex items-center justify-center gap-2"
                            style="background-color: #A24E0C;">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                        </svg>
                        Enregistrer la classe
                    </button>
                    <a href="{{ route('classes.index') }}"
                       class="w-full py-3 border border-gray-200 rounded-xl text-sm font-semibold text-gray-600 text-center transition-all hover:bg-gray-50">
                        Annuler
                    </a>
                </div>

            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════════════════
             LIGNE DU BAS : Bloc matières assignées
        ════════════════════════════════════════════════════════════════════ --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-150 p-6">
            <h3 class="text-sm font-bold uppercase tracking-wider text-gray-400 mb-4 pb-2 border-b border-gray-100 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                </svg>
                Matières assignées
            </h3>
            <div class="flex items-start gap-4 p-4 rounded-xl border border-dashed border-gray-200 bg-gray-50/40">
                <div class="w-9 h-9 rounded-full flex items-center justify-center flex-shrink-0"
                     style="background-color: #EBF3FB; color: #1A3A6B;">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-700">Attribution des matières non disponible à la création</p>
                    <p class="text-xs text-gray-500 mt-0.5">
                        Les matières, coefficients et attributions d'enseignants seront configurables
                        via le module <strong class="text-gray-900">Matières</strong> une fois la classe créée.
                    </p>
                </div>
            </div>
        </div>

    </form>
</div>

<script>
function classForm(sections, initialSection, initialLevel, initialSubGroup) {
    return {
        sections:        sections,
        selectedSection: initialSection || '',
        selectedLevel:   initialLevel   || '',
        subGroup:        initialSubGroup || '',

        get filteredLevels() {
            if (!this.selectedSection) return [];
            const section = this.sections.find(s => s.id == this.selectedSection);
            return section ? section.levels : [];
        },

        get sectionName() {
            if (!this.selectedSection) return '';
            const section = this.sections.find(s => s.id == this.selectedSection);
            return section ? section.name : '';
        },

        get levelName() {
            const lev = this.filteredLevels.find(l => l.id == this.selectedLevel);
            return lev ? lev.name : '';
        },

        get previewName() {
            let n = this.levelName;
            if (!n) return '';
            return [n, this.subGroup.trim()].filter(Boolean).join(' ');
        }
    }
}
</script>

@endsection
