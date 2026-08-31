@extends('layouts.app')

@section('title', 'Gestion des Élèves')
@section('page-title', 'Gestion des Élèves')
@section('page-subtitle', 'Liste des élèves inscrits dans l\'établissement')

@section('content')
<div class="space-y-6">

    {{-- En-tête de page (Titre + Boutons) --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3">
            <h1 class="text-2xl font-bold text-gray-900">Gestion des Élèves</h1>
            <span class="bg-blue-100 text-blue-800 text-xs font-semibold px-2.5 py-0.5 rounded-full">
                {{ $stats['total'] }} élèves
            </span>
        </div>
        
        <div class="flex items-center gap-3">
            @can('manage-students')
            @if($activeYear)
            <a href="{{ route('students.create') }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-[#9c4005] hover:bg-[#853604] text-white text-sm font-semibold rounded-lg shadow-sm transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                </svg>
                Nouvel élève
            </a>
            @endif
            @endcan

            {{-- <button class="inline-flex items-center gap-2 px-4 py-2 border border-[#1A3A6B] text-[#1A3A6B] bg-white text-sm font-semibold rounded-lg hover:bg-gray-50 shadow-sm transition-all">
                <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                </svg>
                Importer
            </button> --}}

            @if($listPrintParams)
            <a href="{{ route('students.documents.lists', $listPrintParams) }}"
               target="_blank"
               class="inline-flex items-center gap-2 px-4 py-2 border border-[#1A3A6B] text-[#1A3A6B] bg-white text-sm font-semibold rounded-lg hover:bg-gray-50 shadow-sm transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                Imprimer la liste
            </a>
            @endif

            {{-- <button class="inline-flex items-center gap-2 px-4 py-2 border border-[#1A3A6B] text-[#1A3A6B] bg-white text-sm font-semibold rounded-lg hover:bg-gray-50 shadow-sm transition-all">
                <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                Exporter
            </button> --}}
        </div>
    </div>

    {{-- Onglets inscription / renouvellement --}}
    @if($activeYear && $selectedYear?->is_active)
    <div class="flex flex-wrap items-center gap-3">
        <a href="{{ route('students.index', array_merge(request()->except('renewal'), ['year_id' => $selectedYear->id])) }}"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold border transition-all
                  {{ empty($renewalFilter) ? 'bg-[#1A3A6B] text-white border-[#1A3A6B]' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50' }}">
            Inscrits ({{ $stats['total'] }})
        </a>
        <a href="{{ route('students.index', array_merge(request()->except('renewal'), ['year_id' => $selectedYear->id, 'renewal' => 'pending'])) }}"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold border transition-all
                  {{ !empty($renewalFilter) ? 'bg-amber-600 text-white border-amber-600' : 'bg-white text-amber-700 border-amber-200 hover:bg-amber-50' }}">
            En attente de renouvellement
            @if($pendingRenewal > 0)
            <span class="px-2 py-0.5 rounded-full text-xs font-bold
                         {{ !empty($renewalFilter) ? 'bg-white/20 text-white' : 'bg-amber-100 text-amber-800' }}">
                {{ $pendingRenewal }}
            </span>
            @endif
        </a>
    </div>
    @endif

    {{-- Filtres --}}
    <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm">
        <form method="GET" action="{{ route('students.index') }}" class="flex flex-wrap items-end gap-4">
            
            @if(!empty($renewalFilter))
            <input type="hidden" name="renewal" value="pending">
            @endif

            {{-- Recherche --}}
            <div class="flex-1 min-w-[280px]">
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Rechercher un élève...</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-3 flex items-center text-gray-400 pointer-events-none">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </span>
                    <input type="search" name="search"
                           value="{{ request('search') }}"
                           placeholder="Nom, matricule..."
                           class="w-full pl-9 pr-12 py-2 border border-gray-200 rounded-lg text-sm bg-gray-50/50 focus:bg-white focus:outline-none focus:ring-1 focus:ring-[#1A3A6B] focus:border-[#1A3A6B] transition-all">
                    <button type="submit"
                            class="absolute right-1 top-1/2 -translate-y-1/2 inline-flex items-center justify-center w-9 h-9 rounded-lg bg-[#1A3A6B] text-white text-sm hover:bg-[#163450] transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="w-full sm:w-56">
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Année</label>
                <select name="year_id" onchange="this.form.submit()"
                        class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm bg-white text-gray-700 focus:outline-none focus:ring-1 focus:ring-[#1A3A6B] focus:border-[#1A3A6B] transition-all">
                    @foreach($years as $year)
                    <option value="{{ $year->id }}"
                            {{ $selectedYear?->id == $year->id ? 'selected' : '' }}>
                        {{ $year->label }}
                        {{ $year->is_active ? '(Active)' : '' }}
                    </option>
                    @endforeach
                </select>
            </div>

            {{-- Section --}}
            <div class="w-full sm:w-48">
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Section</label>
                <select name="section_id" onchange="this.form.submit()"
                        class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm bg-white text-gray-700 focus:outline-none focus:ring-1 focus:ring-[#1A3A6B] focus:border-[#1A3A6B] transition-all">
                    <option value="">Toutes</option>
                    @foreach($sections as $section)
                    <option value="{{ $section->id }}"
                            {{ request('section_id') == $section->id ? 'selected' : '' }}>
                        {{ $section->name }}
                    </option>
                    @endforeach
                </select>
            </div>

            {{-- Classe --}}
            <div class="w-full sm:w-56">
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Classe</label>
                <select name="class_id" onchange="this.form.submit()"
                        class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm bg-white text-gray-700 focus:outline-none focus:ring-1 focus:ring-[#1A3A6B] focus:border-[#1A3A6B] transition-all">
                    <option value="">Toutes les classes</option>
                    @foreach($classes as $class)
                    <option value="{{ $class->id }}"
                            {{ request('class_id') == $class->id ? 'selected' : '' }}>
                        {{ $class->full_name }}
                    </option>
                    @endforeach
                </select>
            </div>

            {{-- Bouton réinitialiser/filtre --}}
            <div class="flex items-center gap-2">
                @if(request()->hasAny(['section_id','class_id','search']))
                <a href="{{ route('students.index', ['year_id' => $selectedYear?->id]) }}"
                   class="inline-flex items-center justify-center p-2.5 border border-gray-200 rounded-lg bg-white text-gray-500 hover:bg-gray-50 transition-all shadow-sm"
                   title="Réinitialiser les filtres">
                    <svg class="w-4. h-4." fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    {{-- x --}}
                </a>
                @endif
                {{-- <button type="button" class="inline-flex items-center justify-center p-2.5 border border-gray-200 rounded-lg bg-gray-50 text-gray-600 hover:bg-gray-100 transition-all shadow-sm">
                    <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                    </svg>
                </button> --}}
            </div>

        </form>
    </div>

    {{-- Tableau --}}
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
        @if($students->isEmpty())
        <div class="p-16 text-center">
            <div class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 bg-gray-50">
                <svg class="w-8 h-8 text-[#1A3A6B]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
            </div>
            <p class="text-gray-500 font-medium">Aucun élève trouvé.</p>
            @can('manage-students')
            @if($activeYear)
            <a href="{{ route('students.create') }}" class="inline-flex items-center gap-1 mt-2 text-sm font-semibold text-[#9c4005] hover:underline">
                + Inscrire le premier élève
            </a>
            @endif
            @endcan
        </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        {{-- <th class="w-12 px-6 py-4">
                            <input type="checkbox" class="rounded border-gray-300 text-[#9c4005] focus:ring-[#9c4005]">
                        </th> --}}
                        <th class="px-4 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Photo</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Nom & Prénom</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Matricule</th>
                        @unless(!empty($renewalFilter))
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Inscrit(e) le</th>
                        @endunless
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Classe</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Section</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Statut</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($students as $index => $student)
                    @php
                        $enrollment = !empty($renewalFilter)
                            ? $student->enrollments
                                ->sortByDesc(fn ($e) => $e->academicYear->start_date)
                                ->first()
                            : ($student->enrollments->where('status', 'active')->sortByDesc(
                                fn ($e) => $e->academicYear?->start_date
                            )->first() ?? $student->enrollments->sortByDesc(
                                fn ($e) => $e->academicYear?->start_date
                            )->first());
                        $status = !empty($renewalFilter)
                            ? 'pending_renewal'
                            : ($enrollment?->status === 'active' ? 'active' : 'inactive');

                        $statusLabels = [
                            'active' => 'Actif',
                            'pending_renewal' => 'En attente',
                            'transferred' => 'Transféré',
                            'transferred_out' => 'Transféré',
                            'withdrawn' => 'Retiré',
                            'inactive' => 'Inactif',
                            'excluded' => 'Exclu'
                        ];
                        $statusLabel = $statusLabels[$status] ?? 'Actif';

                        $statusColors = [
                            'active' => 'bg-green-50 text-green-700 border-green-100',
                            'pending_renewal' => 'bg-amber-50 text-amber-800 border-amber-200',
                            'transferred' => 'bg-gray-100 text-gray-600 border-gray-200',
                            'transferred_out' => 'bg-gray-100 text-gray-600 border-gray-200',
                            'withdrawn' => 'bg-red-50 text-red-700 border-red-100',
                            'inactive' => 'bg-gray-50 text-gray-400 border-gray-200',
                            'excluded' => 'bg-red-50 text-red-800 border-red-200'
                        ];
                        $statusColor = $statusColors[$status] ?? 'bg-green-50 text-green-700 border-green-100';
                    @endphp
                    <tr class="hover:bg-gray-50/50 transition-colors">
                        {{-- <td class="px-6 py-4">
                            <input type="checkbox" class="rounded border-gray-300 text-[#9c4005] focus:ring-[#9c4005]">
                        </td> --}}
                        <td class="px-4 py-4">
                            @if($student->photo)
                            <img src="{{ $student->photo_url }}"
                                 class="w-10 h-10 rounded-full object-cover border-2 border-gray-100 flex-shrink-0">
                            @else
                            <div class="w-10 h-10 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0"
                                 style="background-color: {{ $student->gender === 'M' ? '#1A3A6B' : '#BE185D' }};">
                                {{ strtoupper(substr($student->last_name, 0, 1)) . strtoupper(substr($student->first_name, 0, 1)) }}
                            </div>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex flex-col text-sm">
                                <span class="font-bold text-[#1A3A6B]">{{ $student->last_name }}</span>
                                <span class="text-[#1A3A6B] font-medium">{{ $student->first_name }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="font-mono text-sm font-semibold text-gray-600">
                                {{ $student->matricule }}
                            </span>
                        </td>
                        @unless(!empty($renewalFilter))
                        <td class="px-6 py-4 text-sm text-gray-700">
                            {{ $enrollment?->enrollment_date?->format('d/m/Y') ?? '—' }}
                        </td>
                        @endunless
                        <td class="px-6 py-4 text-sm text-gray-700 font-medium">
                            @if($enrollment?->classGroup)
                            {{ $enrollment->classGroup->name }}
                            @else
                            <span class="text-xs text-gray-400 italic">Non inscrit</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            @if($enrollment?->classGroup?->level?->section)
                            {{ $enrollment->classGroup->level->section->name }}
                            @else
                            <span class="text-gray-300">—</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-3 py-1 rounded-full text-xs font-semibold border {{ $statusColor }}">
                                {{ $statusLabel }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-1">
                                {{-- Renouveler / Inscrire --}}
                                @if(!empty($renewalFilter) || !$enrollment || $enrollment->status !== 'active')
                                <a href="{{ route('students.enroll', $student) }}"
                                   class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-gray-400 hover:text-green-700 hover:bg-green-50 transition-colors"
                                   title="{{ !empty($renewalFilter) ? 'Renouveler' : 'Inscrire' }}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                    </svg>
                                </a>
                                @endif

                                {{-- Voir : toujours visible --}}
                                <a href="{{ route('students.show', $student) }}"
                                   class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-gray-400 hover:text-[#1A3A6B] hover:bg-blue-50 transition-colors" title="Voir la fiche">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </a>

                                @can('manage-students')
                                @if($isYearEditable)
                                {{-- Modifier : année active seulement --}}
                                <a href="{{ route('students.edit', $student) }}"
                                   class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-gray-400 hover:text-amber-600 hover:bg-amber-50 transition-colors" title="Modifier">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                    </svg>
                                </a>

                                {{-- Supprimer --}}
                                <form method="POST" action="{{ route('students.destroy', $student) }}" class="inline"
                                      onsubmit="return confirm('Confirmer la suppression de {{ addslashes($student->full_name) }} ?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors" title="Supprimer">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </form>
                                @else
                                {{-- Année clotürée : badge lecture seule --}}
                                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-[10px] font-semibold bg-gray-100 text-gray-400" title="Année clotürée — lecture seule">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                    </svg>
                                    Lecture
                                </span>
                                @endif
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($students->hasPages())
        <div class="px-6 py-4 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-4">
            <span class="text-sm text-gray-500 font-medium">
                Affichage {{ $students->firstItem() }}-{{ $students->lastItem() }} sur {{ $students->total() }} élèves
            </span>
            <div>
                {{ $students->links('vendor.pagination.custom') }}
            </div>
        </div>
        @else
        <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between">
            <span class="text-sm text-gray-500 font-medium">
                Affichage 1-{{ $students->count() }} sur {{ $students->total() }} élèves
            </span>
        </div>
        @endif
        @endif
    </div>

</div>
@endsection
