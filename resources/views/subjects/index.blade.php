@extends('layouts.app')

@section('title', 'Matières')
@section('page-title', 'Matières')
@section('page-subtitle', 'Catalogue des matières enseignées')

@section('content')

<style>
    label[for="category-name-fr"],
    label[for="category-name-en"] { display: none; }
    form[x-show="editCategory"] div:has(input[name="name_fr"]),
    form[x-show="editCategory"] div:has(input[name="name_en"]) { display: none; }
</style>

<div x-data="{
    tab: '{{ request('tab', 'all') }}',
    fabOpen: false,
    categoryModal: {{ $errors->has('section_id') || $errors->has('code') || $errors->has('name') ? 'true' : 'false' }},
    categoryListModal: {{ request()->boolean('categories') ? 'true' : 'false' }},
    editCategory: null,
    categories: @js($categories->map(fn ($category) => ['id' => $category->id, 'section_id' => $category->section_id, 'section_name' => $category->section?->name, 'code' => $category->code, 'name' => $category->name, 'subjects_count' => $category->subjects_count])->values()),
    openEditor(category) { this.editCategory = { ...category }; this.categoryListModal = false; }
}">

{{-- ── BARRE PRINCIPALE ────────────────────────────────────────────────── --}}
<div class="flex flex-col sm:flex-row sm:items-center
            justify-between gap-4 mb-5">

    {{-- Onglets + filtre --}}
    <div class="flex items-center gap-1 bg-white rounded-xl
                shadow-sm border border-gray-100 p-1">
        @foreach([
            ['key' => 'all',     'label' => 'Toutes les matières'],
            ['key' => 'section', 'label' => 'Par section'],
            ['key' => 'assign',  'label' => 'Attribution aux classes'],
        ] as $t)
        <button @click="tab = '{{ $t['key'] }}'"
                :class="tab === '{{ $t['key'] }}'
                    ? 'bg-gray-100 text-gray-800 font-semibold'
                    : 'text-gray-500 hover:text-gray-700'"
                class="px-4 py-2 rounded-lg text-sm transition-colors
                       whitespace-nowrap">
            {{ $t['label'] }}
        </button>
        @endforeach
    </div>

    <div class="flex items-center gap-3 flex-wrap justify-end">
        {{-- Filtre type --}}
        <div x-show="tab === 'all'" class="flex items-center gap-2">
            <span class="text-xs font-semibold text-gray-400 uppercase
                         tracking-wider whitespace-nowrap">
                Filtrer par :
            </span>
            <form method="GET" action="{{ route('subjects.index') }}">
                <select name="type" onchange="this.form.submit()"
                        class="px-3 py-2 border border-gray-200 rounded-lg
                               text-sm focus:outline-none bg-white">
                    <option value="">Type (Tous)</option>
                    @foreach([
                        'general'   => 'Générale',
                        'technical' => 'Technique',
                        'language'  => 'Langue',
                        // 'sport'     => 'Sport',
                        'other'     => 'Autre',
                    ] as $val => $lbl)
                    <option value="{{ $val }}"
                            {{ request('type') === $val ? 'selected' : '' }}>
                        {{ $lbl }}
                    </option>
                    @endforeach
                </select>
            </form>
        </div>

        <div x-show="tab === 'section'" class="flex items-center gap-2">
            <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider whitespace-nowrap">
                Filtrer par :
            </span>
            <form method="GET" action="{{ route('subjects.index') }}" class="flex items-center gap-2">
                <input type="hidden" name="tab" value="section">
                <select name="section_id" onchange="this.form.submit()"
                        class="px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none bg-white min-w-[180px]">
                    <option value="">Toutes les sections</option>
                    @foreach($sections as $section)
                        <option value="{{ $section->id }}" {{ request('section_id') == $section->id ? 'selected' : '' }}>
                            {{ $section->name }}
                        </option>
                    @endforeach
                </select>
            </form>
        </div>

        <div x-show="tab === 'assign'" class="flex items-center gap-2">
            <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider whitespace-nowrap">
                Filtrer par :
            </span>
            <form method="GET" action="{{ route('subjects.index') }}" class="flex items-center gap-2">
                <input type="hidden" name="tab" value="assign">
                <select name="section_id" onchange="this.form.submit()"
                        class="px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none bg-white min-w-[180px]">
                    <option value="">Toutes les sections</option>
                    @foreach($sections as $section)
                        <option value="{{ $section->id }}" {{ request('section_id') == $section->id ? 'selected' : '' }}>
                            {{ $section->name }}
                        </option>
                    @endforeach
                </select>
            </form>
        </div>

        {{-- Gestion des catégories --}}
        @can('manage-subjects')
        <button type="button" @click="categoryListModal = true"
                class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-bold text-gray-700 transition hover:border-[#1A3A6B]/30 hover:bg-blue-50 whitespace-nowrap">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            Catégories
        </button>
        @endcan
    </div>
</div>

<div x-show="categoryModal" x-cloak
     x-transition.opacity
     @keydown.escape.window="categoryModal = false"
     class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/40 p-4">
    <div @click.outside="categoryModal = false" class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-lg font-black text-gray-900">Nouvelle catégorie</h2>
                <p class="mt-1 text-sm text-gray-500">Elle sera disponible immédiatement dans les formulaires de matières.</p>
            </div>
            <button type="button" @click="categoryModal = false" class="rounded-lg p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-700" aria-label="Fermer">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form method="POST" action="{{ route('subjects.categories.store') }}" class="mt-6 space-y-4">
            @csrf
            <div>
                <label for="category-name-fr" class="mb-1.5 block text-sm font-semibold text-gray-700">Nom en français</label>
                <label for="category-section" class="mb-1.5 block text-sm font-semibold text-gray-700">Section</label>
                <select id="category-section" name="section_id" required class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm text-gray-800 outline-none transition focus:border-[#1A3A6B] focus:ring-2 focus:ring-blue-100">
                    <option value="">Selectionner une section</option>
                    @foreach($sections as $section)
                        <option value="{{ $section->id }}" {{ old('section_id') == $section->id ? 'selected' : '' }}>{{ $section->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="category-name-en" class="mb-1.5 block text-sm font-semibold text-gray-700">Nom en anglais <span class="font-normal text-gray-400">(facultatif)</span></label>
                <label for="category-code" class="mb-1.5 block text-sm font-semibold text-gray-700">Code</label>
                <input id="category-code" name="code" required maxlength="30" value="{{ old('code') }}" placeholder="Ex: SCI" class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm text-gray-800 outline-none transition focus:border-[#1A3A6B] focus:ring-2 focus:ring-blue-100">
                <label for="category-name" class="mb-1.5 mt-4 block text-sm font-semibold text-gray-700">Nom de la categorie</label>
                <input id="category-name" name="name" required maxlength="100" value="{{ old('name') }}" class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm text-gray-800 outline-none transition focus:border-[#1A3A6B] focus:ring-2 focus:ring-blue-100">
            </div>
            <div class="flex justify-end gap-3 border-t border-gray-100 pt-5">
                <button type="button" @click="categoryModal = false" class="rounded-xl px-4 py-2.5 text-sm font-bold text-gray-600 transition hover:bg-gray-100">Annuler</button>
                <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-[#1A3A6B] px-4 py-2.5 text-sm font-bold text-white transition hover:bg-[#163450]"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>Créer la catégorie</button>
            </div>
        </form>
    </div>
</div>

{{-- ════════════════════════════════════════════════════════════════════ --}}
{{-- ONGLET : TOUTES LES MATIÈRES                                         --}}
{{-- ════════════════════════════════════════════════════════════════════ --}}
<div x-show="tab === 'all'" x-transition>

    {{-- Recherche --}}
    <form method="GET" action="{{ route('subjects.index') }}"
          class="mb-4 flex gap-3">
        <div class="relative flex-1 max-w-sm">
            <input type="text" name="search"
                   value="{{ request('search') }}"
                   placeholder="Rechercher une matière..."
                   class="w-full px-4 pr-11 py-2 border border-gray-200
                          rounded-lg text-sm focus:outline-none
                          focus:ring-2 focus:ring-blue-200 bg-white">
            <button type="submit" class="absolute inset-y-0 right-0 flex w-10 items-center justify-center text-gray-500 transition hover:text-[#1A3A6B]" aria-label="Rechercher">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </button>
        </div>
        @if(request()->hasAny(['search','type']))
        <a href="{{ route('subjects.index') }}"
           class="px-3 py-2 border border-gray-200 rounded-lg text-sm
                  text-gray-500 hover:bg-gray-50">
            <svg class="inline h-4 w-4 align-[-2px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </a>
        @endif
    </form>

    {{-- Table principale --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100
                overflow-hidden">
        @if($subjects->isEmpty())
        <div class="p-12 text-center text-gray-400">
            <p class="text-sm">Aucune matière trouvée.</p>
            @can('manage-subjects')
            <a href="{{ route('subjects.create') }}"
               class="inline-block mt-3 text-sm font-medium hover:underline"
               style="color: #1A5C2A;">
                + Créer la première matière
            </a>
            @endcan
        </div>
        @else

        {{-- Desktop --}}
        <div class="hidden lg:block overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr style="background-color: #F8FAFC;"
                        class="border-b border-gray-100">
                        {{-- <th class="text-left px-5 py-3.5 text-xs font-semibold
                                   text-gray-400 uppercase tracking-wider">
                            Code
                        </th> --}}
                        <th class="text-left px-5 py-3.5 text-xs font-semibold
                                   text-gray-400 uppercase tracking-wider">
                            Nom de la matière
                        </th>
                        {{-- <th class="text-left px-5 py-3.5 text-xs font-semibold
                                   text-gray-400 uppercase tracking-wider">
                            Type
                        </th> --}}
                        <th class="text-left px-5 py-3.5 text-xs font-semibold
                                   text-gray-400 uppercase tracking-wider">
                            Sections concernées
                        </th>
                        {{-- <th class="text-center px-5 py-3.5 text-xs font-semibold
                                   text-gray-400 uppercase tracking-wider">
                            Coeff. moy.
                        </th> --}}
                        <th class="text-left px-5 py-3.5 text-xs font-semibold
                                   text-gray-400 uppercase tracking-wider">
                            Enseignants
                        </th>
                        <th class="text-right px-5 py-3.5 text-xs font-semibold
                                   text-gray-400 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($subjects as $subject)
                    @php
                        $typeColors = [
                            'general'   => ['bg' => '#DBEAFE', 'text' => '#1D4ED8',
                                            'label' => 'GÉNÉRALE'],
                            'technical' => ['bg' => '#EDE9FE', 'text' => '#6D28D9',
                                            'label' => 'TECHNIQUE'],
                            'language'  => ['bg' => '#D1FAE5', 'text' => '#065F46',
                                            'label' => 'LANGUE'],
                            // 'sport'     => ['bg' => '#FEF3C7', 'text' => '#92400E',
                            //                 'label' => 'SPORT'],
                            'other'     => ['bg' => '#F3F4F6', 'text' => '#374151',
                                            'label' => 'AUTRE'],
                        ];
                        $tc = $typeColors[$subject->type] ?? $typeColors['other'];

                        // Sections concernées
                        $categorySection = $subject->category?->section;

                        // Enseignants assignés (uniques)
                        $teachers = collect();
                        foreach ($subject->classSubjects as $cs) {
                            foreach ($cs->teacherAssignments as $ta) {
                                if ($ta->staff) {
                                    $teachers->push($ta->staff);
                                }
                            }
                        }
                        $teachers = $teachers->unique('id')->values();

                        // Coefficient moyen
                        $avgCoef = $subject->classSubjects->avg('coefficient');
                    @endphp
                    <tr class="hover:bg-gray-50/50 transition-colors">

                        {{-- Code --}}
                        {{-- <td class="px-5 py-4">
                            <span class="font-mono font-bold text-sm"
                                  style="color: #1A3A6B;">
                                {{ $subject->code }}
                            </span>
                        </td> --}}

                        {{-- Nom --}}
                        <td class="px-5 py-4">
                            <div>
                                <p class="text-sm font-medium text-gray-800">
                                    {{ $subject->name_fr }}
                                </p>
                                @if($subject->name_en)
                                <p class="text-xs text-gray-400">
                                    {{ $subject->name_en }}
                                </p>
                                @endif
                            </div>
                        </td>

                        {{-- Type --}}
                        {{-- <td class="px-5 py-4">
                            <span class="px-2.5 py-1 rounded-full text-xs
                                         font-bold"
                                  style="background-color: {{ $tc['bg'] }};
                                         color: {{ $tc['text'] }};">
                                {{ $tc['label'] }}
                            </span>
                        </td> --}}

                        {{-- Sections --}}
                        <td class="px-5 py-4">
                            <div class="flex flex-wrap gap-1">
                                @if($categorySection)
                                @php
                                    $secColors = [
                                        'FG'  => ['bg' => '#DBEAFE',
                                                  'text' => '#1D4ED8',
                                                  'label' => 'FR. GÉ'],
                                        'FT'  => ['bg' => '#EDE9FE',
                                                  'text' => '#6D28D9',
                                                  'label' => 'FR. TECH'],
                                        'ANG' => ['bg' => '#FEE2E2',
                                                  'text' => '#991B1B',
                                                  'label' => 'ANGLO'],
                                    ];
                                    $sc = $secColors[$categorySection->code]
                                        ?? ['bg' => '#F3F4F6',
                                            'text' => '#374151',
                                            'label' => $categorySection->code];
                                @endphp
                                <span class="px-1.5 py-0.5 rounded text-xs
                                             font-semibold"
                                      style="background-color: {{ $sc['bg'] }};
                                             color: {{ $sc['text'] }};">
                                    {{ $categorySection->name }}
                                </span>
                                @else
                                <span class="text-xs text-gray-300 italic">
                                    Sans section
                                </span>
                                @endif
                            </div>
                        </td>

                        {{-- Coeff moyen --}}
                        {{-- <td class="px-5 py-4 text-center">
                            @if($avgCoef)
                            <span class="font-bold text-sm"
                                  style="color: #1A3A6B;">
                                {{ round($avgCoef, 1) }}
                            </span>
                            @else
                            <span class="text-gray-300">—</span>
                            @endif
                        </td> --}}

                        {{-- Enseignants (avatars) --}}
                        <td class="px-5 py-4">
                            @if($teachers->isNotEmpty())
                            <div class="flex items-center gap-2">
                                <div class="flex -space-x-1.5">
                                    @foreach($teachers->take(3) as $t)
                                    <div class="w-7 h-7 rounded-full flex
                                                items-center justify-center
                                                text-white text-xs font-bold
                                                ring-2 ring-white flex-shrink-0"
                                         style="background-color: #1A3A6B;"
                                         title="{{ $t->full_name }}">
                                        {{ strtoupper(substr($t->last_name, 0, 1))
                                           . strtoupper(substr($t->first_name, 0, 1)) }}
                                    </div>
                                    @endforeach
                                    @if($teachers->count() > 3)
                                    <div class="w-7 h-7 rounded-full flex
                                                items-center justify-center
                                                text-xs font-bold ring-2 ring-white"
                                         style="background-color: #E5E7EB;
                                                color: #374151;">
                                        +{{ $teachers->count() - 3 }}
                                    </div>
                                    @endif
                                </div>
                                <span class="text-xs text-gray-400">
                                    {{ $teachers->count() }} ens.
                                </span>
                            </div>
                            @else
                            <span class="text-xs text-gray-300 italic">
                                Non assigné
                            </span>
                            @endif
                        </td>

                        {{-- Actions --}}
                        <td class="px-5 py-4 text-right">
                            <div class="flex items-center justify-end gap-1">
                                @can('manage-subjects')
                                <a href="{{ route('subjects.edit', $subject) }}"
                                   class="p-1.5 rounded-lg text-gray-400
                                          hover:text-blue-600 hover:bg-blue-50
                                          transition-colors">
                                    <svg class="w-4 h-4" fill="none"
                                         stroke="currentColor"
                                         viewBox="0 0 24 24">
                                        <path stroke-linecap="round"
                                              stroke-linejoin="round"
                                              stroke-width="2"
                                              d="M15.232 5.232l3.536 3.536m
                                                 -2.036-5.036a2.5 2.5 0 113.536
                                                 3.536L6.5 21.036H3v-3.572
                                                 L16.732 3.732z"/>
                                    </svg>
                                </a>
                                <form method="POST"
                                      action="{{ route('subjects.destroy',
                                                       $subject) }}"
                                      onsubmit="return confirm(
                                          'Supprimer « {{ $subject->name_fr }} » ?')">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                            class="p-1.5 rounded-lg text-gray-400
                                                   hover:text-red-600
                                                   hover:bg-red-50 transition-colors">
                                        <svg class="w-4 h-4" fill="none"
                                             stroke="currentColor"
                                             viewBox="0 0 24 24">
                                            <path stroke-linecap="round"
                                                  stroke-linejoin="round"
                                                  stroke-width="2"
                                                  d="M19 7l-.867 12.142A2 2 0
                                                     0116.138 21H7.862a2 2 0
                                                     01-1.995-1.858L5 7m5 4v6
                                                     m4-6v6m1-10V4a1 1 0 00-1
                                                     -1h-4a1 1 0 00-1 1v3
                                                     M4 7h16"/>
                                        </svg>
                                    </button>
                                </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Mobile --}}
        <div class="lg:hidden divide-y divide-gray-100">
            @foreach($subjects as $subject)
            @php
                $tc = $typeColors[$subject->type] ?? $typeColors['other'];
            @endphp
            <div class="p-4 flex items-center justify-between gap-3">
                <div class="flex items-center gap-3 min-w-0">
                    {{-- <span class="font-mono font-bold text-sm flex-shrink-0"
                          style="color: #1A3A6B;">
                        {{ $subject->code }}
                    </span> --}}
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-gray-800 truncate">
                            {{ $subject->name_fr }}
                        </p>
                        <span class="px-2 py-0.5 rounded-full text-xs font-bold"
                              style="background-color: {{ $tc['bg'] }};
                                     color: {{ $tc['text'] }};">
                            {{ $tc['label'] }}
                        </span>
                    </div>
                </div>
                @can('manage-subjects')
                <a href="{{ route('subjects.edit', $subject) }}"
                   class="p-1.5 rounded-lg text-gray-400
                          hover:text-blue-600 hover:bg-blue-50 flex-shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor"
                         viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              stroke-width="2"
                              d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5
                                 2.5 0 113.536 3.536L6.5 21.036H3v-3.572
                                 L16.732 3.732z"/>
                    </svg>
                </a>
                @endcan
            </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        @if($subjects->hasPages())
        <div class="px-5 py-3 border-t border-gray-100">
            {{ $subjects->links() }}
        </div>
        @endif
        @endif
    </div>

    {{-- ── BARRE DE STATS (bas de page) ─────────────────────────────────
    {{-- <div class="mt-3 px-4 py-3 bg-white rounded-xl shadow-sm border
                border-gray-100 flex flex-col sm:flex-row sm:items-center
                justify-between gap-2 text-xs text-gray-500">
        <div class="flex items-center gap-4 flex-wrap">
            <span class="flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-gray-400"></span>
                <strong class="text-gray-700">{{ $stats['total'] }}</strong>
                matières au total
            </span>
            <span class="flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full"
                      style="background-color: #1D4ED8;"></span>
                <strong class="text-gray-700">{{ $stats['general'] }}</strong>
                générales
            </span>
            <span class="flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full"
                      style="background-color: #6D28D9;"></span>
                <strong class="text-gray-700">{{ $stats['technical'] }}</strong>
                techniques
            </span>
            <span class="flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full"
                      style="background-color: #065F46;"></span>
                <strong class="text-gray-700">{{ $stats['language'] }}</strong>
                langue(s)
            </span>
            <span>
                <span class="w-2 h-2 rounded-full"
                      style="background-color: red;"></span>
                <strong class="text-gray-700">{{ $stats['other'] }}</strong>
                autre(s)
            </span>
        </div>
        <span class="text-gray-400">
            Dernière mise à jour : {{ now()->format('d/m/Y à H:i') }}
        </span>
    </div> --}}
</div>

{{-- ════════════════════════════════════════════════════════════════════ --}}
{{-- ONGLET : PAR SECTION                                                  --}}
{{-- ════════════════════════════════════════════════════════════════════ --}}
<div x-show="tab === 'section'" x-transition>
    @php
        $filteredSectionData = $sectionData;
        if (request('section_id')) {
            $filteredSectionData = $sectionData->filter(function ($data) {
                return $data['section']->id == request('section_id');
            })->values();
        }
    @endphp
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 pb-20">
        @foreach($filteredSectionData as $data)
        @php
            $section      = $data['section'];
            $sectionSubs  = $data['subjects'];
            $secColors    = [
                'FR'  => ['color' => '#1D4ED8', 'bg' => '#DBEAFE',
                           'border' => '#93C5FD'],
                'ENG'  => ['color' => '#6D28D9', 'bg' => '#EDE9FE',
                           'border' => '#C4B5FD'],
                'EN' => ['color' => '#991B1B', 'bg' => '#FEE2E2',
                           'border' => '#FCA5A5'],
            ];
            $sc = $secColors[$section->code]
                ?? ['color' => '#374151', 'bg' => '#F3F4F6',
                    'border' => '#D1D5DB'];
        @endphp
        <div class="bg-white rounded-xl shadow-sm border border-gray-100
                    overflow-hidden">
            {{-- En-tête section --}}
            <div class="px-5 py-4 border-b-2"
                 style="background-color: {{ $sc['bg'] }};
                        border-color: {{ $sc['border'] }};">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-bold text-sm"
                           style="color: {{ $sc['color'] }};">
                            {{ $section->name }}
                        </p>
                        <p class="text-xs mt-0.5"
                           style="color: {{ $sc['color'] }}; opacity: 0.7;">
                            {{ $section->language === 'en'
                                ? 'Anglophone' : 'Francophone' }}
                        </p>
                    </div>
                    <span class="px-2.5 py-1 rounded-full text-xs font-bold"
                          style="background-color: {{ $sc['border'] }};
                                 color: {{ $sc['color'] }};">
                        {{ $sectionSubs->count() }} matières
                    </span>
                </div>
            </div>

            {{-- Liste des matières de cette section --}}
            @if($sectionSubs->isEmpty())
            <div class="px-5 py-8 text-center text-gray-400">
                <p class="text-sm italic">
                    Aucune matière enseignée dans cette section.
                </p>
            </div>
            @else
            <div class="divide-y divide-gray-50 max-h-96 overflow-y-auto">
                @foreach($sectionSubs as $sub)
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
                    $tc = $typeColors[$sub->type] ?? $typeColors['other'];
                    $classesInSection = $sub->classSubjects->filter(
                        fn($cs) => $cs->classGroup?->level?->section_id === $section->id
                    )->count();
                @endphp
                <div class="px-4 py-3 flex items-center justify-between gap-3
                            hover:bg-gray-50 transition-colors">
                    <div class="flex items-center gap-2 min-w-0">
                        {{-- <span class="font-mono text-xs font-bold flex-shrink-0"
                              style="color: #1A3A6B;">
                            {{ $sub->code }}
                        </span> --}}
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-800 truncate">
                                {{ $sub->name_fr }}
                            </p>
                            @if($sub->name_en)
                            <p class="text-xs text-gray-400 truncate">
                                {{ $sub->name_en }}
                            </p>
                            @endif
                        </div>
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0">
                        {{-- <span class="px-2 py-0.5 rounded-full text-xs font-bold"
                              style="background-color: {{ $tc['bg'] }};
                                     color: {{ $tc['text'] }};">
                            {{ $tc['label'] }}
                        </span> --}}
                        <span class="text-xs text-gray-400 whitespace-nowrap">
                            {{ $classesInSection }} classes
                        </span>
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>
        @endforeach
    </div>
</div>

{{-- ════════════════════════════════════════════════════════════════════ --}}
{{-- ONGLET : ATTRIBUTION AUX CLASSES                                     --}}
{{-- ════════════════════════════════════════════════════════════════════ --}}
<div x-show="tab === 'assign'" x-transition>

    @php
        $activeYear = \App\Models\AcademicYear::active();
        $allClasses = $activeYear
            ? \App\Models\ClassGroup::where('academic_year_id', $activeYear->id)
                ->with(['level.section', 'classSubjects'])
                ->withCount('classSubjects')
                ->orderBy('name')
                ->get()
                ->when(request('section_id'), function ($collection) {
                    return $collection->filter(function ($class) {
                        return $class->level?->section_id == request('section_id');
                    })->values();
                })
                ->groupBy('level.section.name')
            : collect();
    @endphp

    @if(!$activeYear)
    <div class="bg-amber-50 border border-amber-200 rounded-xl p-5 text-sm
                text-amber-700">
        Aucune année scolaire active. Activez une année pour gérer
        les attributions.
    </div>
    @else
    <div class="space-y-4">
        @foreach($allClasses as $sectionName => $classes)
        <div class="bg-white rounded-xl shadow-sm border border-gray-100
                    overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-100"
                 style="background-color: #F0F4F8;">
                <span class="font-semibold text-sm" style="color: #1A3A6B;">
                    {{ $sectionName }}
                </span>
            </div>
            <div class="divide-y divide-gray-50">
                @foreach($classes as $class)
                <div class="px-5 py-3.5 flex items-center
                            justify-between gap-4">
                    <div>
                        <p class="text-sm font-medium text-gray-800">
                            {{ $class->full_name }}
                        </p>
                        <p class="text-xs text-gray-400">
                            {{ $class->class_subjects_count }}
                            matière(s) assignée(s)
                        </p>
                    </div>
                    @can('manage-subjects')
                    <a href="{{ route('subjects.assign', $class) }}"
                       class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg
                              text-sm font-medium transition-colors border"
                       style="border-color: #1A3A6B; color: #1A3A6B;">
                        <svg class="w-3.5 h-3.5" fill="none"
                             stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round"
                                  stroke-linejoin="round" stroke-width="2"
                                  d="M15.232 5.232l3.536 3.536m-2.036-5.036
                                     a2.5 2.5 0 113.536 3.536L6.5 21.036H3
                                     v-3.572L16.732 3.732z"/>
                        </svg>
                        Gérer
                    </a>
                    @endcan
                </div>
                @endforeach
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>

{{-- ONGLET : CATÉGORIES ─────────────────────────────────────────────── --}}
{{-- (conservé mais accessible via un lien discret) --}}

    <div x-show="categoryListModal" x-cloak x-transition.opacity @keydown.escape.window="categoryListModal = false" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/40 p-4">
        <div @click.outside="categoryListModal = false" class="w-full max-w-2xl overflow-hidden rounded-2xl bg-white shadow-2xl">
            <div class="flex items-start justify-between border-b border-gray-100 px-6 py-5">
                <div><h2 class="text-lg font-black text-gray-900">Catégories de matières</h2><p class="mt-1 text-sm text-gray-500">Organisation du catalogue et des bulletins.</p></div>
                <button type="button" @click="categoryListModal = false" class="rounded-lg p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-700" aria-label="Fermer"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>
            <div class="max-h-[60vh] divide-y divide-gray-100 overflow-y-auto">
                <template x-if="categories.length === 0"><p class="px-6 py-10 text-center text-sm font-medium text-gray-400">Aucune catégorie enregistrée.</p></template>
                <template x-for="category in categories" :key="category.id">
                    <div class="flex items-center gap-4 px-6 py-4">
                        <div class="min-w-0 flex-1"><p class="truncate text-sm font-bold text-gray-800"><span x-text="category.code"></span><span class="font-normal text-gray-400"> - </span><span x-text="category.name"></span></p><p class="mt-0.5 truncate text-xs text-gray-400" x-text="category.section_name"></p></div>
                        <span class="hidden rounded-full bg-gray-100 px-2.5 py-1 text-xs font-bold text-gray-500 sm:inline-flex" x-text="category.subjects_count + ' matière(s)'"></span>
                        <button type="button" @click="openEditor(category)" class="rounded-lg p-2 text-[#1A3A6B] transition hover:bg-blue-50" title="Modifier"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 4H6a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2v-5m-1.5-9.5a2.121 2.121 0 113 3L12 14l-4 1 1-4 9.5-9.5z"/></svg></button>
                        <form :action="'{{ route('subjects.categories.destroy', ['category' => '__CATEGORY__']) }}'.replace('__CATEGORY__', category.id)" method="POST" @submit.prevent="if (confirm('Supprimer cette catégorie ?')) $el.submit()">
                            @csrf @method('DELETE')
                            <button type="submit" :disabled="category.subjects_count > 0" class="rounded-lg p-2 text-red-500 transition hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-30" :title="category.subjects_count > 0 ? 'Cette catégorie contient des matières' : 'Supprimer'"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1H10a1 1 0 00-1 1v3m-4 0h14"/></svg></button>
                        </form>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <div x-show="editCategory" x-cloak x-transition.opacity @keydown.escape.window="editCategory = null" class="fixed inset-0 z-[60] flex items-center justify-center bg-gray-900/40 p-4">
        <div @click.outside="editCategory = null" class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl">
            <div class="flex items-start justify-between gap-4"><div><h2 class="text-lg font-black text-gray-900">Modifier la catégorie</h2><p class="mt-1 text-sm text-gray-500">Les matières déjà associées seront conservées.</p></div><button type="button" @click="editCategory = null; categoryListModal = true" class="rounded-lg p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-700" aria-label="Fermer"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button></div>
            <form x-show="editCategory" x-init="$el.querySelectorAll('input[name=name_fr], input[name=name_en]').forEach(input => input.disabled = true)" :action="editCategory ? '{{ route('subjects.categories.update', ['category' => '__CATEGORY__']) }}'.replace('__CATEGORY__', editCategory.id) : ''" method="POST" class="mt-6 space-y-4">
                @csrf @method('PUT')
                <div><label class="mb-1.5 block text-sm font-semibold text-gray-700">Section</label><select name="section_id" required x-model="editCategory.section_id" class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm text-gray-800 outline-none transition focus:border-[#1A3A6B] focus:ring-2 focus:ring-blue-100">@foreach($sections as $section)<option value="{{ $section->id }}">{{ $section->name }}</option>@endforeach</select></div>
                <div><label class="mb-1.5 block text-sm font-semibold text-gray-700">Code</label><input name="code" required maxlength="30" x-model="editCategory.code" class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm text-gray-800 outline-none transition focus:border-[#1A3A6B] focus:ring-2 focus:ring-blue-100"></div>
                <div><label class="mb-1.5 block text-sm font-semibold text-gray-700">Nom de la categorie</label><input name="name" required maxlength="100" x-model="editCategory.name" class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm text-gray-800 outline-none transition focus:border-[#1A3A6B] focus:ring-2 focus:ring-blue-100"></div>
                <div><label class="mb-1.5 block text-sm font-semibold text-gray-700">Nom en français</label><input name="name_fr" required maxlength="100" x-model="editCategory.name_fr" class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm text-gray-800 outline-none transition focus:border-[#1A3A6B] focus:ring-2 focus:ring-blue-100"></div>
                <div><label class="mb-1.5 block text-sm font-semibold text-gray-700">Nom en anglais <span class="font-normal text-gray-400">(facultatif)</span></label><input name="name_en" maxlength="100" x-model="editCategory.name_en" class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm text-gray-800 outline-none transition focus:border-[#1A3A6B] focus:ring-2 focus:ring-blue-100"></div>
                <div class="flex justify-end gap-3 border-t border-gray-100 pt-5"><button type="button" @click="editCategory = null; categoryListModal = true" class="rounded-xl px-4 py-2.5 text-sm font-bold text-gray-600 transition hover:bg-gray-100">Annuler</button><button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-[#1A3A6B] px-4 py-2.5 text-sm font-bold text-white transition hover:bg-[#163450]"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>Enregistrer</button></div>
            </form>
        </div>
    </div>

    @can('manage-subjects')
    <div class="fixed bottom-16 right-4 z-40 sm:bottom-20 sm:right-6" @click.outside="fabOpen = false">
        <div x-show="fabOpen" x-cloak x-transition.origin.bottom.right class="absolute bottom-16 right-0 w-52 rounded-2xl border border-gray-100 bg-white p-2 shadow-xl">
            <a href="{{ route('subjects.create') }}" class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-bold text-gray-700 transition hover:bg-orange-50 hover:text-[#C2410C]"><span class="flex h-8 w-8 items-center justify-center rounded-lg bg-orange-50 text-[#E87722]"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg></span>Nouvelle matière</a>
            <button type="button" @click="categoryModal = true; fabOpen = false" class="flex w-full items-center gap-3 rounded-xl px-3 py-3 text-left text-sm font-bold text-gray-700 transition hover:bg-blue-50 hover:text-[#1A3A6B]"><span class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 text-[#1A3A6B]"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4M5 20h14"/></svg></span>Nouvelle catégorie</button>
        </div>
        <button type="button" @click="fabOpen = !fabOpen" :aria-expanded="fabOpen.toString()" aria-label="Ajouter une matière ou une catégorie" class="flex h-14 w-14 items-center justify-center rounded-full bg-[#E87722] text-white shadow-lg transition hover:bg-[#C85D0E] hover:shadow-xl focus:outline-none focus:ring-4 focus:ring-orange-200"><svg class="h-6 w-6 transition-transform duration-200" :class="fabOpen ? 'rotate-45' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg></button>
    </div>
    @endcan
</div>{{-- end x-data --}}
@push('fixed_footer')
<div class="fixed bottom-0 left-0 md:left-64 right-0 z-20
            bg-white border-t border-gray-200 shadow-md
            px-5 py-2.5 flex flex-col sm:flex-row sm:items-center
            justify-between gap-2 text-xs text-gray-500">

    {{-- Stats par type --}}
    <div class="flex items-center gap-4 flex-wrap">
        <span class="flex items-center gap-1.5">
            <span class="w-2 h-2 rounded-full bg-gray-400"></span>
            <strong class="text-gray-700">{{ $stats['total'] }}</strong>
            matières au total
        </span>
        <span class="hidden sm:flex items-center gap-1.5">
            <span class="w-2 h-2 rounded-full" style="background:#1D4ED8;"></span>
            <strong class="text-gray-700">{{ $stats['general'] }}</strong>
            générales
        </span>
        <span class="hidden sm:flex items-center gap-1.5">
            <span class="w-2 h-2 rounded-full" style="background:#6D28D9;"></span>
            <strong class="text-gray-700">{{ $stats['technical'] }}</strong>
            techniques
        </span>
        <span class="hidden sm:flex items-center gap-1.5">
            <span class="w-2 h-2 rounded-full" style="background:#065F46;"></span>
            <strong class="text-gray-700">{{ $stats['language'] }}</strong>
            langue(s)
        </span>
        <span class="hidden md:flex items-center gap-1.5">
            <span class="w-2 h-2 rounded-full" style="background:#92400E;"></span>
            <strong class="text-gray-700">{{ $stats['sport'] }}</strong>
            sport
        </span>
    </div>

    {{-- Dernière mise à jour + auteur --}}
    <div class="flex items-center gap-2 text-gray-400 flex-shrink-0">
        @if($lastAudit)
            <span>
                Dernière Mise à jour :
                 {{ now()->format('d/m/Y à H:i') }}
                {{ $lastAudit->created_at->format('d/m/Y à H:i') }}
                <span class="text-gray-300">•</span>
                Par
            </span>
            <span class="text-gray-300">•</span>
            <span class="flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        stroke-width="2"
                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0
                            00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                <span>{{ $lastAudit->user?->name ?? 'Système' }}</span>
            </span>
        @else
            <span>Aucune modification enregistrée</span>
        @endif
    </div>
</div>
@endpush
@endsection
