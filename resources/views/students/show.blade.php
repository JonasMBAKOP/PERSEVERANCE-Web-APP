@extends('layouts.app')

@section('title', $student->full_name)
@section('page-title', 'Fiche Élève')
@section('page-subtitle', 'Détails et informations complètes de l\'élève ' . $student->full_name)

{{-- @section('breadcrumb')
    <a href="{{ route('students.index') }}" class="hover:text-gray-700">
        Élèves
    </a>
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round"
              stroke-width="2" d="M9 5l7 7-7 7"/>
    </svg>
    <span style="color:#1A3A6B;" class="font-medium">
        {{ $student->full_name }}
    </span>
@endsection --}}

@section('content')

<div x-data="{ tab: 'info' }">

{{-- ══════════════════════════════════════════════════════════════════════ --}}
{{-- EN-TÊTE                                                                 --}}
{{-- ══════════════════════════════════════════════════════════════════════ --}}
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 mb-5 p-5">
    <div class="flex flex-col sm:flex-row sm:items-center gap-5">

        {{-- Photo --}}
        @if($student->photo)
        <img src="{{ $student->photo_url }}"
             alt="{{ $student->full_name }}"
             class="w-24 h-24 rounded-full object-cover ring-4
                    ring-gray-100 flex-shrink-0">
        @else
        <div class="w-24 h-24 rounded-full flex items-center justify-center
                    text-white font-black text-2xl flex-shrink-0"
             style="background-color:
                {{ $student->gender === 'M' ? '#1D4ED8' : '#BE185D' }};">
            {{ strtoupper(substr($student->last_name, 0, 1))
               . strtoupper(substr($student->first_name, 0, 1)) }}
        </div>
        @endif

        {{-- Infos principales --}}
        <div class="flex-1 min-w-0">
            <div class="flex items-center gap-3 flex-wrap mb-2">
                <h2 class="text-2xl font-black" style="color:#1A3A6B;">
                    {{ $student->full_name }}
                </h2>
                @if($activeEnrollment)
                <span class="px-2.5 py-0.5 rounded-md text-xs font-bold
                             tracking-wider bg-green-100 text-green-700">
                    ACTIF
                </span>
                @elseif($canEnroll && $previousEnrollment)
                <span class="px-2.5 py-0.5 rounded-md text-xs font-bold
                             bg-amber-100 text-amber-800">
                    EN ATTENTE DE RENOUVELLEMENT
                </span>
                @else
                <span class="px-2.5 py-0.5 rounded-md text-xs font-bold
                             bg-orange-100 text-orange-700">
                    NON INSCRIT
                </span>
                @endif
            </div>

            <div class="flex flex-wrap gap-x-5 gap-y-1 text-sm text-gray-600">
                <span>
                    <span class="text-gray-400">Matricule :</span>
                    <strong class="font-mono" style="color:#1A3A6B;">
                        {{ $student->matricule }}
                    </strong>
                </span>
                @if($activeEnrollment)
                <span>
                    <span class="text-gray-400">Classe :</span>
                    <strong>{{ $activeEnrollment->classGroup->full_name }}</strong>
                </span>
                <span>
                    <span class="text-gray-400">Section :</span>
                    <strong>
                        {{ $activeEnrollment->classGroup->level->section->name }}
                    </strong>
                </span>
                <span>
                    <span class="text-gray-400">Année :</span>
                    <strong>{{ $activeEnrollment->academicYear->label }}</strong>
                </span>
                @endif
            </div>

            @if($canEnroll && $previousEnrollment && $activeYear)
            <p class="text-sm text-amber-700 mt-2">
                Dernière inscription :
                <strong>{{ $previousEnrollment->classGroup->full_name }}</strong>
                ({{ $previousEnrollment->academicYear->label }}) —
                renouvellement requis pour {{ $activeYear->label }}.
            </p>
            @endif
        </div>

        {{-- Boutons d'action --}}
        <div class="flex items-center gap-2 flex-wrap flex-shrink-0">
            {{-- Bouton Transférer --}}
            @can('manage-students')
            @if($activeEnrollment)
            <div x-data="{ showTransferModal: false }" class="relative">
                <button type="button" @click="showTransferModal = true"
                        class="flex items-center gap-2 px-4 py-2 rounded-lg
                               border border-gray-200 text-sm font-medium
                               text-gray-600 hover:bg-gray-50 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                    </svg>
                    Transférer
                </button>

                {{-- Modal Transfert --}}
                <div x-show="showTransferModal" @click.outside="showTransferModal = false" x-cloak
                     class="fixed inset-0 z-50 flex items-center justify-center bg-black/20">
                    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full mx-4 p-6">
                        <h3 class="text-lg font-bold text-gray-800 mb-4">
                            Transférer {{ $student->full_name }}
                        </h3>

                        <form method="POST" action="{{ route('students.enrollments.transfer', $activeEnrollment) }}">
                            @csrf
                            @method('PATCH')

                            <div class="mb-6">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    Classe de destination
                                </label>
                                <select name="new_class_id" required
                                        class="w-full rounded-lg border border-gray-300 px-4 py-2.5
                                               text-sm text-gray-800 focus:border-[#1A3A6B]
                                               focus:ring-2 focus:ring-[#1A3A6B]/20">
                                    <option value="">Sélectionner une classe...</option>
                                    @foreach($transferClasses as $class)
                                        @if($class->id !== $activeEnrollment->class_group_id)
                                        <option value="{{ $class->id }}">
                                            {{ $class->full_name }}
                                            ({{ $class->studentEnrollments()->where('status', 'active')->count() }}/{{ $class->max_students }})
                                        </option>
                                        @endif
                                    @endforeach
                                </select>
                                @error('new_class_id')
                                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="flex gap-3 justify-end">
                                <button type="button" @click="showTransferModal = false"
                                        class="px-4 py-2 rounded-lg border border-gray-300
                                               text-sm font-medium text-gray-700
                                               hover:bg-gray-50 transition-colors">
                                    Annuler
                                </button>
                                <button type="submit"
                                        class="px-4 py-2 rounded-lg text-white text-sm font-semibold
                                               transition-colors"
                                        style="background-color:#1A3A6B;">
                                    Transférer
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            @endif
            @endcan

            {{-- Documents à imprimer --}}
            <div class="relative" x-data="{ openDocs: false }">
                <button type="button" @click="openDocs = !openDocs"
                        class="flex items-center gap-2 px-4 py-2 rounded-lg
                               border border-gray-200 text-sm font-medium
                               text-gray-600 hover:bg-gray-50 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    Documents
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="openDocs" @click.outside="openDocs = false" x-cloak
                     class="absolute right-0 mt-2 w-64 bg-white rounded-xl shadow-lg border border-gray-100 py-1 z-20">
                    @php $yearParam = $activeYear ? '?year_id=' . $activeYear->id : ''; @endphp
                    <a href="{{ route('students.documents.single', [$student, 'fiche']) }}{{ $yearParam }}"
                       target="_blank"
                       class="block px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50">
                        Fiche de renseignement
                    </a>
                    <a href="{{ route('students.documents.single', [$student, 'certificat']) }}{{ $yearParam }}"
                       target="_blank"
                       class="block px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50">
                        Certificat de scolarité
                    </a>
                    <a href="{{ route('students.documents.single', [$student, 'carte']) }}{{ $yearParam }}"
                       target="_blank"
                       class="block px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50">
                        Carte d'identité scolaire
                    </a>
                    <a href="{{ route('students.documents.single', [$student, 'livret']) }}{{ $yearParam }}"
                       target="_blank"
                       class="block px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50">
                        Livret scolaire
                    </a>
                </div>
            </div>

            {{-- Bulletin (module 4.8) --}}
            <button disabled
                    class="flex items-center gap-2 px-4 py-2 rounded-lg
                           border border-gray-200 text-sm font-medium
                           text-gray-400 cursor-not-allowed"
                    title="Disponible après le module Bulletins">
                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                     viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          stroke-width="2"
                          d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2
                             h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0
                             01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Bulletin
            </button>

            @can('manage-students')
            @if($canEnroll && $activeYear)
            <a href="{{ route('students.enroll', $student) }}"
               class="flex items-center gap-2 px-4 py-2 rounded-lg
                      text-white text-sm font-semibold transition-all
                      hover:shadow-md"
               style="background-color:#1A5C2A;">
                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                     viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Renouveler l'inscription
            </a>
            @endif
            @if($isEditable)
            {{-- Modifier --}}
            <a href="{{ route('students.edit', $student) }}"
               class="flex items-center gap-2 px-4 py-2 rounded-lg
                      text-white text-sm font-semibold transition-all
                      hover:shadow-md"
               style="background-color:#E87722;">
                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                     viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          stroke-width="2"
                          d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5
                             0 113.536 3.536L6.5 21.036H3v-3.572
                             L16.732 3.732z"/>
                </svg>
                Modifier
            </a>
            @else
            {{-- Badge lecture seule --}}
            <span class="flex items-center gap-1.5 px-4 py-2 rounded-lg border border-gray-200
                         text-sm font-medium text-gray-400 bg-gray-50"
                  title="Année clôturée — aucune modification autorisée">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0
                             00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                Lecture seule
            </span>
            @endif
            @endcan
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════ --}}
{{-- ONGLETS DE NAVIGATION                                                   --}}
{{-- ══════════════════════════════════════════════════════════════════════ --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-5">
    <div class="flex overflow-x-auto">
        @foreach([
            ['key' => 'info',     'label' => 'Informations',     'icon' => 'user'],
            ['key' => 'notes',    'label' => 'Notes & Moyennes', 'icon' => 'chart'],
            ['key' => 'absences', 'label' => 'Absences',         'icon' => 'calendar'],
            ['key' => 'finances', 'label' => 'Finances',         'icon' => 'cash'],
            ['key' => 'history',  'label' => 'Historique',       'icon' => 'clock'],
        ] as $t)
        <button @click="tab = '{{ $t['key'] }}'"
                :class="tab === '{{ $t['key'] }}'
                    ? 'border-b-2 font-semibold'
                    : 'text-gray-500 hover:text-gray-700'"
                class="flex items-center gap-2 px-5 py-4 text-sm
                       whitespace-nowrap transition-colors border-b-2
                       border-transparent"
                :style="tab === '{{ $t['key'] }}'
                    ? 'color:#E87722; border-color:#E87722;' : ''">

            @if($t['icon'] === 'user')
            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                 viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      stroke-width="2"
                      d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0
                         00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
            @elseif($t['icon'] === 'chart')
            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                 viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      stroke-width="2"
                      d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002
                         2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10
                         m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2
                         a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
            @elseif($t['icon'] === 'calendar')
            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                 viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      stroke-width="2"
                      d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0
                         00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            @elseif($t['icon'] === 'cash')
            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                 viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      stroke-width="2"
                      d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2
                         m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6
                         a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            @else
            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                 viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      stroke-width="2"
                      d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            @endif
            {{ $t['label'] }}
        </button>
        @endforeach
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════ --}}
{{-- ONGLET : INFORMATIONS                                                   --}}
{{-- ══════════════════════════════════════════════════════════════════════ --}}
<div x-show="tab === 'info'" x-transition>

    {{-- Ligne 1 : Infos personnelles + Contacts --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-5">

        {{-- Informations personnelles --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100
                    overflow-hidden">
            <div class="h-full border-l-4"
                 style="border-color:#1A3A6B;">
                <div class="p-6">
                    <h3 class="flex items-center gap-2 text-base font-bold mb-5"
                        style="color:#1A3A6B;">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor"
                             viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  stroke-width="2"
                                  d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7
                                     0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        Informations personnelles
                    </h3>

                    <div class="grid grid-cols-2 gap-5">
                        <div>
                            <p class="text-xs text-gray-400 mb-1">
                                Date de naissance
                            </p>
                            <p class="text-sm font-semibold text-gray-800">
                                {{ $student->date_of_birth?->format('d/m/Y') ?? '—' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 mb-1">Lieu</p>
                            <p class="text-sm font-semibold text-gray-800">
                                {{ $student->place_of_birth ?? '—' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 mb-1">Sexe</p>
                            <p class="text-sm font-semibold text-gray-800">
                                {{ $student->gender === 'M' ? 'Masculin' : 'Féminin' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 mb-1">Nationalité</p>
                            <p class="text-sm font-semibold text-gray-800">
                                {{ $student->nationality ?? '—' }}
                            </p>
                        </div>
                        <div class="col-span-2">
                            <p class="text-xs text-gray-400 mb-1">N° acte de naissance</p>
                            <p class="text-sm font-semibold text-gray-800 font-mono">
                                {{ $student->birth_certificate_number ?? '—' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Contacts & Tuteurs --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100
                    overflow-hidden">
            <div class="h-full border-l-4"
                 style="border-color:#E87722;">
                <div class="p-6">
                    <h3 class="flex items-center gap-2 text-base font-bold mb-5"
                        style="color:#E87722;">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor"
                             viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  stroke-width="2"
                                  d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10
                                     0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3
                                     3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356
                                     -1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0
                                     11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0
                                     014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        Contacts & Tuteurs
                    </h3>

                    <div class="space-y-4">
                        {{-- Père --}}
                        @if($student->father_name || $student->father_phone)
                        <div class="flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-xs text-gray-400 mb-0.5">Père</p>
                                <p class="text-sm font-semibold text-gray-800">
                                    {{ $student->father_name ?? '—' }}
                                </p>
                                @if($student->father_phone)
                                <p class="text-xs text-gray-500">
                                    {{ $student->father_phone }}
                                </p>
                                @endif
                            </div>
                            @if($student->father_phone)
                            <a href="tel:{{ $student->father_phone }}"
                               class="w-10 h-10 rounded-full flex items-center
                                      justify-center flex-shrink-0 transition-colors
                                      hover:opacity-80"
                               style="background-color:#E87722;">
                                <svg class="w-4 h-4 text-white" fill="none"
                                     stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round"
                                          stroke-linejoin="round" stroke-width="2"
                                          d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684
                                             l1.498 4.493a1 1 0 01-.502 1.21l-2.257
                                             1.13a11.042 11.042 0 005.516 5.516l1.13
                                             -2.257a1 1 0 011.21-.502l4.493 1.498a1 1
                                             0 01.684.949V19a2 2 0 01-2 2h-1C9.716
                                             21 3 14.284 3 6V5z"/>
                                </svg>
                            </a>
                            @endif
                        </div>
                        @endif

                        {{-- Mère --}}
                        @if($student->mother_name || $student->mother_phone)
                        <div class="flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-xs text-gray-400 mb-0.5">Mère</p>
                                <p class="text-sm font-semibold text-gray-800">
                                    {{ $student->mother_name ?? '—' }}
                                </p>
                                @if($student->mother_phone)
                                <p class="text-xs text-gray-500">
                                    {{ $student->mother_phone }}
                                </p>
                                @endif
                            </div>
                            @if($student->mother_phone)
                            <a href="tel:{{ $student->mother_phone }}"
                               class="w-10 h-10 rounded-full flex items-center
                                      justify-center flex-shrink-0 transition-colors
                                      hover:opacity-80"
                               style="background-color:#E87722;">
                                <svg class="w-4 h-4 text-white" fill="none"
                                     stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round"
                                          stroke-linejoin="round" stroke-width="2"
                                          d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684
                                             l1.498 4.493a1 1 0 01-.502 1.21l-2.257
                                             1.13a11.042 11.042 0 005.516 5.516l1.13
                                             -2.257a1 1 0 011.21-.502l4.493 1.498a1 1
                                             0 01.684.949V19a2 2 0 01-2 2h-1C9.716
                                             21 3 14.284 3 6V5z"/>
                                </svg>
                            </a>
                            @endif
                        </div>
                        @endif

                        {{-- Tuteur --}}
                        @if($student->guardian_name || $student->guardian_phone)
                        <div class="flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-xs text-gray-400 mb-0.5">
                                    Tuteur
                                    @if($student->guardian_relationship)
                                    ({{ $student->guardian_relationship }})
                                    @endif
                                </p>
                                <p class="text-sm font-semibold text-gray-800">
                                    {{ $student->guardian_name ?? '—' }}
                                </p>
                                @if($student->guardian_phone)
                                <p class="text-xs text-gray-500">
                                    {{ $student->guardian_phone }}
                                </p>
                                @endif
                            </div>
                            @if($student->guardian_phone)
                            <a href="tel:{{ $student->guardian_phone }}"
                               class="w-10 h-10 rounded-full flex items-center
                                      justify-center flex-shrink-0"
                               style="background-color:#E87722;">
                                <svg class="w-4 h-4 text-white" fill="none"
                                     stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round"
                                          stroke-linejoin="round" stroke-width="2"
                                          d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684
                                             l1.498 4.493a1 1 0 01-.502 1.21l-2.257
                                             1.13a11.042 11.042 0 005.516 5.516l1.13
                                             -2.257a1 1 0 011.21-.502l4.493 1.498a1 1
                                             0 01.684.949V19a2 2 0 01-2 2h-1C9.716
                                             21 3 14.284 3 6V5z"/>
                                </svg>
                            </a>
                            @endif
                        </div>
                        @endif

                        @if(!$student->father_name && !$student->mother_name
                            && !$student->guardian_name)
                        <p class="text-sm text-gray-400 italic">
                            Aucun contact enregistré.
                        </p>
                        @endif

                        {{-- Séparateur + Adresse --}}
                        @if($student->address)
                        <div class="pt-3 border-t border-gray-100">
                            <p class="text-xs text-gray-400 mb-1">Adresse</p>
                            <div class="flex items-start gap-1.5">
                                <svg class="w-3.5 h-3.5 text-gray-400 mt-0.5
                                            flex-shrink-0" fill="none"
                                     stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round"
                                          stroke-linejoin="round" stroke-width="2"
                                          d="M17.657 16.657L13.414 20.9a1.998 1.998
                                             0 01-2.827 0l-4.244-4.243a8 8 0 1111.314
                                             0z"/>
                                    <path stroke-linecap="round"
                                          stroke-linejoin="round" stroke-width="2"
                                          d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <p class="text-sm text-gray-700">
                                    {{ $student->address }}
                                </p>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Ligne 2 : Scolarité --}}
    @if($activeEnrollment)
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-5">
        <h3 class="flex items-center gap-2 text-base font-bold mb-5"
            style="color:#1A3A6B;">
            <svg class="w-5 h-5" fill="none" stroke="currentColor"
                 viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      stroke-width="2"
                      d="M12 14l9-5-9-5-9 5 9 5z"/>
                <path stroke-linecap="round" stroke-linejoin="round"
                      stroke-width="2"
                      d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952
                         11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078
                         12.078 0 01.665-6.479L12 14z"/>
            </svg>
            Scolarité
        </h3>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">

            {{-- Date d'inscription --}}
            <div class="flex items-center gap-4 p-4 rounded-xl
                        bg-gray-50 border border-gray-100">
                <div class="w-11 h-11 rounded-xl flex items-center
                            justify-center flex-shrink-0"
                     style="background-color:#EBF3FB;">
                    <svg class="w-5 h-5" style="color:#1A3A6B;" fill="none"
                         stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              stroke-width="2"
                              d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2
                                 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <p class="text-xs text-gray-400">Date d'inscription</p>
                    <p class="text-sm font-bold text-gray-800">
                        {{ $activeEnrollment->enrollment_date->format('d/m/Y') }}
                    </p>
                    @can('manage-students')
                    <form method="POST" action="{{ route('students.enrollments.update-date', $activeEnrollment) }}" class="mt-2 flex flex-wrap items-center gap-2">
                        @csrf
                        @method('PATCH')
                        <input type="date" name="enrollment_date" value="{{ $activeEnrollment->enrollment_date->toDateString() }}" class="rounded-lg border border-gray-200 px-2 py-1 text-xs">
                        <button type="submit" class="rounded-lg bg-[#1A3A6B] px-2.5 py-1 text-[11px] font-semibold text-white">Mettre à jour</button>
                    </form>
                    @endcan
                </div>
            </div>

            {{-- Année d'entrée --}}
            <div class="flex items-center gap-4 p-4 rounded-xl
                        bg-gray-50 border border-gray-100">
                <div class="w-11 h-11 rounded-xl flex items-center
                            justify-center flex-shrink-0"
                     style="background-color:#EBF3FB;">
                    <svg class="w-5 h-5" style="color:#1A3A6B;" fill="none"
                         stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              stroke-width="2"
                              d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3
                                 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs text-gray-400">Année d'entrée</p>
                    <p class="text-sm font-bold text-gray-800">
                        {{ $student->enrollments
                            ->sortBy('created_at')
                            ->first()
                            ?->academicYear
                            ?->start_date?->format('Y') ?? '—' }}
                    </p>
                </div>
            </div>

            {{-- Établissement d'origine --}}
            <div class="flex items-center gap-4 p-4 rounded-xl
                        bg-gray-50 border border-gray-100">
                <div class="w-11 h-11 rounded-xl flex items-center
                            justify-center flex-shrink-0"
                     style="background-color:#EBF3FB;">
                    <svg class="w-5 h-5" style="color:#1A3A6B;" fill="none"
                         stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              stroke-width="2"
                              d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m
                                 -2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m
                                 -5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs text-gray-400">Établissement d'origine</p>
                    <p class="text-sm font-bold text-gray-800">
                        {{ $activeEnrollment->origin_school ?? 'COPTAN' }}
                    </p>
                </div>
            </div>

        </div>
    </div>
    @endif

    {{-- Ligne 3 : Statistiques rapides --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">

        {{-- Moyenne Générale --}}
        <div class="rounded-2xl p-5 text-white"
             style="background-color:#1A3A6B;">
            <div class="flex items-center gap-2 mb-3">
                <svg class="w-4 h-4 opacity-70" fill="none"
                     stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          stroke-width="2"
                          d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v2
                             a2 2 0 01-2 2H6a2 2 0 01-2-2V4z"/>
                </svg>
                <span class="text-xs font-semibold opacity-70 uppercase
                             tracking-wider">
                    Moyenne Générale
                </span>
            </div>
            <p class="text-4xl font-black mb-1">
                —<span class="text-2xl">/20</span>
            </p>
            <p class="text-xs opacity-60">
                Disponible après la saisie des notes
            </p>
        </div>

        {{-- Absences --}}
        <div class="rounded-2xl p-5 text-white"
             style="background-color:#E87722;">
            <div class="flex items-center gap-2 mb-3">
                <svg class="w-4 h-4 opacity-70" fill="none"
                     stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          stroke-width="2"
                          d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502
                             -1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333
                             -3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <span class="text-xs font-semibold opacity-70 uppercase
                             tracking-wider">
                    Absences
                </span>
            </div>
            @php
                $absences = $activeEnrollment
                    ? $activeEnrollment->absences()->sum('hours')
                    : 0;
            @endphp
            <p class="text-4xl font-black mb-1">
                {{ number_format($absences, 1) }}
                <span class="text-2xl">h</span>
            </p>
            <p class="text-xs opacity-70">
                Total cette année
            </p>
        </div>

        {{-- Progression du cursus --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold text-gray-500 uppercase
                             tracking-wider">
                    Progression du cursus
                </span>
                @php
                    $totalYears  = $student->enrollments->count();
                    // Progression basée sur nombre d'années
                    $maxYears    = 7; // Durée max typique lycée
                    $progression = $totalYears > 0
                        ? min(round(($totalYears / $maxYears) * 100), 100)
                        : 0;
                @endphp
                <span class="text-xl font-black" style="color:#E87722;">
                    {{ $progression }}%
                </span>
            </div>
            <div class="h-2.5 bg-gray-100 rounded-full overflow-hidden">
                <div class="h-full rounded-full transition-all"
                     style="width:{{ $progression }}%;
                            background-color:#E87722;">
                </div>
            </div>
            <p class="text-xs text-gray-400 mt-2">
                {{ $totalYears }} année(s) sur ~{{ $maxYears }}
                dans l'établissement
            </p>
        </div>

    </div>

</div>

{{-- ══════════════════════════════════════════════════════════════════════ --}}
{{-- ONGLET : NOTES & MOYENNES                                               --}}
{{-- ══════════════════════════════════════════════════════════════════════ --}}
<div x-show="tab === 'notes'" x-transition>
    @if($activeEnrollment)
        @php
            $notesItems = collect();
            if ($activeEnrollment->classGroup) {
                $notesItems = $activeEnrollment->classGroup->classSubjects
                    ->filter(fn ($classSubject) => (bool) $classSubject->is_active)
                    ->map(function ($classSubject) use ($activeEnrollment) {
                        $grades = $activeEnrollment->grades
                            ->where('class_subject_id', $classSubject->id)
                            ->filter(fn ($grade) => $grade->class_subject_id === $classSubject->id);

                        $validGrades = $grades->filter(fn ($grade) => $grade->grade !== null && ! $grade->is_absent);
                        $latestGrade = $validGrades->sortByDesc(fn ($grade) => $grade->sequence?->number ?? 0)->first();
                        $average = $validGrades->isNotEmpty() ? round($validGrades->avg('grade'), 2) : null;

                        return (object) [
                            'classSubject' => $classSubject,
                            'subjectName' => $classSubject->subject?->name_fr ?? $classSubject->subject?->name_en ?? 'Matière',
                            'average' => $average,
                            'latestGrade' => $latestGrade,
                            'gradeCount' => $validGrades->count(),
                        ];
                    })
                    ->filter(fn ($item) => $item->subjectName !== 'Matière' || $item->gradeCount > 0)
                    ->values();
            }

            $notesAverage = $notesItems->filter(fn ($item) => $item->average !== null)->avg('average');
        @endphp

        <div class="space-y-5">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <div class="inline-flex items-center gap-2 rounded-full bg-[#EBF3FB] px-3 py-1 text-[11px] font-bold uppercase tracking-[0.2em] text-[#1A3A6B]">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                            Notes & Moyennes
                        </div>
                        <h3 class="mt-3 text-lg font-black text-[#1A3A6B]">Suivi académique de {{ $student->first_name }}</h3>
                        <p class="mt-1 text-sm text-gray-500">Vue synthétique des moyennes disponibles pour l’année active.</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                        <div class="text-[11px] font-bold uppercase tracking-[0.2em] text-slate-400">Moyenne générale</div>
                        <div class="mt-1 text-2xl font-black text-[#1A3A6B]">
                            {{ $notesAverage !== null ? number_format((float) $notesAverage, 2, ',', ' ') : '—' }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
                @forelse($notesItems as $item)
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-slate-900">{{ $item->subjectName }}</p>
                                <p class="mt-1 text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">
                                    {{ $item->gradeCount > 0 ? $item->gradeCount . ' note(s) saisie(s)' : 'Aucune note saisie' }}
                                </p>
                            </div>
                            <div class="rounded-full bg-[#F7F5FF] px-3 py-1 text-sm font-black text-[#1A3A6B]">
                                {{ $item->average !== null ? number_format((float) $item->average, 2, ',', ' ') : '—' }}
                            </div>
                        </div>
                        @if($item->latestGrade)
                            <div class="mt-4 rounded-xl bg-slate-50 px-3 py-2 text-sm text-slate-600">
                                <span class="font-semibold text-slate-700">Dernière note :</span>
                                {{ $item->latestGrade->grade }}
                                @if($item->latestGrade->sequence)
                                    <span class="ml-2 text-slate-400">• {{ $item->latestGrade->sequence->label }}</span>
                                @endif
                            </div>
                        @else
                            <div class="mt-4 rounded-xl border border-dashed border-slate-200 px-3 py-2 text-sm text-slate-400">
                                Aucune note validée n’est encore disponible pour cette matière.
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="lg:col-span-2 rounded-2xl border border-dashed border-slate-200 bg-white p-10 text-center text-sm text-slate-500">
                        Aucune matière n’est encore associée à cette inscription.
                    </div>
                @endforelse
            </div>
        </div>
    @else
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-10 text-center">
            <div class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4" style="background-color:#FEF3EA;">
                <svg class="w-8 h-8" style="color:#E87722;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
            </div>
            <p class="text-gray-500 font-medium mb-1">Aucune inscription active</p>
            <p class="text-sm text-gray-400">Impossible de consulter les notes tant que l’élève n’a pas d’inscription active pour l’année en cours.</p>
        </div>
    @endif
</div>

{{-- ══════════════════════════════════════════════════════════════════════ --}}
{{-- ONGLET : ABSENCES                                                       --}}
{{-- ══════════════════════════════════════════════════════════════════════ --}}
<div x-show="tab === 'absences'" x-transition>
    @if($activeEnrollment)
    <div class="space-y-5">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <div class="inline-flex items-center gap-2 rounded-full bg-[#FEF3EA] px-3 py-1 text-[11px] font-bold uppercase tracking-[0.2em] text-[#E87722]">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        Suivi des absences
                    </div>
                    <h3 class="mt-3 text-lg font-black text-[#1A3A6B]">Historique des absences de {{ $student->first_name }}</h3>
                    <p class="mt-1 text-sm text-gray-500">Vue synthétique pour l’année active, avec accès au détail complet.</p>
                </div>
                <a href="{{ route('absences.student', $activeEnrollment) }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-[#E87722] px-4 py-2 text-sm font-semibold text-[#E87722] transition hover:bg-[#FFF7ED]">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7m0 0l-7 7m7-7H4"/>
                    </svg>
                    Voir toutes les absences
                </a>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            @php
                $absenceTotal = (float) $activeEnrollment->absences->sum('hours');
                $absenceJustified = (float) $activeEnrollment->absences->where('is_justified', true)->sum('hours');
                $absenceUnjustified = (float) $activeEnrollment->absences->where('is_justified', false)->sum('hours');
            @endphp
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wide text-gray-400">Total heures</p>
                <p class="mt-2 text-2xl font-black text-[#1A3A6B]">{{ number_format($absenceTotal, 1, ',', ' ') }} h</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wide text-gray-400">Justifiées</p>
                <p class="mt-2 text-2xl font-black text-[#1A5C2A]">{{ number_format($absenceJustified, 1, ',', ' ') }} h</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wide text-gray-400">Non justifiées</p>
                <p class="mt-2 text-2xl font-black text-[#E87722]">{{ number_format($absenceUnjustified, 1, ',', ' ') }} h</p>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="border-b border-gray-100 px-5 py-4">
                <h4 class="font-black text-[#1A3A6B]">Dernières absences enregistrées</h4>
            </div>
            @if($activeEnrollment->absences->isEmpty())
                <div class="px-6 py-10 text-center text-sm text-gray-500">
                    Aucune absence n’a encore été enregistrée pour cet élève.
                </div>
            @else
                <div class="divide-y divide-gray-100">
                    @foreach($activeEnrollment->absences->take(8) as $absence)
                        <div class="flex flex-col gap-3 px-5 py-4 md:flex-row md:items-center md:justify-between">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="text-sm font-semibold text-gray-800">{{ $absence->absence_date?->format('d/m/Y') ?? '—' }}</span>
                                    @if($absence->is_justified)
                                        <span class="rounded-full bg-green-100 px-2.5 py-1 text-[11px] font-bold uppercase tracking-wide text-green-700">Justifiée</span>
                                    @else
                                        <span class="rounded-full bg-amber-100 px-2.5 py-1 text-[11px] font-bold uppercase tracking-wide text-amber-700">Non justifiée</span>
                                    @endif
                                </div>
                                <p class="mt-1 text-sm text-gray-500">
                                    {{ $absence->classSubject?->subject?->name_fr ?? 'Absence générale' }} · {{ number_format((float) $absence->hours, 1, ',', ' ') }} h
                                </p>
                            </div>
                            <div class="text-sm text-gray-500">
                                {{ $absence->period ?? 'Journée' }}
                                @if($absence->timetableSlot)
                                    @php $periodOffset = max(0, (int) $absence->timetable_period_index - (int) $absence->timetableSlot->period_index); @endphp
                                    <span class="block text-xs text-gray-400">
                                        {{ $absence->timetableSlot->start_time?->copy()->addHours($periodOffset)->format('H:i') }}-{{ $absence->timetableSlot->start_time?->copy()->addHours($periodOffset + 1)->format('H:i') }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
    @else
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-10 text-center">
        <div class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4" style="background-color:#FEF3EA;">
            <svg class="w-8 h-8" style="color:#E87722;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
        </div>
        <p class="text-gray-500 font-medium mb-1">Aucune inscription active</p>
        <p class="text-sm text-gray-400">Impossible de consulter les absences tant que l’élève n’a pas d’inscription active pour l’année en cours.</p>
    </div>
    @endif
</div>

{{-- ══════════════════════════════════════════════════════════════════════ --}}
{{-- ONGLET : FINANCES                                                       --}}
{{-- ══════════════════════════════════════════════════════════════════════ --}}
<div x-show="tab === 'finances'" x-transition>
    @if($activeEnrollment)
    <div class="text-center py-4">
        <a href="{{ route('finances.student', $activeEnrollment) }}"
           class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl
                  text-white font-semibold text-sm"
           style="background-color:#1A5C2A;">
            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                 viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      stroke-width="2"
                      d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2
                         4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2
                         0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            Voir le compte financier
        </a>
    </div>
    @else
    <p class="text-center text-sm text-gray-400 py-8">
        Élève non inscrit — aucun compte financier disponible.
    </p>
    @endif
</div>

{{-- ══════════════════════════════════════════════════════════════════════ --}}
{{-- ONGLET : HISTORIQUE                                                     --}}
{{-- ══════════════════════════════════════════════════════════════════════ --}}
<div x-show="tab === 'history'" x-transition>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center
                    justify-between">
            <h3 class="font-semibold text-sm" style="color:#1A3A6B;">
                Historique scolaire
            </h3>
            @can('manage-students')
            @if($canEnroll && $activeYear)
            <a href="{{ route('students.enroll', $student) }}"
               class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg
                      text-white text-xs font-medium"
               style="background-color:#1A5C2A;">
                + Renouveler
            </a>
            @elseif(!$activeEnrollment && $isEditable)
            <a href="{{ route('students.enroll', $student) }}"
               class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg
                      text-white text-xs font-medium"
               style="background-color:#1A5C2A;">
                + Inscrire
            </a>
            @endif
            @endcan
        </div>

        @if($student->enrollments->isEmpty())
        <div class="px-5 py-10 text-center text-gray-400">
            <p class="text-sm">Aucune inscription enregistrée.</p>
        </div>
        @else
        <div class="divide-y divide-gray-50">
            @foreach($student->enrollments->sortByDesc('created_at')
                as $enr)
            @php
                $statusConf = [
                    'active'          => ['bg' => '#D1FAE5', 'text' => '#065F46',
                                          'label' => 'Actif(ve)'],
                    'inactive'        => ['bg' => '#FEF3C7', 'text' => '#92400E',
                                          'label' => 'Clôturée'],
                    'transferred'     => ['bg' => '#FEF3C7', 'text' => '#92400E',
                                          'label' => 'Transféré(e)'],
                    'transferred_out' => ['bg' => '#FEF3C7', 'text' => '#92400E',
                                          'label' => 'Transféré(e)'],
                    'withdrawn'       => ['bg' => '#FEE2E2', 'text' => '#991B1B',
                                          'label' => 'Retiré(e)'],
                    'excluded'        => ['bg' => '#F3F4F6', 'text' => '#374151',
                                          'label' => 'Exclu(e)'],
                ];
                $sc = $statusConf[$enr->status] ?? $statusConf['active'];
            @endphp
            <div class="px-5 py-4 flex items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl flex items-center
                                justify-center flex-shrink-0 text-white
                                text-xs font-bold"
                         style="background-color:#1A3A6B;">
                        {{ substr($enr->academicYear->label, 2, 2) }}
                        -
                        {{ substr($enr->academicYear->label, 7, 2) }}
                    </div>
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <p class="text-sm font-semibold text-gray-800">
                                {{ $enr->academicYear->label }}
                            </p>
                            <span class="px-2 py-0.5 rounded-full text-xs
                                         font-medium"
                                  style="background-color:{{ $sc['bg'] }};
                                         color:{{ $sc['text'] }};">
                                {{ $sc['label'] }}
                            </span>
                            @if($enr->is_repeating)
                            <span class="px-2 py-0.5 rounded-full text-xs
                                         font-medium bg-amber-100 text-amber-700">
                                Redoublant(e)
                            </span>
                            @endif
                        </div>
                        <p class="text-sm text-gray-500 mt-0.5">
                            {{ $enr->classGroup->full_name }}
                            ·
                            {{ $enr->classGroup->level->section->name }}
                        </p>
                        <p class="text-xs text-gray-400">
                            Inscrit(e) le
                            {{ $enr->enrollment_date->format('d/m/Y') }}
                        </p>
                    </div>
                </div>

                {{-- Actions sur l'inscription --}}
                {{-- @can('manage-students')
                @if($enr->status === 'active')
                <div class="flex flex-col items-end gap-2">
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open"
                                class="p-2 rounded-lg text-gray-400
                                       hover:bg-gray-100 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                 viewBox="0 0 24 24">
                                <path stroke-linecap="round"
                                      stroke-linejoin="round" stroke-width="2"
                                      d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1
                                         0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1
                                         0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/>
                            </svg>
                        </button>
                        <div x-show="open" @click.away="open = false"
                             class="absolute right-0 mt-1 w-56 bg-white
                                    rounded-xl shadow-lg border border-gray-200
                                    z-10 py-2">
                            <form method="POST"
                                  action="{{ route('students.enrollments.status',
                                                   $enr) }}">
                                @csrf @method('PATCH')
                                @foreach([
                                    'transferred' => '→ Marquer transféré(e)',
                                    'withdrawn'   => 'Marquer retiré(e)',
                                    'excluded'    => '⊘ Marquer exclu(e)',
                                ] as $val => $lbl)
                                <button type="submit" name="status"
                                        value="{{ $val }}"
                                        class="w-full text-left px-4 py-2 text-sm
                                               text-gray-700 hover:bg-gray-50">
                                    {{ $lbl }}
                                </button>
                                @endforeach
                            </form>
                            <div class="border-t border-gray-100 mt-2 pt-2 px-3 space-y-2">
                                <form method="POST" action="{{ route('students.enrollments.update-date', $enr) }}" class="space-y-1">
                                    @csrf @method('PATCH')
                                    <label class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Date d'inscription</label>
                                    <input type="date" name="enrollment_date" value="{{ $enr->enrollment_date->toDateString() }}" class="w-full rounded-lg border border-gray-200 px-2 py-1 text-sm">
                                    <button type="submit" class="w-full rounded-lg bg-[#1A3A6B] px-2.5 py-1.5 text-xs font-semibold text-white">Enregistrer</button>
                                </form>
                                @if($transferClasses->isNotEmpty())
                                <form method="POST" action="{{ route('students.enrollments.transfer', $enr) }}" class="space-y-1">
                                    @csrf @method('PATCH')
                                    <label class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Transférer vers</label>
                                    <select name="new_class_id" class="w-full rounded-lg border border-gray-200 px-2 py-1 text-sm">
                                        @foreach($transferClasses as $class)
                                            @if($class->id !== $enr->class_group_id)
                                            <option value="{{ $class->id }}">{{ $class->full_name }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                    <button type="submit" class="w-full rounded-lg bg-[#1A5C2A] px-2.5 py-1.5 text-xs font-semibold text-white">Transférer</button>
                                </form>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                @endif
                @endcan --}}
            </div>
            @endforeach
        </div>
        @endif
    </div>
</div>

</div>{{-- end x-data --}}

@endsection
