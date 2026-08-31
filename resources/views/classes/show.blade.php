@extends('layouts.app')

@section('title', $classGroup->full_name)
@section('page-title')
    Détails Classe — {{ $classGroup->full_name }} 
@endsection
@section('page-subtitle')
    Imformations détaillées de la classe :
    <span class="font-bold text-gray-900">{{ $classGroup->full_name }}</span>
@endsection

@section('content')

<style>
    .class-details-page .class-timetable-scroll th,
    .class-details-page .class-timetable-scroll td { font-size: 11px; }
    .class-details-page .class-timetable-scroll th:not(:first-child) { padding: 10px 4px; }
    .class-details-page .class-timetable-scroll td { padding: 8px 4px; }
</style>

{{-- CALCULS STATS DYNAMIQUES --}}
@php
    $totalStudents = $stats['students'];
    $boysCount = $stats['boys'];
    $girlsCount = $stats['girls'];
    $boysPct = $totalStudents > 0 ? round(($boysCount / $totalStudents) * 100) : 0;
@endphp

<div x-data="{ activeTab: 'students', searchStudent: '' }" class="class-details-page -mx-2 -mt-2 -mb-2 lg:-mx-4 lg:-mt-4 lg:-mb-4">

    {{-- ── EN-TÊTE PREMIUM GÉANTE (MOCKUP 3) ───────────────────────────────────────────── --}}
    <div class="rounded-3xl shadow-lg text-white overflow-hidden mb-6 relative" style="background-color: #1A3A6B;">
        {{-- Décoration d'arrière-plan --}}
        <div class="absolute right-0 top-0 bottom-0 w-1/3 bg-gradient-to-l from-white/5 to-transparent pointer-events-none"></div>
        
        <div class="px-6 py-6.5">
            {{-- Ligne titre principal --}}
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h2 class="text-3xl font-black tracking-tight mb-2">
                        {{ $classGroup->full_name }}
                    </h2>
                    
                    {{-- Metadonnées avec icônes --}}
                    <div class="flex flex-wrap items-center gap-y-2 gap-x-4 text-sm text-white/80 font-medium">
                        <span class="flex items-center gap-1.5">
                            <svg class="w-4 h-4 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                            {{ $classGroup->level->section->name }}
                        </span>
                        <span class="text-white/40">|</span>
                        <span class="flex items-center gap-1.5">
                            <svg class="w-4 h-4 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            {{ $classGroup->titularStaff?->full_name ?? 'Aucun enseignant titulaire' }}
                        </span>
                        <span class="text-white/40">|</span>
                        <span class="flex items-center gap-1.5">
                            <svg class="w-4 h-4 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            Année {{ $classGroup->academicYear->label }}
                        </span>
                    </div>

                    {{-- Badges statistiques rapides --}}
                    <div class="flex items-center gap-2 mt-4">
                        <span class="bg-white/10 text-white border border-white/10 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider">
                            {{ $totalStudents }} Élèves
                        </span>
                        <span class="bg-white/10 text-white border border-white/10 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider">
                            {{ $stats['subjects'] }} Matières
                        </span>
                    </div>
                </div>

                {{-- Action Modifier à droite --}}
                @can('manage-classes')
                    @if(!$classGroup->academicYear->isClosed())
                        <div class="flex-shrink-0">
                            <a href="{{ route('classes.edit', $classGroup) }}"
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl border border-white/20 hover:bg-white/10 active:scale-95 transition-all text-sm font-bold text-white">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                </svg>
                                Modifier la classe
                            </a>
                        </div>
                    @endif
                @endcan
            </div>
        </div>
    </div>

    {{-- ── BARRE DE NAVIGATION DES ONGLETS (MOCKUP 3) ─────────────────────────────────── --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-150 mb-6 overflow-hidden">
        <div class="flex overflow-x-auto border-b border-gray-100">
            {{-- Onglet Élèves --}}
            <button @click="activeTab = 'students'"
                    :class="activeTab === 'students' ? 'border-b-2 font-bold text-gray-800' : 'text-gray-400 hover:text-gray-600'"
                    class="px-6 py-4 text-sm font-semibold whitespace-nowrap transition-all border-b-2 border-transparent focus:outline-none"
                    :style="activeTab === 'students' ? 'color: #A24E0C; border-color: #A24E0C;' : ''">
                Élèves
            </button>
            
            {{-- Onglet Matières --}}
            <button @click="activeTab = 'subjects'"
                    :class="activeTab === 'subjects' ? 'border-b-2 font-bold text-gray-800' : 'text-gray-400 hover:text-gray-600'"
                    class="px-6 py-4 text-sm font-semibold whitespace-nowrap transition-all border-b-2 border-transparent focus:outline-none"
                    :style="activeTab === 'subjects' ? 'color: #A24E0C; border-color: #A24E0C;' : ''">
                Matières & Coefficients
            </button>
            
            {{-- Onglets Placeholders --}}
            @foreach(['Emploi du temps' => 'timetable', 'Statistiques' => 'stats'] as $label => $tab)
            <button @click="activeTab = '{{ $tab }}'"
                    :class="activeTab === '{{ $tab }}' ? 'border-b-2 font-bold text-gray-800' : 'text-gray-400 hover:text-gray-600'"
                    class="px-6 py-4 text-sm font-semibold whitespace-nowrap transition-all border-b-2 border-transparent focus:outline-none"
                    :style="activeTab === '{{ $tab }}' ? 'color: #A24E0C; border-color: #A24E0C;' : ''">
                {{ $label }}
            </button>
            @endforeach
        </div>
    </div>

    {{-- ── 1. CONTENU DE L'ONGLET ÉLÈVES (MOCKUP 3) ─────────────────────────────────────── --}}
    <div x-show="activeTab === 'students'" class="grid grid-cols-1 lg:grid-cols-3 gap-6" x-transition>
        
        {{-- Liste des élèves (Colonne Gauche - 2/3) --}}
        <div class="lg:col-span-2 space-y-4">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-150 overflow-hidden">
                <div class="p-5 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-gray-50/50">
                    <div class="relative flex-1">
                        <input type="text" x-model="searchStudent" placeholder="Rechercher un élève..."
                               class="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-100 bg-white"
                               style="color: #1A3A6B;" />
                        <div class="absolute left-3.5 top-3 text-gray-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                    </div>

                    @can('manage-students')
                        @if(!$classGroup->academicYear->isClosed())
                            <a href="{{ route('students.create', ['class_id' => $classGroup->id]) }}"
                            class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-white
                                    text-xs font-medium"
                            style="background-color:#1A5C2A;">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                Inscrire un élève
                            </a>
                        @endif
                    @endcan
                </div>

                @if($classGroup->studentEnrollments->isEmpty())
                <div class="p-12 text-center text-gray-400">
                    <svg class="w-12 h-12 mx-auto mb-3 opacity-25" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <p class="text-sm font-semibold">Aucun élève inscrit dans cette classe.</p>
                    <p class="text-xs text-gray-300 mt-1">Inscrivez des élèves via le module Élèves (Étape 4.6).</p>
                </div>
                @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50/50 border-b border-gray-100 text-gray-400 font-bold uppercase tracking-wider text-[10px]">
                                <th class="text-center px-4 py-3.5 w-12">#</th>
                                <th class="text-left px-4 py-3.5">Élève</th>
                                <th class="text-left px-4 py-3.5">Matricule</th>
                                <th class="text-center px-4 py-3.5">Sexe</th>
                                <th class="text-center px-4 py-3.5">Statut</th>
                                <th class="text-center px-4 py-3.5 w-20">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach($classGroup->studentEnrollments as $index => $enrollment)
                            @php $student = $enrollment->student; @endphp
                            @if($student)
                            <tr x-data="{ studentName: @json(strtolower($student->full_name)), studentMatricule: @json(strtolower($student->matricule)) }"
                                class="hover:bg-gray-50/70 transition-colors"
                                x-show="searchStudent === '' || studentName.includes(searchStudent.toLowerCase()) || studentMatricule.includes(searchStudent.toLowerCase())">
                                <td class="px-4 py-3.5 text-center font-bold text-gray-400">
                                    {{ sprintf('%02d', $index + 1) }}
                                </td>
                                <td class="px-4 py-3.5">
                                    <div class="flex items-center gap-3">
                                        @if($student->photo)
                                        <img src="{{ $student->photo_url }}" class="w-9 h-9 rounded-full object-cover flex-shrink-0 border border-gray-100 shadow-xs">
                                        @else
                                        <div class="w-9 h-9 rounded-full flex items-center justify-center text-white text-xs font-black flex-shrink-0"
                                             style="background-color: #1A3A6B;">
                                            {{ strtoupper(substr($student->last_name, 0, 1)) }}
                                        </div>
                                        @endif
                                        <div>
                                            <p class="font-bold text-gray-800 leading-snug">{{ $student->full_name }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3.5 text-gray-500 font-medium">
                                    {{ $student->matricule }}
                                </td>
                                <td class="px-4 py-3.5 text-center">
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold
                                                 {{ $student->gender === 'M'
                                                     ? 'bg-blue-50 text-blue-700 border border-blue-100'
                                                     : 'bg-pink-50 text-pink-700 border border-pink-100' }}">
                                        {{ $student->gender === 'M' ? 'M' : 'F' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 text-center">
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider
                                                 {{ $enrollment->status === 'active'
                                                     ? 'bg-green-50 text-green-700 border border-green-200'
                                                     : 'bg-red-50 text-red-700 border border-red-200' }}">
                                        {{ $enrollment->status === 'active' ? 'ACTIF' : 'SUSPENDU' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <a href="{{ route('students.show', $student) }}" class="p-1 text-gray-400 hover:text-blue-750 transition-colors" title="Fiche élève">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </a>
                                        <a href="{{ route('students.edit', $student) }}" class="p-1 text-gray-400 hover:text-amber-700 transition-colors" title="Modifier">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                            </svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        </div>

        {{-- Résumé de la classe (Colonne Droite - 1/3) --}}
        <div class="space-y-4">
            
            {{-- Résumé Card --}}
            <div class="bg-white border border-gray-150 rounded-2xl p-5 shadow-sm space-y-6">
                <div class="flex items-center gap-2 pb-2 border-b border-gray-100">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    <h3 class="font-bold text-sm text-gray-800 uppercase tracking-wide">Résumé de la classe</h3>
                </div>

                {{-- Évaluation précédente --}}
                <div class="rounded-xl border border-gray-100 bg-gray-50/60 p-3">
                    <div class="flex items-center justify-between gap-2">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Évaluation précédente</p>
                        @if($previousEvaluation['available'])
                            <span class="rounded-full bg-[#EBF3FB] px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-[#1A3A6B]">
                                {{ $previousEvaluation['sequence'] ? ($previousEvaluation['sequence']->label ?: 'Évaluation ' . $previousEvaluation['sequence']->number) : 'Aucune donnée' }}
                            </span>
                        @else
                            <span class="rounded-full bg-gray-100 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-gray-500">À venir</span>
                        @endif
                    </div>

                    @if($previousEvaluation['available'])
                        <div class="mt-3 grid grid-cols-2 gap-3">
                            <div class="rounded-lg border border-gray-200 bg-white p-3">
                                <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Moyenne</p>
                                <p class="mt-1 text-xl font-black text-gray-800">
                                    {{ $previousEvaluation['average'] !== null ? number_format($previousEvaluation['average'], 2, ',', ' ') : '—' }}
                                    @if($previousEvaluation['average'] !== null)
                                        <span class="text-xs font-normal text-gray-400">/20</span>
                                    @endif
                                </p>
                            </div>
                            <div class="rounded-lg border border-gray-200 bg-white p-3">
                                <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Taux de réussite</p>
                                <p class="mt-1 text-xl font-black text-gray-800">
                                    {{ $previousEvaluation['success_rate'] !== null ? $previousEvaluation['success_rate'] . '%' : '—' }}
                                </p>
                            </div>
                        </div>

                        <div class="mt-3 space-y-2">
                            <div class="flex items-center justify-between rounded-lg border border-green-100 bg-green-50/70 px-3 py-2">
                                <div>
                                    <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Meilleur élève</p>
                                    <p class="text-sm font-bold text-gray-800">
                                        {{ data_get($previousEvaluation, 'best_student.name', '—') }}
                                    </p>
                                </div>
                                <span class="text-sm font-black text-green-700">
                                    {{ data_get($previousEvaluation, 'best_student.average') !== null ? number_format(data_get($previousEvaluation, 'best_student.average'), 2, ',', ' ') : '—' }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between rounded-lg border border-red-100 bg-red-50/70 px-3 py-2">
                                <div>
                                    <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Plus faible élève</p>
                                    <p class="text-sm font-bold text-gray-800">
                                        {{ data_get($previousEvaluation, 'weakest_student.name', '—') }}
                                    </p>
                                </div>
                                <span class="text-sm font-black text-red-700">
                                    {{ data_get($previousEvaluation, 'weakest_student.average') !== null ? number_format(data_get($previousEvaluation, 'weakest_student.average'), 2, ',', ' ') : '—' }}
                                </span>
                            </div>
                        </div>
                    @else
                        <div class="mt-3 rounded-lg border border-dashed border-gray-200 bg-white p-4 text-center text-sm text-gray-500">
                            La première évaluation n’est pas encore disponible.
                        </div>
                    @endif
                </div>

                {{-- Circular Gauge Gender Ratio (MOCKUP 3 STYLE) --}}
                <div class="p-4 border border-gray-100 rounded-2xl bg-gray-50/30 flex flex-col items-center">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-4">Ratio Garçons / Filles</p>
                    <div class="relative flex items-center justify-center mb-4">
                        <svg class="w-24 h-24 transform -rotate-90">
                            {{-- Filles (Pink) en fond --}}
                            <circle cx="48" cy="48" r="38" stroke="#EC4899" stroke-width="10" fill="transparent" />
                            {{-- Garçons (Blue) en progression --}}
                            <circle cx="48" cy="48" r="38" stroke="#1A3A6B" stroke-width="10" fill="transparent"
                                    stroke-dasharray="238.7" stroke-dashoffset="{{ 238.7 * (1 - $boysPct / 100) }}" />
                        </svg>
                        <div class="absolute text-center">
                            <p class="text-xl font-black text-gray-800 leading-none">{{ $totalStudents }}</p>
                            <p class="text-[9px] font-bold text-gray-450 uppercase mt-0.5">Total</p>
                        </div>
                    </div>
                    <div class="flex justify-center gap-5 text-xs font-bold">
                        <span class="flex items-center gap-1.5" style="color: #1A3A6B;">
                            <span class="w-2.5 h-2.5 rounded-full" style="background-color: #1A3A6B;"></span>
                            Garçons ({{ $boysCount }})
                        </span>
                        <span class="flex items-center gap-1.5 text-pink-650">
                            <span class="w-2.5 h-2.5 rounded-full bg-pink-500"></span>
                            Filles ({{ $girlsCount }})
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── 2. CONTENU DE L'ONGLET MATIÈRES (MOCKUP 3 COHÉRENCE) ──────────────────────────────── --}}
    {{-- <div x-show="activeTab === 'subjects'" class="bg-white rounded-2xl shadow-sm border border-gray-150 overflow-hidden" x-transition p-5> --}}
    <div x-show="activeTab === 'subjects'" class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <div class="flex items-center justify-between mb-4 pb-2
                    border-b border-gray-100">
            <h3 class="text-sm font-semibold uppercase tracking-wider
                    text-gray-400">
                Matières
            </h3>
            <div class="flex items-center gap-3">
                @if($classGroup->classSubjects->isNotEmpty())
                <span class="text-xs text-gray-400">
                    Coef. total :
                    <strong style="color: #1A3A6B;">
                        {{ $classGroup->classSubjects
                            ->where('is_active', true)->sum('coefficient') }}
                    </strong>
                </span>
                @endif
                {{-- Bouton gérer les matières --}}
                @can('manage-subjects')
                @if(!$classGroup->academicYear->isClosed())
                <a href="{{ route('subjects.assign', $classGroup) }}"
                class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg
                        text-white text-xs font-medium transition-colors"
                style="background-color: #1A5C2A;">
                    <svg class="w-3.5 h-3.5" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 4v16m8-8H4"/>
                    </svg>
                    Gérer
                </a>
                @endif
                @endcan
            </div>
        </div>

        @if($classGroup->classSubjects->isEmpty())
        <div class="p-12 text-center text-gray-400">
            <p class="text-sm font-semibold">Aucune matière n'a été attribuée à cette classe.</p>
            <p class="text-xs text-gray-300 mt-1">Configurez les matières depuis le module Matières (Étape 4.4).</p>
        </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50/50 border-b border-gray-100 text-gray-400 font-bold uppercase tracking-wider text-[10px]">
                        <th class="text-left px-6 py-4">Matière</th>
                        <th class="text-center px-6 py-4">Catégorie</th>
                        <th class="text-center px-6 py-4">Coefficient</th>
                        <th class="text-center px-6 py-4">Volume Horaire</th>
                        <th class="text-left px-6 py-4">Enseignant Assigné</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($classGroup->classSubjects->sortBy('subject.name_fr') as $cs)
                    @php
                        $teacher = $cs->teacherAssignments
                            ->where('academic_year_id', $classGroup->academic_year_id)
                            ->first()?->staff;
                    @endphp
                    <tr class="hover:bg-gray-50/70 transition-colors">
                        <td class="px-6 py-4 font-bold text-gray-800">
                            {{ $cs->subject->name_fr }}
                        </td>
                        <td class="px-6 py-4 text-center">
                            @php($colors = ['bg-blue-50 text-blue-700 border-blue-100', 'bg-emerald-50 text-emerald-700 border-emerald-100', 'bg-amber-50 text-amber-700 border-amber-100', 'bg-violet-50 text-violet-700 border-violet-100', 'bg-rose-50 text-rose-700 border-rose-100'])
                            <span class="px-2 py-0.5 rounded text-xs font-bold border {{ $colors[crc32((string) $cs->subject->category->id) % count($colors)] }}">
                                {{ $cs->subject->category->name_fr }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center font-bold text-gray-800">
                            {{ $cs->coefficient }}
                        </td>
                        <td class="px-6 py-4 text-center text-gray-500 font-medium">
                            {{ $cs->hours_per_week ? $cs->hours_per_week . ' h/semaine' : 'Non défini' }}
                        </td>
                        <td class="px-6 py-4 font-medium text-gray-700">
                            {{ $teacher?->full_name ?? '—' }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    {{-- ── 3. ONGLET NOTES (PLACEHOLDER) ─────────────────────────────────────────────── --}}
    {{-- <div x-show="activeTab === 'grades'" class="bg-white rounded-2xl shadow-sm border border-gray-150 p-12 text-center" x-transition>
        <div class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4" style="background-color: #EBF3FB; color: #1A3A6B;">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
            </svg>
        </div>
        <h3 class="text-lg font-extrabold text-gray-800 mb-1">Notes & Évaluations</h3>
        <p class="text-sm text-gray-400 max-w-md mx-auto mt-2">
            Les tableaux de bord et synthèses de notes seront enrichis dans la prochaine itération du module.
        </p>
    </div> --}}

    {{-- ── 4. ONGLET EMPLOI DU TEMPS ───────────────────────────────────────────────── --}}
    <div x-show="activeTab === 'timetable'" class="space-y-4" x-transition>
        <div class="rounded-2xl border border-gray-100 bg-white shadow-sm overflow-hidden">
            <div class="border-b border-gray-100 px-5 py-4 flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div>
                    <h3 class="font-black text-[#1A3A6B]">Emploi du temps de la classe</h3>
                    <p class="text-xs text-gray-500">La grille ci-dessous suit la même structure que celle du module principal.</p>
                </div>
                <div class="rounded-full bg-[#F8FBFE] px-3 py-1 text-xs font-semibold text-[#1A3A6B]">
                    Grille active · {{ $timetableSetting->period_duration_minutes }} min / période
                </div>
            </div>
            <div class="class-timetable-scroll overflow-x-auto p-0">
                @include('timetable.partials.grid', [
                    'mode' => 'class',
                    'printable' => false,
                    'compact' => true,
                    'days' => $timetableDays,
                    'gridRows' => $timetableGridRows,
                    'slots' => $timetableSlots,
                    'conflicts' => $timetableConflicts,
                ])
            </div>
        </div>
    </div>

    {{-- ── 5. ONGLET STATISTIQUES ─────────────────────────────────────────────────── --}}
    <div x-show="activeTab === 'stats'" class="space-y-6" x-transition>
        <div class="rounded-3xl border border-gray-100 bg-white p-6 shadow-sm">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-gray-400">Vue d’ensemble</p>
                    <h3 class="mt-1 text-2xl font-black text-[#1A3A6B]">Tableau de bord de la classe</h3>
                    <p class="mt-2 max-w-2xl text-sm text-gray-500">Une vision claire des effectifs, de la répartition par sexe et des performances de la classe.</p>
                </div>
                <div class="rounded-2xl bg-[#F8FBFE] px-4 py-3 text-sm font-semibold text-[#1A3A6B] border border-[#E7F0FA]">
                    {{ $classGroup->level->section->name }} · {{ $classGroup->academicYear->label }}
                </div>
            </div>

            <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-2xl border border-gray-100 bg-[#F8FBFE] p-4">
                    <p class="text-xs font-bold uppercase tracking-wide text-gray-400">Élèves actifs</p>
                    <p class="mt-2 text-3xl font-black text-[#1A3A6B]">{{ $stats['students'] }}</p>
                </div>
                <div class="rounded-2xl border border-gray-100 bg-[#F8FBFE] p-4">
                    <p class="text-xs font-bold uppercase tracking-wide text-gray-400">Garçons</p>
                    <p class="mt-2 text-3xl font-black text-[#1A3A6B]">{{ $stats['boys'] }}</p>
                </div>
                <div class="rounded-2xl border border-gray-100 bg-[#F8FBFE] p-4">
                    <p class="text-xs font-bold uppercase tracking-wide text-gray-400">Filles</p>
                    <p class="mt-2 text-3xl font-black text-[#1A3A6B]">{{ $stats['girls'] }}</p>
                </div>
                <div class="rounded-2xl border border-gray-100 bg-[#F8FBFE] p-4">
                    <p class="text-xs font-bold uppercase tracking-wide text-gray-400">Matières</p>
                    <p class="mt-2 text-3xl font-black text-[#1A3A6B]">{{ $stats['subjects'] }}</p>
                </div>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="rounded-3xl border border-gray-100 bg-white p-6 shadow-sm h-full">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-gray-400">Performance</p>
                        <h4 class="mt-1 text-lg font-black text-gray-800">Évaluation précédente</h4>
                    </div>
                    <div class="rounded-full bg-[#EBF3FB] px-3 py-1 text-xs font-semibold text-[#1A3A6B]">
                        {{ $previousEvaluation['sequence'] ? ($previousEvaluation['sequence']->label ?: 'Évaluation ' . $previousEvaluation['sequence']->number) : 'Aucune donnée' }}
                    </div>
                </div>

                <div class="mt-5 space-y-4">
                    <div>
                        <div class="mb-2 flex items-center justify-between text-sm font-semibold text-gray-600">
                            <span>Moyenne générale</span>
                            <span>{{ data_get($previousEvaluation, 'average') !== null ? number_format(data_get($previousEvaluation, 'average'), 2, ',', ' ') . ' / 20' : '—' }}</span>
                        </div>
                        <div class="h-2.5 rounded-full bg-gray-100">
                            <div class="h-2.5 rounded-full bg-[#1A3A6B]" style="width: {{ data_get($previousEvaluation, 'average') !== null ? min(100, (data_get($previousEvaluation, 'average') / 20) * 100) : 0 }}%"></div>
                        </div>
                    </div>
                    <div>
                        <div class="mb-2 flex items-center justify-between text-sm font-semibold text-gray-600">
                            <span>Taux de réussite</span>
                            <span>{{ data_get($previousEvaluation, 'success_rate') !== null ? data_get($previousEvaluation, 'success_rate') . '%' : '—' }}</span>
                        </div>
                        <div class="h-2.5 rounded-full bg-gray-100">
                            <div class="h-2.5 rounded-full bg-[#A24E0C]" style="width: {{ data_get($previousEvaluation, 'success_rate') !== null ? data_get($previousEvaluation, 'success_rate') : 0 }}%"></div>
                        </div>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div class="rounded-2xl border border-gray-100 bg-[#F8FBFE] p-3">
                            <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-gray-400">Meilleur élève</p>
                            <p class="mt-1 text-sm font-semibold text-gray-800">{{ data_get($previousEvaluation, 'best_student.name') ?? '—' }}</p>
                            <p class="mt-1 text-xs font-medium text-[#1A3A6B]">{{ data_get($previousEvaluation, 'best_student.average') !== null ? number_format(data_get($previousEvaluation, 'best_student.average'), 2, ',', ' ') . ' / 20' : '—' }}</p>
                        </div>
                        <div class="rounded-2xl border border-gray-100 bg-[#F8FBFE] p-3">
                            <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-gray-400">Plus faible</p>
                            <p class="mt-1 text-sm font-semibold text-gray-800">{{ data_get($previousEvaluation, 'weakest_student.name') ?? '—' }}</p>
                            <p class="mt-1 text-xs font-medium text-[#A24E0C]">{{ data_get($previousEvaluation, 'weakest_student.average') !== null ? number_format(data_get($previousEvaluation, 'weakest_student.average'), 2, ',', ' ') . ' / 20' : '—' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="rounded-3xl border border-gray-100 bg-white p-6 shadow-sm h-full">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-gray-400">Performance</p>
                        <h4 class="mt-1 text-lg font-black text-gray-800">Annuel</h4>
                    </div>
                    <div class="rounded-full bg-[#F8FBFE] px-3 py-1 text-xs font-semibold text-[#1A3A6B]">
                        Moyenne générale annuelle
                    </div>
                </div>

                <div class="mt-5 space-y-4">
                    <div>
                        <div class="mb-2 flex items-center justify-between text-sm font-semibold text-gray-600">
                            <span>Moyenne générale</span>
                            <span>{{ $annualEvaluation['average'] !== null ? number_format($annualEvaluation['average'], 2, ',', ' ') . ' / 20' : '—' }}</span>
                        </div>
                        <div class="h-2.5 rounded-full bg-gray-100">
                            <div class="h-2.5 rounded-full bg-[#1A3A6B]" style="width: {{ $annualEvaluation['average'] !== null ? min(100, ($annualEvaluation['average'] / 20) * 100) : 0 }}%"></div>
                        </div>
                    </div>
                    <div>
                        <div class="mb-2 flex items-center justify-between text-sm font-semibold text-gray-600">
                            <span>Taux de réussite</span>
                            <span>{{ $annualEvaluation['success_rate'] !== null ? $annualEvaluation['success_rate'] . '%' : '—' }}</span>
                        </div>
                        <div class="h-2.5 rounded-full bg-gray-100">
                            <div class="h-2.5 rounded-full bg-[#A24E0C]" style="width: {{ $annualEvaluation['success_rate'] !== null ? $annualEvaluation['success_rate'] : 0 }}%"></div>
                        </div>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div class="rounded-2xl border border-gray-100 bg-[#F8FBFE] p-3">
                            <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-gray-400">Meilleur élève</p>
                            <p class="mt-1 text-sm font-semibold text-gray-800">{{ $annualEvaluation['best_student']['name'] ?? '—' }}</p>
                            <p class="mt-1 text-xs font-medium text-[#1A3A6B]">{{ isset($annualEvaluation['best_student']['average']) ? number_format($annualEvaluation['best_student']['average'], 2, ',', ' ') . ' / 20' : '—' }}</p>
                        </div>
                        <div class="rounded-2xl border border-gray-100 bg-[#F8FBFE] p-3">
                            <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-gray-400">Plus faible</p>
                            <p class="mt-1 text-sm font-semibold text-gray-800">{{ $annualEvaluation['weakest_student']['name'] ?? '—' }}</p>
                            <p class="mt-1 text-xs font-medium text-[#A24E0C]">{{ isset($annualEvaluation['weakest_student']['average']) ? number_format($annualEvaluation['weakest_student']['average'], 2, ',', ' ') . ' / 20' : '—' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

@endsection
