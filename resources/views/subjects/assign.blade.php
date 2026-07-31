@extends('layouts.app')

@section('title', 'Attribution — ' . $classGroup->full_name)
@section('page-title', 'Attribution des Matières aux Classes')
@section('page-subtitle', 'Gérer les matières et enseignants par classe')

@section('content')

@php
    $typeColors = [
        'general'   => ['bg' => '#DBEAFE', 'text' => '#1D4ED8',
                        'label' => 'GÉNÉRALE'],
        'technical' => ['bg' => '#EDE9FE', 'text' => '#6D28D9',
                        'label' => 'TECHNIQUE'],
        'language'  => ['bg' => '#D1FAE5', 'text' => '#065F46',
                        'label' => 'LANGUE'],
        'sport'     => ['bg' => '#FEF3C7', 'text' => '#92400E',
                        'label' => 'SPORT'],
        'other'     => ['bg' => '#F3F4F6', 'text' => '#374151',
                        'label' => 'AUTRE'],
    ];
@endphp

<div x-data="assignManager({{ $totalCoef }}, {{ $totalHrs ?? 0 }})">

{{-- ══════════════════════════════════════════════════════════════════════ --}}
{{-- BARRE FILTRES + COPIER                                                  --}}
{{-- ══════════════════════════════════════════════════════════════════════ --}}
<div class="flex flex-col sm:flex-row sm:items-end gap-3 mb-5">

    {{-- Filtres Section + Classe + Charger --}}
    <div class="flex-1 bg-white rounded-xl shadow-sm border border-gray-100
                p-4 flex flex-col sm:flex-row sm:items-end gap-3">

        {{-- Section --}}
        <div class="flex-1">
            <label class="block text-xs font-semibold text-gray-400
                           uppercase tracking-wider mb-1">
                Section
            </label>
            <select id="sectionFilter"
                    class="w-full px-3 py-2.5 border border-gray-200 rounded-lg
                           text-sm focus:outline-none bg-white"
                    onchange="filterClasses(this.value)">
                <option value="">Toutes les sections</option>
                @foreach($sections as $section)
                <option value="{{ $section->id }}"
                        {{ $classGroup->level->section_id == $section->id
                            ? 'selected' : '' }}>
                    {{ $section->name }}
                </option>
                @endforeach
            </select>
        </div>

        {{-- Classe --}}
        <div class="flex-1">
            <label class="block text-xs font-semibold text-gray-400
                           uppercase tracking-wider mb-1">
                Classe
            </label>
            <select id="classFilter"
                    class="w-full px-3 py-2.5 border border-gray-200 rounded-lg
                           text-sm focus:outline-none bg-white">
                @foreach($sections as $section)
                    @if(isset($classesBySection[$section->id]))
                        @foreach($classesBySection[$section->id] as $class)
                        <option value="{{ $class->id }}"
                                data-section="{{ $section->id }}"
                                {{ $classGroup->id == $class->id ? 'selected' : '' }}>
                            {{ $class->full_name }}
                        </option>
                        @endforeach
                    @endif
                @endforeach
            </select>
        </div>

        {{-- Bouton Charger --}}
        <button type="button" onclick="chargerClasse()"
                class="px-5 py-2.5 rounded-lg text-white text-sm font-semibold
                       transition-all hover:shadow-md whitespace-nowrap"
                style="background-color: #1A3A6B;">
            Charger
        </button>
    </div>

    {{-- Bouton Copier --}}
    <button type="button" @click="showCopyModal = true"
            class="flex items-center gap-2 px-4 py-3 rounded-xl border-2
                   border-dashed text-sm font-medium transition-colors
                   whitespace-nowrap hover:bg-blue-50"
            style="border-color: #1A3A6B; color: #1A3A6B;">
        <svg class="w-4 h-4" fill="none" stroke="currentColor"
             viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round"
                  stroke-width="2"
                  d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6
                     12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2
                     2 0 002 2z"/>
        </svg>
        Copier depuis une autre classe
    </button>
</div>

