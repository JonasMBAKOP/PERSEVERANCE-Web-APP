@extends('layouts.app')

@section('title', 'Modifier ' . $classGroup->full_name)
@section('page-title', 'Modifier Classe')
@section('page-subtitle')
    Mise à jour des paramètres de la classe :
            <span class="font-bold text-gray-900">{{ $classGroup->full_name }}</span>
@endsection

{{-- @section('breadcrumb')
    <a href="{{ route('classes.index') }}" class="hover:text-gray-700">Classes</a>
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
    </svg>
    <a href="{{ route('classes.show', $classGroup) }}" class="hover:text-gray-700">{{ $classGroup->full_name }}</a>
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
    </svg>
    <span class="font-medium" style="color: #1A3A6B;">Modifier</span>
@endsection --}}

@section('content')

<div class="max-w-5xl"
     x-data="classEditForm(
         {{ json_encode($sections) }},
         '{{ $classGroup->level->section_id }}',
         '{{ $classGroup->level_id }}',
         '{{ old('sub_group', $classGroup->sub_group) }}',
         '{{ old('series',    $classGroup->series)    }}'
     )">

    {{-- Titre --}}
    {{-- <div class="mb-6">
        <h2 class="text-2xl font-extrabold" style="color: #1A3A6B;">Modifier la classe</h2>
        <p class="text-sm text-gray-500 mt-1">
            Mise à jour des paramètres de la classe :
            <span class="font-bold text-gray-700">{{ $classGroup->full_name }}</span>
            ({{ $classGroup->academicYear->label }})
        </p>
    </div> --}}

    <form method="POST" action="{{ route('classes.update', $classGroup) }}">
        @csrf @method('PUT')

        {{-- Nom de classe généré dynamiquement --}}
        <input type="hidden" name="name"         :value="previewName">
        <input type="hidden" name="max_students" value="{{ $classGroup->max_students ?? 60 }}">

        {{-- ═══════════════════════════════════════════════════════════
             LIGNE PRINCIPALE : Colonne gauche (infos) + Colonne droite (aperçu + boutons)
        ════════════════════════════════════════════════════════════════ --}}
        <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">

            {{-- ── COLONNE GAUCHE (3/5) ─────────────────────────────────────── --}}
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
                    </div>{{-- Séparateur --}}
                    <div class="border-t border-gray-100 pt-4">
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-3 flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            Responsable de la classe
                        </p>
                        <select name="titular_staff_id"
                                class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none bg-white"
                                style="color: #1A3A6B;">
                            <option value="">Aucun professeur titulaire</option>
                            @foreach($staffList as $staff)
                            <option value="{{ $staff->id }}"
                                    {{ old('titular_staff_id', $classGroup->titular_staff_id) == $staff->id ? 'selected' : '' }}>
                                {{ $staff->full_name }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                </div>
            </div>

            {{-- ── COLONNE DROITE (2/5) — Aperçu + Boutons ──────────────────── --}}
            <div class="lg:col-span-2 flex flex-col gap-4">

                {{-- Aperçu --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-150 p-6 flex flex-col flex-1" style="min-height: 220px;">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-4">Aperçu du Label</p>

                    {{-- Badge section --}}
                    <div class="mb-4">
                        <p class="text-xs text-gray-500 mb-1.5">Section</p>
                        <div class="px-3 py-1.5 rounded-lg border border-gray-200 text-sm font-semibold text-gray-700 min-h-[36px] flex items-center"
                             x-text="sectionName || '—'"></div>
                    </div>

                    {{-- Badge niveau --}}
                    <div class="flex-1 flex flex-col items-center justify-center rounded-2xl py-6"
                         style="background-color: #EBF3FB;">
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-3">Nom de la classe</p>
                        <div class="bg-white px-6 py-3 rounded-full shadow-sm border border-blue-50 font-black text-xl text-center transition-all"
                             style="color: #1A3A6B;"
                             x-text="previewName || '—'">
                        </div>
                    </div>
                </div>

                {{-- Boutons --}}
                <div class="flex flex-col gap-3">
                    <button type="submit"
                            class="w-full py-3 rounded-xl text-white text-sm font-bold shadow-sm transition-all duration-200 hover:shadow-md hover:brightness-110 active:scale-[0.99] flex items-center justify-center gap-2"
                            style="background-color: #1A3A6B;">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                        </svg>
                        Enregistrer les modifications
                    </button>
                    <a href="{{ route('classes.show', $classGroup) }}"
                       class="w-full py-3 border border-gray-200 rounded-xl text-sm font-semibold text-gray-600 text-center transition-all hover:bg-gray-50">
                        Annuler
                    </a>
                </div>

            </div>
        </div>

    </form>
</div>

<script>
function classEditForm(sections, sectionId, levelId) {
    return {
        sections:        sections,
        selectedSection: sectionId || '',
        selectedLevel:   levelId   || '',

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
            return n;
        }
    }
}
</script>

@endsection
