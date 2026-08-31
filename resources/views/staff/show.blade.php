@extends('layouts.app')

@section('title', $staff->full_name)
@section('page-title', 'Fiche du Personnel : ' . $staff->full_name)
@section('page-subtitle', 'Détails et informations complètes du personnel ' . $staff->full_name)

@section('breadcrumb')
    <a href="{{ route('staff.index') }}" class="hover:text-gray-700">
        Personnel
    </a>
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
    </svg>
    <span class="font-medium text-[#A35200]">
        {{ $staff->full_name }}
    </span>
@endsection

@section('content')
<style>
    .staff-details-page .staff-tabs {
        overflow-x: auto;
        scrollbar-width: thin;
        -webkit-overflow-scrolling: touch;
    }

    .staff-details-page .staff-tabs > button {
        flex: 0 0 auto;
        white-space: nowrap;
    }

    .staff-details-page .staff-timetable-scroll th,
    .staff-details-page .staff-timetable-scroll td {
        font-size: 11px;
    }

    .staff-details-page .staff-timetable-scroll th:not(:first-child) {
        padding: 10px 4px;
    }

    .staff-details-page .staff-timetable-scroll td {
        padding: 8px 4px;
    }
</style>

<div x-data="{ tab: 'info' }" class="staff-details-page space-y-6 -mx-2 -mt-2 -mb-2 lg:-mx-4 lg:-mt-4 lg:-mb-4">

    {{-- ── CARD D'EN-TÊTE PREMIUM (Mockup 1 Style) ───────────────────────────────────────────── --}}
    <div class="bg-gradient-to-r from-[#0C3260] to-[#0A2240] rounded-2xl shadow-sm overflow-hidden p-6 text-white relative">
        <div class="flex flex-col md:flex-row items-center md:items-start justify-between gap-6">
            
            <div class="flex flex-col sm:flex-row items-center gap-6">
                {{-- Avatar + Badge Actif/Inactif --}}
                <div class="relative flex-shrink-0">
                    @if($staff->photo)
                        <img src="{{ $staff->photo_url }}" alt="{{ $staff->full_name }}"
                             class="w-24 h-24 rounded-full object-cover ring-4 ring-[#194E80]">
                    @else
                        @php
                            $words = explode(' ', $staff->full_name);
                            $initials = '';
                            foreach($words as $w) {
                                if($w) $initials .= strtoupper(substr($w, 0, 1));
                            }
                            $initials = substr($initials, 0, 2);
                        @endphp
                        <div class="w-24 h-24 rounded-full flex items-center justify-center font-black text-2xl bg-[#152e4d] text-white ring-4 ring-[#194E80]">
                            {{ $initials }}
                        </div>
                    @endif
                    
                    {{-- Status Badge --}}
                    @if($staff->is_active)
                        <span class="absolute -bottom-1.5 left-1/2 -translate-x-1/2 bg-[#2ECC71] text-white text-[9px] font-black px-2.5 py-0.5 rounded-full uppercase tracking-wider shadow border border-white">
                            Actif
                        </span>
                    @else
                        <span class="absolute -bottom-1.5 left-1/2 -translate-x-1/2 bg-[#E74C3C] text-white text-[9px] font-black px-2.5 py-0.5 rounded-full uppercase tracking-wider shadow border border-white">
                            Inactif
                        </span>
                    @endif
                </div>

                {{-- Name & Contact Info --}}
                <div class="text-center sm:text-left">
                    <h2 class="text-xl lg:text-2xl font-bold text-white uppercase tracking-wide">
                        {{ $staff->full_name }}
                    </h2>
                    
                    @php
                        $subtext = $staff->primaryPosition?->position_label ?? 'Personnel';
                        if ($staff->isTeacher()) {
                            $subjectsList = $staff->teacherAssignments
                                ->map(fn($ta) => $ta->classSubject?->subject?->name_fr)
                                ->filter()
                                ->unique()
                                ->implode(' & ');
                            if ($subjectsList) {
                                $subtext .= ' — ' . $subjectsList;
                            }
                        }
                    @endphp
                    <p class="text-sm font-medium text-blue-200 mt-1">
                        {{ $subtext }}
                    </p>

                    <div class="flex flex-wrap justify-center sm:justify-start items-center gap-x-6 gap-y-2 mt-4 text-sm text-blue-100">
                        @if($staff->phone)
                        <a href="tel:{{ $staff->phone }}" class="flex items-center gap-2 hover:text-white transition-colors">
                            <svg class="w-4 h-4 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.94.725l.548 2.2a1 1 0 01-.321.988l-1.305.98a10.582 10.582 0 004.872 4.872l.98-1.305a1 1 0 01.988-.321l2.2.548a1 1 0 01.725.94V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                            <span>{{ $staff->phone }}</span>
                        </a>
                        @endif

                        @if($staff->email)
                        <a href="mailto:{{ $staff->email }}" class="flex items-center gap-2 hover:text-white transition-colors">
                            <svg class="w-4 h-4 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            <span class="truncate max-w-[200px]">{{ $staff->email }}</span>
                        </a>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex items-center gap-3 w-full md:w-auto justify-center md:justify-end flex-wrap">
                @can('manage-staff')
                <a href="{{ route('staff.edit', $staff) }}"
                   class="flex items-center gap-2 px-4 py-2 border border-white/30 rounded-lg text-white text-sm font-semibold hover:bg-white/10 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                    </svg>
                    Modifier
                </a>
                @endcan

                @if($staff->user)
                <a href="{{ route('users.edit', $staff->user) }}"
                   class="flex items-center gap-2 px-4 py-2 bg-white rounded-lg text-gray-900 text-sm font-semibold hover:bg-gray-50 transition-colors shadow">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    Gérer le compte
                </a>
                @endif

                {{-- Supprimer (avec confirmation) --}}
                @can('manage-staff')
                <form method="POST" action="{{ route('staff.destroy', $staff) }}" 
                      class="inline"
                      onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer {{ $staff->full_name }} ? Cette action est irréversible.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="flex items-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-700 rounded-lg text-white text-sm font-semibold transition-colors shadow">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Supprimer
                    </button>
                </form>
                @endcan
            </div>
        </div>
    </div>

    {{-- ── TAB BAR ───────────────────────────────────────────────────────────── --}}
    <div class="border-b border-gray-200">
        <nav class="staff-tabs flex flex-nowrap -mb-px gap-6" aria-label="Tabs">
            <button @click="tab = 'info'"
                    :class="tab === 'info' ? 'border-[#A35200] text-[#A35200]' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="flex items-center gap-2 py-4 px-1 border-b-2 font-semibold text-sm transition-all">
                <svg class="w-5 h-5 text-gray-400 group-hover:text-gray-500" :class="tab === 'info' ? 'text-[#A35200]' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Informations
            </button>

            <button @click="tab = 'classes'"
                    :class="tab === 'classes' ? 'border-[#A35200] text-[#A35200]' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="flex items-center gap-2 py-4 px-1 border-b-2 font-semibold text-sm transition-all">
                <svg class="w-5 h-5 text-gray-400 group-hover:text-gray-500" :class="tab === 'classes' ? 'text-[#A35200]' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
                Classes assignées
            </button>

            <button @click="tab = 'schedule'"
                    :class="tab === 'schedule' ? 'border-[#A35200] text-[#A35200]' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="flex items-center gap-2 py-4 px-1 border-b-2 font-semibold text-sm transition-all">
                <svg class="w-5 h-5 text-gray-400 group-hover:text-gray-500" :class="tab === 'schedule' ? 'text-[#A35200]' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Emploi du temps
            </button>

            <button @click="tab = 'presence'"
                    :class="tab === 'presence' ? 'border-[#A35200] text-[#A35200]' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="flex items-center gap-2 py-4 px-1 border-b-2 font-semibold text-sm transition-all">
                <svg class="w-5 h-5 text-gray-400 group-hover:text-gray-500" :class="tab === 'presence' ? 'text-[#A35200]' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Présences
            </button>
        </nav>
    </div>

    {{-- ── CONTENU DES ONGLETS ───────────────────────────────────────────────── --}}
    
    {{-- 1. INFORMATIONS (Deux Colonnes) --}}
    <div x-show="tab === 'info'" class="grid grid-cols-1 lg:grid-cols-5 gap-6" x-transition>
        
        {{-- Colonne Gauche (Informations Personnelles & Performance) --}}
        <div class="lg:col-span-2 space-y-6">
            
            {{-- Informations personnelles --}}
            <div class="bg-white border border-gray-100 rounded-2xl shadow-sm overflow-hidden p-5">
                <div class="flex items-center justify-between pb-3 border-b border-gray-100 mb-4">
                    <h3 class="text-sm font-bold uppercase tracking-wider text-gray-400">
                        Informations personnelles
                    </h3>
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                
                <dl class="divide-y divide-gray-50 text-sm">
                    <div class="py-3 flex justify-between gap-4">
                        <dt class="text-gray-500">Date de naissance</dt>
                        <dd class="font-bold text-gray-800">{{ $staff->date_of_birth ? $staff->date_of_birth->format('d/m/Y') : '—' }}</dd>
                    </div>
                    <div class="py-3 flex justify-between gap-4">
                        <dt class="text-gray-500">Sexe</dt>
                        <dd class="font-bold text-gray-800">{{ $staff->gender === 'M' ? 'M' : 'F' }}</dd>
                    </div>
                    <div class="py-3 flex justify-between gap-4">
                        <dt class="text-gray-500">Diplôme le plus élevé</dt>
                        <dd class="font-bold text-gray-800 text-right">{{ $staff->diploma ?? '—' }}</dd>
                    </div>
                    <div class="py-3 flex justify-between gap-4">
                        <dt class="text-gray-500">Établissement d'origine</dt>
                        <dd class="font-bold text-gray-800 text-right">{{ $staff->origin_school ?? '—' }}</dd>
                    </div>
                    <div class="py-3 flex justify-between gap-4">
                        <dt class="text-gray-500">Date d'embauche</dt>
                        <dd class="font-bold text-gray-800">{{ $staff->start_date ? $staff->start_date->format('d/m/Y') : '—' }}</dd>
                    </div>
                    <div class="py-3 flex justify-between gap-4">
                        <dt class="text-gray-500">Type de contrat</dt>
                        <dd class="font-bold text-gray-800">{{ $staff->contract_label }}</dd>
                    </div>
                </dl>
            </div>

            @if($staff->censeurAssignments->isNotEmpty())
            {{-- Affectation Préfet des études --}}
            <div class="bg-white border border-blue-100 rounded-2xl shadow-sm p-5">
                <div class="flex items-center justify-between pb-3 border-b border-gray-100 mb-4">
                    <h3 class="text-sm font-bold uppercase tracking-wider text-[#1A3A6B] flex items-center gap-2">
                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5m0 0h4m-4 0V11m0 0l3-3m-3 3l-3-3" />
                        </svg>
                        Affectation Préfet des études
                    </h3>
                    <span class="px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider rounded-full bg-blue-50 text-blue-700">Sections & Cycles</span>
                </div>

                @php
                    $groupedAssignments = $staff->censeurAssignments->groupBy('section_id');
                @endphp

                <div class="space-y-3 text-xs">
                    @foreach($groupedAssignments as $secId => $assigns)
                    @php
                        $section = $assigns->first()->section;
                        $cycles = $assigns->pluck('cycle_label')->join(', ');
                    @endphp
                    <div class="flex items-center justify-between p-3 rounded-xl bg-blue-50/50 border border-blue-100">
                        <span class="font-bold text-gray-800 flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-blue-600"></span>
                            {{ $section?->name }} <span class="text-gray-400 font-normal">({{ $section?->code }})</span>
                        </span>
                        <span class="font-bold text-blue-900 bg-white px-2.5 py-1 rounded-lg border border-blue-200">
                            {{ $cycles }}
                        </span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Performance d'enseignement --}}
            <div class="bg-white border border-gray-100 rounded-2xl shadow-sm p-5">
                <div class="flex items-center gap-2 pb-3 border-b border-gray-100 mb-4">
                    <svg class="w-4 h-4 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                    </svg>
                    <h3 class="text-sm font-bold uppercase tracking-wider text-gray-400">
                        Performance d'enseignement
                    </h3>
                </div>

                <div class="space-y-4 text-xs font-semibold">
                    <div>
                        <div class="flex justify-between text-gray-700 mb-1">
                            <span>Taux de présence</span>
                            <span>98%</span>
                        </div>
                        <div class="w-full bg-gray-100 h-2.5 rounded-full overflow-hidden">
                            <div class="bg-[#1A3A6B] h-2.5 rounded-full" style="width: 98%"></div>
                        </div>
                    </div>

                    <div>
                        <div class="flex justify-between text-gray-700 mb-1">
                            <span>Moyenne générale classes</span>
                            <span>14.5/20</span>
                        </div>
                        <div class="w-full bg-gray-100 h-2.5 rounded-full overflow-hidden">
                            <div class="bg-[#A35200] h-2.5 rounded-full" style="width: 72.5%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Colonne Droite (Matières Enseignées) --}}
        <div class="lg:col-span-3">
            <div class="bg-white border border-gray-100 rounded-2xl shadow-sm overflow-hidden flex flex-col h-full p-5">
                
                <div class="flex items-center justify-between pb-3 border-b border-gray-100 mb-4">
                    <h3 class="text-sm font-bold uppercase tracking-wider text-gray-400">
                        Matières enseignées
                    </h3>
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                </div>

                @php
                    // Group teacher assignments by subject
                    $subjectStats = $staff->teacherAssignments
                        ->filter(fn($ta) => $ta->classSubject?->subject)
                        ->groupBy(fn($ta) => $ta->classSubject->subject->id)
                        ->map(function($assignments) {
                            $first = $assignments->first();
                            $subjectName = $first->classSubject->subject->name_fr;
                            $coefSum = $assignments->sum(fn($ta) => $ta->classSubject->coefficient);
                            $count = $assignments->count();
                            $avgCoef = $count > 0 ? round($coefSum / $count, 1) : 0;
                            
                            return [
                                'name' => $subjectName,
                                'avg_coef' => $avgCoef,
                                'class_count' => $assignments->map(fn($ta) => $ta->classSubject->class_group_id)->unique()->count(),
                                'code' => $first->classSubject->subject->code,
                            ];
                        });
                @endphp

                <div class="flex-1 overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-100 text-left text-xs font-bold text-gray-400 uppercase tracking-wider">
                                <th class="pb-3 w-1/2">Matière</th>
                                {{-- <th class="pb-3 text-center">Coefficient Moyen</th> --}}
                                <th class="pb-3 text-right">Nb de classes</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @forelse($subjectStats as $stats)
                            <tr class="align-middle">
                                <td class="py-4">
                                    <div class="flex items-center gap-3">
                                        @php
                                            $isMath = str_contains(strtoupper($stats['code']), 'MATH') || str_contains(strtoupper($stats['name']), 'MATH');
                                            $isPhys = str_contains(strtoupper($stats['code']), 'PHYS') || str_contains(strtoupper($stats['code']), 'ELEC') || str_contains(strtoupper($stats['name']), 'PHYS');
                                            
                                            $iconBg = $isMath ? 'bg-blue-50 text-blue-700' : ($isPhys ? 'bg-[#FDF2E9] text-[#A35200]' : 'bg-gray-50 text-gray-600');
                                        @endphp
                                        <div class="w-8 h-8 rounded-lg flex items-center justify-center font-semibold {{ $iconBg }}">
                                            @if($isMath)
                                                &Sigma;
                                            @elseif($isPhys)
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                </svg>
                                            @else
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                                </svg>
                                            @endif
                                        </div>
                                        <span class="font-bold text-gray-800">{{ $stats['name'] }}</span>
                                    </div>
                                </td>
                                {{-- <td class="py-4 text-center font-bold text-gray-700">
                                    {{ $stats['avg_coef'] }}
                                </td> --}}
                                <td class="py-4 text-right">
                                    @php
                                        $badgeBg = $isMath ? 'bg-blue-50 text-blue-700' : ($isPhys ? 'bg-[#FDF2E9] text-[#A35200]' : 'bg-gray-100 text-gray-700');
                                    @endphp
                                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold {{ $badgeBg }}">
                                        {{ $stats['class_count'] }} {{ $stats['class_count'] > 1 ? 'classes' : 'classe' }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="py-12 text-center text-gray-400 italic">
                                    Aucune attribution de matière enregistrée.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Action Footer --}}
                <div class="border-t border-gray-100 pt-3 mt-4 text-right">
                    <a href="{{ route('subjects.index') }}" class="inline-flex items-center gap-1 text-xs font-bold text-[#A35200] hover:underline uppercase tracking-wide">
                        Modifier les attributions
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- 2. CLASSES ASSIGNÉES --}}
    <div x-show="tab === 'classes'" class="bg-white border border-gray-100 rounded-2xl shadow-sm p-6" x-transition>
        <h3 class="text-lg font-bold text-[#0c3260] mb-4">Classes & Groupes assignés</h3>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
            @forelse($staff->teacherAssignments->groupBy(fn($ta) => $ta->classSubject?->classGroup?->id) as $classGroupId => $assignments)
                @php
                    $class = $assignments->first()->classSubject?->classGroup;
                @endphp
                @if($class)
                <div class="border border-gray-200 rounded-xl p-4 flex flex-col justify-between hover:border-orange-300 transition-colors">
                    <div>
                        <h4 class="font-bold text-gray-900 text-base mb-1">{{ $class->name }}</h4>
                        <p class="text-xs text-gray-400 mb-3">{{ $class->academicYear->label }}</p>
                        
                        <div class="flex flex-wrap gap-1">
                            @foreach($assignments as $ta)
                                @if($ta->classSubject?->subject)
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-600">
                                    {{ $ta->classSubject->subject->name_fr }}
                                </span>
                                @endif
                            @endforeach
                        </div>
                    </div>
                    
                    <a href="{{ route('classes.show', $class) }}" class="text-xs font-bold text-[#A35200] hover:underline mt-4 inline-flex items-center gap-1">
                        Accéder à la classe
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>
                @endif
            @empty
                <p class="col-span-full py-8 text-center text-gray-400 italic">Aucune classe assignée pour ce membre.</p>
            @endforelse
        </div>
    </div>

    {{-- 3. EMPLOI DU TEMPS --}}
    <div x-show="tab === 'schedule'" class="bg-white border border-gray-100 rounded-2xl shadow-sm p-6" x-transition>
        <h3 class="text-lg font-bold text-[#0c3260] mb-4">Emploi du temps hebdomadaire</h3>

        @php
            $days = [1 => 'Lundi', 2 => 'Mardi', 3 => 'Mercredi', 4 => 'Jeudi', 5 => 'Vendredi'];
        @endphp

        @if($scheduleSlots->isEmpty())
            <div class="rounded-2xl border border-dashed border-gray-200 bg-gray-50 p-10 text-center text-gray-500">
                <p class="font-semibold">Aucun emploi du temps disponible pour cet enseignant cette année.</p>
                <p class="text-sm mt-2">Vérifiez les attributions de classe ou le planning dans le module d'emploi du temps.</p>
            </div>
        @else
            <div class="grid gap-4 lg:grid-cols-[1.4fr_1fr_1fr] mb-6">
                <div class="bg-[#F8FBFE] rounded-2xl border border-[#E5EDF5] p-4">
                    <p class="text-xs font-bold uppercase tracking-wide text-gray-400">Cours par semaine</p>
                    <p class="mt-2 text-3xl font-black text-[#1A3A6B]">{{ $scheduleSlots->count() }}</p>
                </div>
                <div class="bg-[#F8FBFE] rounded-2xl border border-[#E5EDF5] p-4">
                    <p class="text-xs font-bold uppercase tracking-wide text-gray-400">Périodes totales</p>
                    <p class="mt-2 text-3xl font-black text-[#1A5C2A]">{{ $scheduleTotalHours }}</p>
                </div>
                <div class="bg-[#F8FBFE] rounded-2xl border border-[#E5EDF5] p-4">
                    <p class="text-xs font-bold uppercase tracking-wide text-gray-400">Matières</p>
                    <p class="mt-2 text-3xl font-black text-[#0C3260]">{{ $scheduleTeacherSubjectCount }}</p>
                </div>
            </div>

            <div class="tt-note rounded-2xl p-4 text-sm font-semibold mb-4">
                Chaque période est comptée comme <strong>1 heure</strong> pour l'horaire enseignant.
            </div>

            <div class="tt-panel overflow-hidden rounded-2xl">
                <div class="border-b border-gray-100 px-5 py-4">
                    <h3 class="font-black text-[#1A3A6B]">Emploi du temps du professeur</h3>
                    <p class="text-xs text-gray-500">Les cours sont groupés par classe et discipline.</p>
                </div>
                <div class="staff-timetable-scroll tt-grid-wrap overflow-x-auto p-0">
                    @include('timetable.partials.grid', [
                        'mode' => 'teacher',
                        'printable' => false,
                        'compact' => true,
                        'days' => $days,
                        'gridRows' => $gridRows,
                        'slots' => $scheduleSlots,
                        'teacherSubjectCount' => $scheduleTeacherSubjectCount,
                    ])
                </div>
            </div>
        @endif
    </div>

    {{-- 4. PRÉSENCES --}}
    <div x-show="tab === 'presence'" class="bg-white border border-gray-100 rounded-2xl shadow-sm p-6" x-transition>
        <h3 class="text-lg font-bold text-[#0c3260] mb-4">Suivi des présences & retards</h3>
        
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-6">
            <div class="bg-green-50 border border-green-100 p-4 rounded-xl text-center">
                <span class="block text-2xl font-black text-green-700">98%</span>
                <span class="text-xs text-green-600 font-semibold uppercase">Taux de présence global</span>
            </div>
            <div class="bg-orange-50 border border-orange-100 p-4 rounded-xl text-center">
                <span class="block text-2xl font-black text-orange-700">0</span>
                <span class="text-xs text-orange-600 font-semibold uppercase">Absences non justifiées</span>
            </div>
            <div class="bg-blue-50 border border-blue-100 p-4 rounded-xl text-center">
                <span class="block text-2xl font-black text-blue-700">2</span>
                <span class="text-xs text-blue-600 font-semibold uppercase">Absences autorisées</span>
            </div>
        </div>

        <div class="border border-gray-100 rounded-xl overflow-hidden text-sm">
            <div class="bg-gray-50 px-4 py-3 font-semibold text-gray-700 border-b border-gray-100">Historique récent</div>
            <div class="p-4 text-center text-gray-400 italic">Aucun enregistrement d'absence ou de retard pour l'année scolaire en cours.</div>
        </div>
    </div>
</div>
@endsection