{{-- ══════════════════════════════════════════════════════════════════════ --}}
{{-- FORMULAIRE PRINCIPAL                                                     --}}
{{-- ══════════════════════════════════════════════════════════════════════ --}}
<form method="POST"
      action="{{ route('subjects.save-assignment', $classGroup) }}"
      id="mainForm">
    @csrf

    {{-- ── TABLE DES MATIÈRES (layout unique responsive) ─────────────────── --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100
                overflow-hidden mb-24">

        {{-- En-tête colonnes (desktop uniquement, visuel) --}}
        <div class="hidden sm:grid sm:grid-cols-10 gap-2 px-5 py-3
                    border-b border-gray-100 text-xs font-semibold
                    text-gray-400 uppercase tracking-wider"
            style="background-color:#F8FAFC;">
            <div class="col-span-3">Matière</div>
            {{-- <div class="col-span-2">Type</div> --}}
            <div class="col-span-1 text-center">Coef.</div>
            <div class="col-span-3">Enseignant assigné</div>
            <div class="col-span-1 text-center">H/Sem</div>
            <div class="col-span-1 text-center">Statut</div>
            <div class="col-span-1"></div>
        </div>

        {{-- Lignes (UN SEUL LAYOUT pour mobile ET desktop) --}}
        <div id="subjectsTable">

            @forelse($classGroup->classSubjects->sortBy('subject.name_fr') as $cs)
            @php
                $tc        = $typeColors[$cs->subject->type] ?? $typeColors['other'];
                $ta        = $cs->teacherAssignments->first();
                $hasGrades = $cs->grades()->count() > 0;
            @endphp

            <div class="border-b border-gray-50 hover:bg-gray-50/30 transition-colors
                        {{ !$cs->is_active ? 'opacity-60 bg-gray-50/50' : '' }}"
                @change="updateTotals()">
                <div class="px-4 sm:px-5 py-4">

                    {{-- Ligne 1 : Nom + Type + Toggle + Supprimer --}}
                    <div class="flex items-start gap-3 mb-3 sm:hidden">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="font-bold text-sm text-gray-800">
                                    {{ $cs->subject->name_fr }}
                                </span>
                                {{-- <span class="px-2 py-0.5 rounded-full text-xs font-bold"
                                    style="background-color:{{ $tc['bg'] }};
                                            color:{{ $tc['text'] }};">
                                    {{ $tc['label'] }}
                                </span> --}}
                                @if($hasGrades)
                                <svg class="w-3.5 h-3.5 text-amber-400"
                                    fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58
                                            9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53
                                            0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0
                                            11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0
                                            002 0V6a1 1 0 00-1-1z"/>
                                </svg>
                                @endif
                            </div>
                        </div>

                        {{-- Toggle + Delete (mobile header) --}}
                        <div class="flex items-center gap-2 flex-shrink-0">
                            <input type="hidden"
                                name="subjects[{{ $cs->subject_id }}][is_active]"
                                value="0">
                            <label class="relative inline-flex cursor-pointer">
                                <input type="checkbox"
                                    name="subjects[{{ $cs->subject_id }}][is_active]"
                                    value="1"
                                    {{ $cs->is_active ? 'checked' : '' }}
                                    {{ $hasGrades ? 'disabled' : '' }}
                                    class="sr-only peer">
                                <div class="w-9 h-5 bg-gray-200 rounded-full peer
                                            peer-checked:bg-green-500
                                            after:content-[''] after:absolute
                                            after:top-[2px] after:left-[2px]
                                            after:bg-white after:rounded-full
                                            after:h-4 after:w-4 after:transition-all
                                            peer-checked:after:translate-x-4">
                                </div>
                            </label>
                            @if(!$hasGrades)
                            <button type="button" onclick="removeRow(this)"
                                    class="p-1 rounded text-gray-300
                                        hover:text-red-500 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862
                                            a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10
                                            V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                            @endif
                        </div>
                    </div>

                    {{-- Layout unifié : grille responsive --}}
                    <div class="grid grid-cols-2 sm:grid-cols-10 gap-3 items-center">

                        {{-- Matière (desktop seulement — mobile = voir au-dessus) --}}
                        <div class="hidden sm:flex sm:col-span-3 items-center gap-2
                                    min-w-0">
                            <div class="min-w-0">
                                <p class="font-bold text-sm text-gray-800 truncate">
                                    {{ $cs->subject->name_fr }}
                                </p>
                            </div>
                            @if($hasGrades)
                            <svg class="w-3.5 h-3.5 text-amber-400 flex-shrink-0"
                                fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58
                                        9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53
                                        0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0
                                        11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0
                                        002 0V6a1 1 0 00-1-1z"/>
                            </svg>
                            @endif
                        </div>

                        {{-- Type (desktop) --}}
                        {{-- <div class="hidden sm:flex sm:col-span-2 items-center">
                            <span class="px-2.5 py-1 rounded-full text-xs font-bold"
                                style="background-color:{{ $tc['bg'] }};
                                        color:{{ $tc['text'] }};">
                                {{ $tc['label'] }}
                            </span>
                        </div> --}}

                        {{-- Coefficient --}}
                        <div class="col-span-1 sm:col-span-1">
                            <label class="block text-xs text-gray-400 mb-1 sm:hidden">
                                Coefficient
                            </label>
                            <input type="text" inputmode="decimal"
                                name="subjects[{{ $cs->subject_id }}][coefficient]"
                                value="{{ $cs->coefficient }}"
                                pattern="[0-9]+([.,][0-9]+)?"
                                @input="updateTotals()"
                                class="w-full px-2 py-2 border border-gray-200
                                        rounded-lg text-sm text-center font-semibold
                                        focus:outline-none focus:ring-2
                                        focus:ring-blue-100"
                                style="color:#1A3A6B;">
                            <input type="hidden"
                                name="subjects[{{ $cs->subject_id }}][subject_id]"
                                value="{{ $cs->subject_id }}">
                        </div>

                        {{-- Enseignant --}}
                        <div class="col-span-2 sm:col-span-3">
                            <label class="block text-xs text-gray-400 mb-1 sm:hidden">
                                Enseignant
                            </label>
                            <select name="teachers[{{ $cs->id }}]"
                                    class="w-full px-3 py-2 border border-gray-200
                                        rounded-lg text-sm focus:outline-none
                                        focus:ring-2 focus:ring-blue-100 bg-white">
                                <option value="">— Non assigné —</option>
                                @foreach($teachers as $teacher)
                                <option value="{{ $teacher->id }}"
                                        {{ $ta?->staff_id == $teacher->id
                                            ? 'selected' : '' }}>
                                    {{ $teacher->full_name }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Heures/semaine --}}
                        <div class="col-span-1 sm:col-span-1">
                            <label class="block text-xs text-gray-400 mb-1 sm:hidden">
                                H/Semaine
                            </label>
                            <div class="flex items-center gap-1">
                                <input type="number"
                                    name="subjects[{{ $cs->subject_id }}][hours_per_week]"
                                    value="{{ $cs->hours_per_week }}"
                                    step="0.5" min="0.5" placeholder="—"
                                    @input="updateTotals()"
                                    class="w-full px-2 py-2 border border-gray-200
                                            rounded-lg text-sm text-center
                                            focus:outline-none">
                                <span class="text-xs text-gray-400 hidden sm:inline">h</span>
                            </div>
                        </div>

                        {{-- Statut (desktop) --}}
                        <div class="hidden sm:flex sm:col-span-1 justify-center">
                            <input type="hidden"
                                name="subjects[{{ $cs->subject_id }}][is_active]"
                                value="0">
                            <label class="relative inline-flex cursor-pointer">
                                <input type="checkbox"
                                    name="subjects[{{ $cs->subject_id }}][is_active]"
                                    value="1"
                                    {{ $cs->is_active ? 'checked' : '' }}
                                    {{ $hasGrades ? 'disabled' : '' }}
                                    class="sr-only peer">
                                <div class="w-10 h-5 bg-gray-200 rounded-full peer
                                            peer-checked:bg-green-500
                                            after:content-[''] after:absolute
                                            after:top-[2px] after:left-[2px]
                                            after:bg-white after:rounded-full
                                            after:h-4 after:w-4 after:transition-all
                                            peer-checked:after:translate-x-5">
                                </div>
                            </label>
                        </div>

                        {{-- Supprimer (desktop) --}}
                        <div class="hidden sm:flex sm:col-span-1 justify-center">
                            @if(!$hasGrades)
                            <button type="button" onclick="removeRow(this)"
                                    class="p-1.5 rounded-lg text-gray-300
                                        hover:text-red-500 hover:bg-red-50
                                        transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862
                                            a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10
                                            V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                            @else
                            <span class="p-1.5 text-gray-200"
                                title="Notes existantes">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0
                                            00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm
                                            10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                            </span>
                            @endif
                        </div>

                    </div>
                </div>
            </div>

            @empty
            <div class="px-5 py-12 text-center text-gray-400" id="emptyState">
                <p class="text-sm">Aucune matière assignée à cette classe.</p>
                <p class="text-xs mt-1">
                    Utilisez le bouton
                    <strong style="color:#1A5C2A;">+</strong>
                    pour ajouter des matières.
                </p>
            </div>
            @endforelse

        </div>{{-- subjectsTable --}}

        {{-- Ajouter une matière --}}
        @if($availableSubjects->isNotEmpty())
        <div class="border-t border-dashed border-gray-200">
            <div id="addSubjectPanel" class="hidden px-5 py-4 bg-blue-50/30">
                <div class="flex flex-wrap items-end gap-3">
                    <div class="flex-1 min-w-48">
                        <label class="block text-xs font-medium text-gray-600 mb-1">
                            Matière
                        </label>
                        <select id="newSubjectSelect"
                                class="w-full px-3 py-2.5 border border-gray-200
                                    rounded-lg text-sm focus:outline-none bg-white">
                            <option value="">Sélectionner...</option>
                            @foreach($availableSubjects->groupBy('category.name_fr')
                                as $catName => $subs)
                            <optgroup label="{{ $catName }}">
                                @foreach($subs as $sub)
                                <option value="{{ $sub->id }}"
                                        data-name="{{ $sub->name_fr }}"
                                        {{-- data-code="{{ $sub->code }}" --}}
                                        data-type="{{ $sub->type }}">
                                    {{ $sub->name_fr }}
                                </option>
                                @endforeach
                            </optgroup>
                            @endforeach
                        </select>
                    </div>
                    <div class="w-20">
                        <label class="block text-xs font-medium text-gray-600 mb-1">
                            Coef.
                        </label>
                        <input type="text" inputmode="decimal" id="newCoef" value="2" pattern="[0-9]+([.,][0-9]+)?"
                            class="w-full px-3 py-2.5 border border-gray-200
                                    rounded-lg text-sm text-center focus:outline-none">
                    </div>
                    <div class="w-20">
                        <label class="block text-xs font-medium text-gray-600 mb-1">
                            H/Sem
                        </label>
                        <input type="number" id="newHours" step="0.5" min="0.5"
                            placeholder="—"
                            class="w-full px-3 py-2.5 border border-gray-200
                                    rounded-lg text-sm text-center focus:outline-none">
                    </div>
                    <button type="button" onclick="confirmAddSubject()"
                            class="px-4 py-2.5 rounded-lg text-white text-sm
                                font-medium"
                            style="background-color:#1A5C2A;">
                        Ajouter
                    </button>
                    <button type="button"
                            onclick="document.getElementById('addSubjectPanel')
                                    .classList.add('hidden')"
                            class="px-4 py-2.5 rounded-lg text-gray-600 text-sm
                                border border-gray-200 hover:bg-gray-50">
                        Annuler
                    </button>
                </div>
            </div>
        </div>
        @endif

    </div>{{-- fin TABLE --}}
    {{-- TABLE --}}
    
    {{-- ── BARRE STICKY DU BAS ───────────────────────────────────────────── --}}
    <div class="fixed bottom-0 left-0 md:left-64 right-0 z-30
                bg-white border-t border-gray-200 shadow-lg
                px-5 py-3.5 flex flex-col sm:flex-row
                sm:items-center justify-between gap-3">

        <div class="text-sm font-medium text-gray-600">
            <span class="font-bold" style="color:#1A3A6B;">
                Résumé de la configuration
            </span>
            <span class="mx-2 text-gray-300">—</span>
            Totaux :
            <span class="font-bold" style="color:#E87722;"
                  x-text="totalHours + ' h/semaine'">
                {{ $totalHrs }}h/semaine
            </span>
            <span class="mx-1 text-gray-300">|</span>
            Coef. total :
            <span class="font-bold" style="color:#1A5C2A;"
                  x-text="totalCoef">
                {{ $totalCoef }}
            </span>
        </div>

        <div class="flex items-center gap-2 flex-shrink-0">
            <a href="{{ route('classes.show', $classGroup) }}"
               class="px-4 py-2 rounded-lg border border-gray-200 text-sm
                      font-medium text-gray-600 hover:bg-gray-50">
                Annuler
            </a>
            <button type="button"
                    onclick="if(confirm('Réinitialiser ?')) location.reload()"
                    class="px-4 py-2 rounded-lg border text-sm font-medium"
                    style="border-color:#1A3A6B; color:#1A3A6B;">
                Réinitialiser
            </button>
            <button type="submit"
                    class="px-5 py-2 rounded-lg text-white text-sm font-bold
                           transition-all hover:shadow-md"
                    style="background-color:#E87722;">
                Enregistrer les modifications
            </button>
        </div>
    </div>

</form>

{{-- ══════════════════════════════════════════════════════════════════════ --}}
{{-- FAB — Ajouter une matière                                               --}}
{{-- ══════════════════════════════════════════════════════════════════════ --}}
@if($availableSubjects->isNotEmpty())
<div class="fixed bottom-20 right-6 z-40"
     x-data="{ showFab: false, fabHovered: false }">

    {{-- Panel ajout (visible quand showFab = true) --}}
    {{-- <div x-show="showFab" x-transition
         class="absolute bottom-16 right-0 w-80 bg-white rounded-2xl
                shadow-xl border border-gray-200 p-4 mb-2">
        <div class="flex items-center justify-between mb-3">
            <h4 class="font-semibold text-sm" style="color:#1A3A6B;">
                Ajouter une matière
            </h4>
            <button @click="showFab = false"
                    class="text-gray-400 hover:text-gray-600">
                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                     viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="space-y-3">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">
                    Matière
                </label>
                <select id="newSubjectSelect"
                        class="w-full px-3 py-2 border border-gray-200
                               rounded-lg text-sm focus:outline-none bg-white">
                    <option value="">Sélectionner...</option>
                    @foreach($availableSubjects->groupBy('category.name_fr')
                        as $catName => $subs)
                    <optgroup label="{{ $catName }}">
                        @foreach($subs as $sub)
                        <option value="{{ $sub->id }}"
                                data-name="{{ $sub->name_fr }}"
                                data-code="{{ $sub->code }}"
                                data-type="{{ $sub->type }}">
                            {{ $sub->code }} — {{ $sub->name_fr }}
                        </option>
                        @endforeach
                    </optgroup>
                    @endforeach
                </select>
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">
                        Coefficient
                    </label>
                    <input type="text" inputmode="decimal" id="newCoef" value="2" pattern="[0-9]+([.,][0-9]+)?"
                           class="w-full px-3 py-2 border border-gray-200
                                  rounded-lg text-sm text-center focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">
                        H/Semaine
                    </label>
                    <input type="number" id="newHours" step="0.5" min="0.5"
                           placeholder="—"
                           class="w-full px-3 py-2 border border-gray-200
                                  rounded-lg text-sm text-center focus:outline-none">
                </div>
            </div>
            <button type="button" onclick="addNewSubjectRow()"
                    class="w-full py-2 rounded-lg text-white text-sm font-semibold"
                    style="background-color:#1A5C2A;">
                Ajouter à la liste
            </button>
        </div>
    </div> --}}

    {{-- FAB button --}}
    <button type="button"
            @click="showFab = !showFab;
                if(showFab) document.getElementById('addSubjectPanel')
                    .classList.remove('hidden');
                else document.getElementById('addSubjectPanel')
                    .classList.add('hidden');"
            {{-- @click="showFab = !showFab" --}}
            @mouseenter="fabHovered = true"
            @mouseleave="fabHovered = false"
            class="flex items-center gap-0 h-12 rounded-full shadow-lg
                   text-white transition-all duration-300 overflow-hidden
                   hover:shadow-xl"
            :class="fabHovered || showFab ? 'px-4 gap-2' : 'px-3.5'"
            style="background-color:#1A5C2A; min-width: 3rem;">
        <svg class="w-5 h-5 flex-shrink-0 transition-transform duration-300"
             :class="showFab ? 'rotate-45' : ''"
             fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round"
                  stroke-width="2.5" d="M12 4v16m8-8H4"/>
        </svg>
        <span class="text-sm font-semibold whitespace-nowrap overflow-hidden
                     transition-all duration-300"
              :class="fabHovered || showFab ? 'max-w-40 opacity-100' : 'max-w-0 opacity-0'">
            Ajouter une matière
        </span>
    </button>
</div>
@endif

{{-- ══════════════════════════════════════════════════════════════════════ --}}
{{-- MODAL COPIER                                                             --}}
{{-- ══════════════════════════════════════════════════════════════════════ --}}
<div x-show="showCopyModal"
     class="fixed inset-0 z-50 flex items-center justify-center p-4"
     style="background:rgba(0,0,0,0.5);"
     @click.self="showCopyModal = false">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md" @click.stop>
        <div class="px-6 py-5 border-b border-gray-100 flex items-center
                    justify-between">
            <h3 class="font-bold" style="color:#1A3A6B;">
                Copier depuis une autre classe
            </h3>
            <button @click="showCopyModal = false"
                    class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor"
                     viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <form method="POST"
              action="{{ route('subjects.copy-from-class', $classGroup) }}">
            @csrf
            <div class="px-6 py-5 space-y-4">
                <p class="text-sm text-gray-500">
                    Les matières de la classe source seront ajoutées à
                    <strong>{{ $classGroup->full_name }}</strong>.
                    Les matières déjà assignées ne seront pas dupliquées.
                </p>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Classe source <span class="text-red-500">*</span>
                    </label>
                    @if($otherClasses->isEmpty())
                    <p class="text-sm text-gray-400 italic">
                        Aucune autre classe dans cette année.
                    </p>
                    @else
                    <select name="source_class_id"
                            class="w-full px-3 py-2.5 border border-gray-200
                                   rounded-lg text-sm focus:outline-none bg-white">
                        <option value="">Sélectionner...</option>
                        @foreach($otherClasses->groupBy('level.section.name')
                            as $sName => $cls)
                        <optgroup label="{{ $sName }}">
                            @foreach($cls as $c)
                            <option value="{{ $c->id }}">
                                {{ $c->full_name }}
                                ({{ $c->classSubjects->count() }} matières)
                            </option>
                            @endforeach
                        </optgroup>
                        @endforeach
                    </select>
                    @endif
                </div>
                <label class="flex items-center gap-2 cursor-pointer text-sm
                               text-gray-600">
                    <input type="checkbox" name="copy_teachers" value="1"
                           checked style="accent-color:#1A3A6B;">
                    Copier aussi les enseignants assignés
                </label>
            </div>
            <div class="px-6 py-4 border-t border-gray-100 flex justify-end gap-3">
                <button type="button" @click="showCopyModal = false"
                        class="px-4 py-2 border border-gray-200 rounded-lg
                               text-sm text-gray-600 hover:bg-gray-50">
                    Annuler
                </button>
                <button type="submit"
                        {{ $otherClasses->isEmpty() ? 'disabled' : '' }}
                        class="px-4 py-2 rounded-lg text-white text-sm font-semibold"
                        style="background-color:#1A3A6B;">
                    Copier les matières
                </button>
            </div>
        </form>
    </div>
</div>

</div>{{-- end x-data --}}

<script>
// ── Alpine manager ───────────────────────────────────────────────────────────
function assignManager(initCoef, initHours) {
    return {
        showCopyModal: false,
        totalCoef:  initCoef,
        totalHours: initHours,

        // init() {
        //     // Désactiver les champs mobile avant soumission pour éviter
        //     // d'écraser les valeurs du tableau desktop
        //     const form = document.getElementById('mainForm');
        //     if (form) {
        //         form.addEventListener('submit', () => {
        //             document.querySelectorAll('[data-mobile-row] input, [data-mobile-row] select')
        //                 .forEach(el => el.disabled = true);
        //         });
        //     }
        // },

        updateTotals() {
            let coef = 0, hours = 0;
            document.querySelectorAll(
                'input[name$="[coefficient]"]'
            ).forEach(i => coef  += parseFloat(String(i.value).replace(',', '.')) || 0);
            document.querySelectorAll(
                'input[name$="[hours_per_week]"]'
            ).forEach(i => hours += parseFloat(String(i.value).replace(',', '.')) || 0);
            this.totalCoef  = Math.round(coef * 10) / 10;
            this.totalHours = Math.round(hours * 10) / 10;
        }
    }
}

// ── Filtres section / classe ─────────────────────────────────────────────────
const classesBySection = @json($classesBySection->map(fn($cls) =>
    $cls->map(fn($c) => ['id' => $c->id, 'name' => $c->full_name])
));

function filterClasses(sectionId) {
    const sel = document.getElementById('classFilter');
    Array.from(sel.options).forEach(opt => {
        const sId = opt.getAttribute('data-section');
        opt.hidden = sectionId ? (sId != sectionId) : false;
    });
    const firstVisible = Array.from(sel.options).find(o => !o.hidden && o.value);
    if (firstVisible) sel.value = firstVisible.value;
}

function chargerClasse() {
    const classId = document.getElementById('classFilter').value;
    if (classId) {
        window.location.href = `/subjects/assign/${classId}`;
    }
}

// ── Supprimer une ligne ──────────────────────────────────────────────────────
function removeRow(btn) {
    if (!confirm('Retirer cette matière ?')) return;
    // Remonter jusqu'au div parent de la ligne (border-b)
    const row = btn.closest('[class*="border-b"]');
    if (row) row.remove();
    document.getElementById('mainForm').dispatchEvent(new Event('change'));
}

// ── Ajouter une matière ──────────────────────────────────────────────────────
const typeConfig = {
    general:   { label: 'GÉNÉRALE',  bg: '#DBEAFE', text: '#1D4ED8' },
    technical: { label: 'TECHNIQUE', bg: '#EDE9FE', text: '#6D28D9' },
    language:  { label: 'LANGUE',    bg: '#D1FAE5', text: '#065F46' },
    sport:     { label: 'SPORT',     bg: '#FEF3C7', text: '#92400E' },
    other:     { label: 'AUTRE',     bg: '#F3F4F6', text: '#374151' },
};

// Construire la liste des enseignants depuis le DOM
function getTeacherOptions() {
    const seen = new Set();
    const opts = ['<option value="">— Non assigné —</option>'];
    document.querySelectorAll('select[name^="teachers"] option').forEach(o => {
        if (o.value && !seen.has(o.value)) {
            seen.add(o.value);
            opts.push(`<option value="${o.value}">${o.text}</option>`);
        }
    });
    return opts.join('');
}

function confirmAddSubject() {
    const sel   = document.getElementById('newSubjectSelect');
    const coef  = String(document.getElementById('newCoef').value || 2).replace(',', '.');
    const hours = document.getElementById('newHours').value || '';

    if (!sel.value) { alert('Sélectionnez une matière.'); return; }

    const opt  = sel.options[sel.selectedIndex];
    const id   = sel.value;
    const name = opt.getAttribute('data-name');
    const type = opt.getAttribute('data-type') || 'other';
    const tc   = typeConfig[type];

    const div = document.createElement('div');
    div.className = 'border-b border-gray-50 hover:bg-gray-50/30 bg-green-50/20';
    div.innerHTML = `
        <div class="px-4 sm:px-5 py-4">
            {{-- Mobile: nom + type + toggle + delete --}}
            <div class="flex items-start gap-3 mb-3 sm:hidden">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="font-bold text-sm text-gray-800">${name}</span>
                        <span class="px-2 py-0.5 rounded-full text-xs font-bold"
                              style="background:${tc.bg};color:${tc.text}">
                            ${tc.label}
                        </span>
                    </div>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0">
                    <label class="relative inline-flex cursor-pointer">
                        <input type="hidden" name="subjects[${id}][is_active]" value="0">
                        <input type="checkbox" name="subjects[${id}][is_active]"
                               value="1" checked class="sr-only peer">
                        <div class="w-9 h-5 bg-gray-200 rounded-full peer
                                    peer-checked:bg-green-500
                                    after:content-[''] after:absolute
                                    after:top-[2px] after:left-[2px]
                                    after:bg-white after:rounded-full
                                    after:h-4 after:w-4 after:transition-all
                                    peer-checked:after:translate-x-4"></div>
                    </label>
                    <button type="button" onclick="removeRow(this)"
                            class="p-1 rounded text-gray-300 hover:text-red-500">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                             viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  stroke-width="2"
                                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862
                                     a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10
                                     V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Grille responsive --}}
            <div class="grid grid-cols-2 sm:grid-cols-12 gap-3 items-center">

                {{-- Matière desktop --}}
                <div class="hidden sm:flex sm:col-span-3 items-center gap-2">
                    <span class="font-bold text-sm text-gray-800">${name}</span>
                </div>

                {{-- Type desktop --}}
                <div class="hidden sm:flex sm:col-span-2">
                    <span class="px-2.5 py-1 rounded-full text-xs font-bold"
                          style="background:${tc.bg};color:${tc.text}">
                        ${tc.label}
                    </span>
                </div>

                {{-- Coefficient --}}
                <div class="col-span-1 sm:col-span-1">
                    <label class="block text-xs text-gray-400 mb-1 sm:hidden">
                        Coefficient
                    </label>
                    <input type="text" inputmode="decimal" name="subjects[${id}][coefficient]"
                           value="${coef}" pattern="[0-9]+([.,][0-9]+)?"
                           class="w-full px-2 py-2 border border-gray-200
                                  rounded-lg text-sm text-center font-semibold
                                  focus:outline-none" style="color:#1A3A6B;">
                    <input type="hidden" name="subjects[${id}][subject_id]"
                           value="${id}">
                </div>

                {{-- Enseignant --}}
                <div class="col-span-2 sm:col-span-3">
                    <label class="block text-xs text-gray-400 mb-1 sm:hidden">
                        Enseignant
                    </label>
                    <select name="teachers[new_${id}]"
                            class="w-full px-3 py-2 border border-gray-200
                                   rounded-lg text-sm focus:outline-none bg-white">
                        <option value="">— Non assigné —</option>
                        ${getTeacherOptions()}
                    </select>
                </div>

                {{-- Heures --}}
                <div class="col-span-1 sm:col-span-1">
                    <label class="block text-xs text-gray-400 mb-1 sm:hidden">
                        H/Semaine
                    </label>
                    <div class="flex items-center gap-1">
                        <input type="number" name="subjects[${id}][hours_per_week]"
                               value="${hours}" step="0.5" min="0.5" placeholder="—"
                               class="w-full px-2 py-2 border border-gray-200
                                      rounded-lg text-sm text-center
                                      focus:outline-none">
                        <span class="text-xs text-gray-400 hidden sm:inline">h</span>
                    </div>
                </div>

                {{-- Statut desktop --}}
                <div class="hidden sm:flex sm:col-span-1 justify-center">
                    <label class="relative inline-flex cursor-pointer">
                        <input type="hidden" name="subjects[${id}][is_active]"
                               value="0">
                        <input type="checkbox" name="subjects[${id}][is_active]"
                               value="1" checked class="sr-only peer">
                        <div class="w-10 h-5 bg-gray-200 rounded-full peer
                                    peer-checked:bg-green-500
                                    after:content-[''] after:absolute
                                    after:top-[2px] after:left-[2px]
                                    after:bg-white after:rounded-full
                                    after:h-4 after:w-4 after:transition-all
                                    peer-checked:after:translate-x-5"></div>
                    </label>
                </div>

                {{-- Supprimer desktop --}}
                <div class="hidden sm:flex sm:col-span-1 justify-center">
                    <button type="button" onclick="removeRow(this)"
                            class="p-1.5 rounded-lg text-gray-300
                                   hover:text-red-500 hover:bg-red-50">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                             viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  stroke-width="2"
                                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862
                                     a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10
                                     V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </div>

            </div>
        </div>
    `;

    document.getElementById('emptyState')?.remove();
    document.getElementById('subjectsTable').appendChild(div);

    // Retirer du select disponible
    sel.remove(sel.selectedIndex);

    // Fermer le panel si plus de matières disponibles
    if (sel.options.length <= 1) {
        document.getElementById('addSubjectPanel').classList.add('hidden');
        const fabEl = document.querySelector('[x-data*="showFab"]');
        if (fabEl?._x_dataStack?.[0]) {
            fabEl._x_dataStack[0].showFab = false;
        }
    }

    sel.value = '';
    document.getElementById('newCoef').value  = 2;
    document.getElementById('newHours').value = '';
}
// function addNewSubjectRow() {
//     const sel   = document.getElementById('newSubjectSelect');
//     const coef  = document.getElementById('newCoef').value || 2;
//     const hours = document.getElementById('newHours').value || '';
//     if (!sel.value) { alert('Sélectionnez une matière.'); return; }

//     const opt  = sel.options[sel.selectedIndex];
//     const id   = sel.value;
//     const name = opt.getAttribute('data-name');
//     const type = opt.getAttribute('data-type') || 'other';
//     const tc   = typeConfig[type];

//     const div  = document.createElement('div');
//     div.className = 'border-b border-gray-50 bg-green-50/20 transition-colors';
//     div.innerHTML = `
//         <table class="w-full hidden md:table">
//             <colgroup>
//                 <col style="width:22%"><col style="width:12%">
//                 <col style="width:10%"><col style="width:26%">
//                 <col style="width:12%"><col style="width:10%">
//                 <col style="width:8%">
//             </colgroup>
//             <tbody>
//                 <tr>
//                     <td class="px-5 py-4">
//                         <span class="font-bold text-sm text-gray-800">${name}</span>
//                     </td>
//                     <td class="px-3 py-4">
//                         <span class="px-2 py-0.5 rounded-full text-xs font-bold"
//                               style="background:${tc.bg};color:${tc.text}">
//                             ${tc.label}
//                         </span>
//                     </td>
//                     <td class="px-3 py-4 text-center">
//                         <input type="number" name="subjects[${id}][coefficient]"
//                                value="${coef}" step="0.5" min="0.5" max="9"
//                                class="w-14 px-2 py-1.5 border border-gray-200
//                                       rounded-lg text-sm text-center font-semibold
//                                       focus:outline-none"
//                                style="color:#1A3A6B">
//                         <input type="hidden" name="subjects[${id}][subject_id]"
//                                value="${id}">
//                     </td>
//                     <td class="px-3 py-4">
//                         <select name="teachers[new_${id}]"
//                                 class="w-full px-3 py-2 border border-gray-200
//                                        rounded-lg text-sm focus:outline-none bg-white">
//                             ${getTeacherOptions()}
//                         </select>
//                     </td>
//                     <td class="px-3 py-4 text-center">
//                         <div class="flex items-center justify-center gap-1">
//                             <input type="number"
//                                    name="subjects[${id}][hours_per_week]"
//                                    value="${hours}" step="0.5" min="0.5"
//                                    placeholder="—"
//                                    class="w-14 px-2 py-1.5 border border-gray-200
//                                           rounded-lg text-sm text-center
//                                           focus:outline-none">
//                             <span class="text-xs text-gray-400">h</span>
//                         </div>
//                     </td>
//                     <td class="px-3 py-4 text-center">
//                         <input type="hidden"
//                                name="subjects[${id}][is_active]" value="0">
//                         <label class="relative inline-flex items-center cursor-pointer">
//                             <input type="checkbox" name="subjects[${id}][is_active]"
//                                    value="1" checked class="sr-only peer">
//                             <div class="w-10 h-5 bg-gray-200 rounded-full peer
//                                         peer-checked:bg-green-500
//                                         after:content-[''] after:absolute
//                                         after:top-[2px] after:left-[2px]
//                                         after:bg-white after:rounded-full
//                                         after:h-4 after:w-4 after:transition-all
//                                         peer-checked:after:translate-x-5"></div>
//                         </label>
//                     </td>
//                     <td class="px-3 py-4 text-center">
//                         <button type="button" onclick="removeRow(this)"
//                                 class="p-1.5 rounded-lg text-gray-300
//                                        hover:text-red-500 hover:bg-red-50">
//                             <svg class="w-4 h-4" fill="none" stroke="currentColor"
//                                  viewBox="0 0 24 24">
//                                 <path stroke-linecap="round" stroke-linejoin="round"
//                                       stroke-width="2"
//                                       d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862
//                                          a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10
//                                          V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
//                             </svg>
//                         </button>
//                     </td>
//                 </tr>
//             </tbody>
//         </table>
//         <div class="md:hidden px-4 py-3 space-y-2">
//             <div class="flex items-center gap-2">
//                 <span class="font-bold text-sm text-gray-800">${name}</span>
//                 <span class="px-2 py-0.5 rounded-full text-xs font-bold"
//                       style="background:${tc.bg};color:${tc.text}">${tc.label}</span>
//             </div>
//             <div class="grid grid-cols-2 gap-2">
//                 <div>
//                     <label class="text-xs text-gray-400">Coefficient</label>
//                     <input type="number" name="subjects[${id}][coefficient]"
//                            value="${coef}" step="0.5" min="0.5" max="9"
//                            class="w-full px-3 py-2 border border-gray-200
//                                   rounded-lg text-sm text-center focus:outline-none">
//                     <input type="hidden" name="subjects[${id}][subject_id]"
//                            value="${id}">
//                 </div>
//                 <div>
//                     <label class="text-xs text-gray-400">H/Sem</label>
//                     <input type="number" name="subjects[${id}][hours_per_week]"
//                            value="${hours}" step="0.5" min="0.5" placeholder="—"
//                            class="w-full px-3 py-2 border border-gray-200
//                                   rounded-lg text-sm text-center focus:outline-none">
//                 </div>
//             </div>
//         </div>
//     `;

//     document.getElementById('emptyState')?.remove();
//     document.getElementById('subjectsTable').appendChild(div);

//     // Retirer du select d'options disponibles
//     sel.remove(sel.selectedIndex);
//     if (sel.options.length <= 1) {
//         // Fermer le FAB panel
//         const fabEl = document.querySelector('[x-data*="showFab"]');
//         if (fabEl?.__x) fabEl.__x.$data.showFab = false;
//     }

//     sel.value = '';
//     document.getElementById('newCoef').value  = 2;
//     document.getElementById('newHours').value = '';
// }
</script>

@endsection