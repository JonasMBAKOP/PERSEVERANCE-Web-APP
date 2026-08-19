@extends('layouts.app')

@section('title', 'Personnel')
@section('page-title', 'Gestion du Personnel')
@section('page-subtitle', 'Détails et informations complètes du personnel')

@section('content')
<div x-data="{ viewMode: 'grid' }">

    <div class="mb-6 flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
        <div>
            {{-- Title and pill inside content area --}}
            <div class="flex items-center mb-1">
                <h2 class="text-2xl font-bold text-gray-900">Enseignants & Personnel</h2>
                {{-- <a href="{{ route('staff.lists.print', request()->query()) }}" target="_blank"
                class="ml-auto inline-flex items-center gap-2 rounded-xl bg-[#1A3A6B] px-4 py-2 text-sm font-bold text-white shadow-sm transition hover:bg-[#163450]"
                title="Print staff list">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2m-2 0H8v4h8v-4z"/>
                    </svg>
                    Imprimer la liste
                </a> --}}
                <span class="bg-[#FFEADF] text-[#A2522D] text-xs font-semibold px-2.5 py-0.5 rounded-full uppercase tracking-wider ml-3">
                    {{ $stats['total'] }} membres
                </span>
            </div>

            {{-- Stats Row --}}
            <div class="flex flex-wrap items-center gap-x-6 gap-y-2 text-sm text-gray-500 mb-6 font-medium">
                <span class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <span class="text-gray-900 font-bold">{{ $stats['teachers'] }}</span> enseignants
                </span>
                <span class="text-gray-300">|</span>
                <span class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                    <span class="text-gray-900 font-bold">{{ $stats['subjects_taught'] }}</span> matières enseignées
                </span>
                <span class="text-gray-300">|</span>
                <span class="flex items-center gap-2 text-red-600 font-semibold">
                    <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <span>{{ $stats['no_class'] }} sans classe assignée</span>
                </span>
            </div>
        </div>

        <a href="{{ route('staff.lists.print', request()->query()) }}" target="_blank"
            class="ml-auto inline-flex items-center gap-2 rounded-xl bg-[#1A3A6B] px-4 py-2 text-sm font-bold text-white shadow-sm transition hover:bg-[#163450]"
            title="Print staff list">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2m-2 0H8v4h8v-4z"/>
            </svg>
            Imprimer la liste
        </a>
    </div>

    {{-- Filters and Actions Row --}}
    <form method="GET" action="{{ route('staff.index') }}" class="flex flex-col gap-4 mb-3">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center flex-1">
                {{-- Champ de recherche mobile-first --}}
                <div class="relative w-full sm:max-w-[320px] lg:max-w-[360px]">
                    <span class="absolute inset-y-0 left-3 flex items-center text-gray-400 pointer-events-none">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </span>
                    <input type="search" name="search" id="search" value="{{ request('search') }}"
                           placeholder="Nom, poste, matière..."
                           class="h-11 w-full rounded-xl border border-gray-200 bg-white pl-10 pr-20 text-sm text-gray-700 shadow-sm transition focus:border-[#1A3A6B] focus:outline-none focus:ring-2 focus:ring-[#1A3A6B]/20">
                    @if(request('search'))
                        <a href="{{ route('staff.index', array_merge(request()->except('search', 'page'), [])) }}"
                           class="absolute inset-y-0 right-11 flex items-center text-gray-400 hover:text-gray-600 transition-colors"
                           title="Effacer la recherche">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </a>
                    @endif
                    <button type="submit"
                            class="absolute right-1.5 top-1/2 -translate-y-1/2 inline-flex h-8 w-8 items-center justify-center rounded-lg bg-[#1A3A6B] text-white shadow-sm transition hover:bg-[#163450] focus:outline-none focus:ring-2 focus:ring-[#1A3A6B]/20"
                            aria-label="Lancer la recherche">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </button>
                </div>

                {{-- Filtre Matières --}}
                <div class="relative w-full sm:w-auto">
                    <select name="subject_id" onchange="this.form.submit()"
                            class="h-11 w-full appearance-none rounded-xl border {{ request('subject_id') ? 'border-[#A35200] bg-[#FDF2E9] text-[#A35200] font-semibold' : 'border-gray-200 bg-white text-gray-600' }} pl-3 pr-9 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent cursor-pointer">
                        <option value="">Toutes les matières</option>
                        @foreach($subjects as $sub)
                            <option value="{{ $sub->id }}" {{ request('subject_id') == $sub->id ? 'selected' : '' }}>
                                {{ $sub->name_fr }}
                            </option>
                        @endforeach
                    </select>
                    <span class="pointer-events-none absolute inset-y-0 right-2.5 flex items-center">
                        @if(request('subject_id'))
                            <a href="{{ route('staff.index', array_merge(request()->except('subject_id', 'page'), [])) }}"
                               class="pointer-events-auto text-[#A35200] hover:text-[#7a3d00] transition-colors"
                               title="Retirer ce filtre">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </a>
                        @else
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        @endif
                    </span>
                </div>

                {{-- Filtre Statut --}}
                <div class="relative w-full sm:w-auto">
                    <select name="status" onchange="this.form.submit()"
                            class="h-11 w-full appearance-none rounded-xl border {{ request('status') ? 'border-[#A35200] bg-[#FDF2E9] text-[#A35200] font-semibold' : 'border-gray-200 bg-white text-gray-600' }} pl-3 pr-9 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent cursor-pointer">
                        <option value="">Tous les statuts</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Actifs</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactifs</option>
                    </select>
                    <span class="pointer-events-none absolute inset-y-0 right-2.5 flex items-center">
                        @if(request('status'))
                            <a href="{{ route('staff.index', array_merge(request()->except('status', 'page'), [])) }}"
                               class="pointer-events-auto text-[#A35200] hover:text-[#7a3d00] transition-colors"
                               title="Retirer ce filtre">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </a>
                        @else
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        @endif
                    </span>
                </div>
            </div>

            <div class="flex items-center gap-2 self-end sm:self-auto">
                <button type="button" @click="viewMode = 'grid'"
                        :class="viewMode === 'grid' ? 'bg-gray-100 border-gray-300 text-gray-800' : 'bg-white border-gray-200 text-gray-400'"
                        class="p-2 border rounded-lg hover:bg-gray-50 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                    </svg>
                </button>
                <button type="button" @click="viewMode = 'list'"
                        :class="viewMode === 'list' ? 'bg-gray-100 border-gray-300 text-gray-800' : 'bg-white border-gray-200 text-gray-400'"
                        class="p-2 border rounded-lg hover:bg-gray-50 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>

        {{-- <a href="{{ route('staff.lists.print', request()->query()) }}" target="_blank"
                   class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50"
                   title="Imprimer la liste du personnel">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 9V2h12v7m-1 5H7a3 3 0 00-3 3v3h16v-3a3 3 0 00-3-3zM7 14h10v8H7v-8z"/>
                    </svg>
                    Imprimer
                </a> --}}

                <a href="{{ route('staff.documents.cards') }}"
                   class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50"
                   title="Cartes professionnelles">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M3 12h18M3 17h18"/>
                    </svg>
                    Cartes
                </a>
            </div>
        </div>
    </form>

    {{-- Chips filtres actifs --}}
    @if(request()->hasAny(['search', 'subject_id', 'status']))
    <div class="flex flex-wrap items-center gap-2 mb-5">
        <span class="text-xs text-gray-400 font-medium uppercase tracking-wider">Filtres actifs :</span>
        @if(request('search'))
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-700">
                <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                « {{ request('search') }} »
                <a href="{{ route('staff.index', array_merge(request()->except('search', 'page'), [])) }}" class="text-gray-400 hover:text-gray-700 ml-0.5">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                </a>
            </span>
        @endif
        @if(request('subject_id'))
            @php $filteredSub = $subjects->firstWhere('id', request('subject_id')); @endphp
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-[#FDF2E9] text-[#A35200]">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13"/></svg>
                {{ $filteredSub?->name_fr ?? 'Matière' }}
                <a href="{{ route('staff.index', array_merge(request()->except('subject_id', 'page'), [])) }}" class="text-[#A35200] hover:text-[#7a3d00] ml-0.5">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                </a>
            </span>
        @endif
        @if(request('status'))
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold {{ request('status') === 'active' ? 'bg-green-50 text-green-700' : 'bg-orange-50 text-orange-700' }}">
                <span class="w-1.5 h-1.5 rounded-full {{ request('status') === 'active' ? 'bg-green-500' : 'bg-orange-500' }}"></span>
                {{ request('status') === 'active' ? 'Actifs' : 'Inactifs' }}
                <a href="{{ route('staff.index', array_merge(request()->except('status', 'page'), [])) }}" class="{{ request('status') === 'active' ? 'text-green-500 hover:text-green-700' : 'text-orange-500 hover:text-orange-700' }} ml-0.5">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                </a>
            </span>
        @endif
        <a href="{{ route('staff.index') }}" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold border border-gray-200 text-gray-500 hover:bg-gray-100 hover:text-gray-700 transition-colors">
            Tout effacer
        </a>
    </div>
    @endif

    {{-- Grid Layout --}}
    <div x-show="viewMode === 'grid'" x-transition class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($staff as $member)
            <div class="bg-white border border-gray-100 rounded-2xl shadow-sm p-5 flex flex-col relative hover:shadow-md transition-shadow">
                <!-- Top Header of Card -->
                <div class="flex justify-between items-start mb-4">
                    <!-- Avatar -->
                    @if($member->photo)
                        <img src="{{ $member->photo_url }}" alt="{{ $member->full_name }}" class="w-12 h-12 rounded-full object-cover ring-2 ring-gray-50">
                    @else
                        @php
                            $words = explode(' ', $member->full_name);
                            $initials = '';
                            foreach($words as $w) {
                                if($w) $initials .= strtoupper(substr($w, 0, 1));
                            }
                            $initials = substr($initials, 0, 2);
                            $bgColors = [
                                'bg-[#0A2540] text-white',
                                'bg-[#FBE9E7] text-[#FF5722]',
                                'bg-[#E3F2FD] text-[#2196F3]',
                                'bg-[#E8F5E9] text-[#4CAF50]',
                                'bg-[#EDE7F6] text-[#673AB7]'
                            ];
                            $bgColor = $bgColors[$member->id % count($bgColors)];
                        @endphp
                        <div class="w-12 h-12 rounded-full flex items-center justify-center font-bold text-sm {{ $bgColor }}">
                            {{ $initials }}
                        </div>
                    @endif
                    
                    <!-- Status Badge -->
                    @if($member->is_active)
                        <span class="bg-[#e6f4ea] text-[#137333] text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wider">
                            Actif
                        </span>
                    @else
                        <span class="bg-[#fef7e0] text-[#b06000] text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wider">
                            En congé
                        </span>
                    @endif
                </div>
                
                <!-- Body of Card -->
                <div class="flex-1">
                    <h3 class="text-base font-semibold text-gray-900 mb-1 hover:text-blue-600">
                        <a href="{{ route('staff.show', $member) }}">
                            {{ $member->gender === 'M' ? 'M.' : ($member->gender === 'F' ? 'Mme.' : '') }} {{ $member->full_name }}
                        </a>
                    </h3>
                    
                    <!-- Matières enseignées ou poste principal -->
                    @php
                        $subjectsForCard = $member->teacherAssignments
                            ->map(fn($ta) => $ta->classSubject?->subject?->name_fr)
                            ->filter()
                            ->unique()
                            ->values();
                    @endphp
                    @if($subjectsForCard->isNotEmpty())
                        <p class="text-sm font-medium text-[#A35200] mb-3 leading-snug">
                            {{ $subjectsForCard->implode(' & ') }}
                        </p>
                    @else
                        <p class="text-sm font-medium text-gray-400 mb-3">
                            Aucune matière enseignée
                            {{-- {{ $member->positions->firstWhere('is_primary', true)?->position_label ?? 'Personnel' }} --}}
                        </p>
                    @endif
                    
                    <!-- Classes Count -->
                    @php
                        $classesCount = $member->teacherAssignments
                            ->map(fn($ta) => $ta->classSubject?->class_group_id)
                            ->filter()
                            ->unique()
                            ->count();
                    @endphp
                    <div class="flex items-center gap-2 text-xs text-gray-500 mb-4">
                         <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                         </svg>
                         <span>
                             @if($classesCount > 0)
                                 {{ $classesCount }} classes
                             @else
                                 0 classes (Assignation en cours)
                             @endif
                         </span>
                    </div>
                </div>
                
                <!-- Separator -->
                <hr class="border-gray-100 my-3">
                
                <!-- Actions row -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-1">
                        {{-- Voir la fiche --}}
                        <a href="{{ route('staff.show', $member) }}"
                           class="p-2 rounded-lg text-gray-400 hover:text-[#A35200] hover:bg-orange-50 transition-colors"
                           title="Voir la fiche">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </a>

                        @can('manage-staff')
                        {{-- Modifier --}}
                        <a href="{{ route('staff.edit', $member) }}"
                           class="p-2 rounded-lg text-gray-400 hover:text-[#A35200] hover:bg-orange-50 transition-colors"
                           title="Modifier">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                            </svg>
                        </a>
                        @endcan
                    </div>

                    {{-- Compte utilisateur --}}
                    @if($member->user)
                        <span class="p-2 rounded-lg text-blue-500 hover:bg-blue-50 transition-colors cursor-help"
                              title="Compte connecté : {{ $member->user->email }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        </span>
                    @else
                        <span class="p-2 rounded-lg text-gray-300 cursor-help"
                              title="Aucun compte utilisateur lié">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </span>
                    @endif
                </div>
            </div>
        @empty
            <div class="col-span-full bg-white border border-gray-100 rounded-2xl p-12 text-center text-gray-400">
                <svg class="w-12 h-12 mx-auto mb-3 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <p class="text-sm mb-3">Aucun membre du personnel trouvé.</p>
            </div>
        @endforelse
    </div>

    {{-- List Layout --}}
    <div x-show="viewMode === 'list'" x-transition class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-gray-100" style="background-color: #F8FAFC;">
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Membre</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Poste(s)</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Contact</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Contrat</th>
                        <th class="text-center px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Statut</th>
                        <th class="text-right px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($staff as $member)
                    <tr class="hover:bg-gray-50/50 transition-colors {{ !$member->is_active ? 'opacity-60' : '' }}">
                        <td class="px-5 py-3">
                            <div class="flex items-center gap-3">
                                @if($member->photo)
                                    <img src="{{ $member->photo_url }}" alt="" class="w-10 h-10 rounded-full object-cover">
                                @else
                                    <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-xs bg-slate-800 text-white">
                                        {{ strtoupper(substr($member->last_name, 0, 1) . substr($member->first_name, 0, 1)) }}
                                    </div>
                                @endif
                                <div class="min-w-0">
                                    <a href="{{ route('staff.show', $member) }}" class="text-sm font-medium text-gray-900 hover:text-blue-600 truncate block">
                                        {{ $member->full_name }}
                                    </a>
                                    <p class="text-xs text-gray-400">
                                        {{ $member->gender === 'M' ? 'Homme' : 'Femme' }}
                                    </p>
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-3">
                            <div class="flex flex-wrap gap-1">
                                @foreach($member->positions as $pos)
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $pos->is_primary ? 'bg-blue-50 text-blue-700' : 'bg-gray-100 text-gray-600' }}">
                                    {{ $pos->position_label }}
                                </span>
                                @endforeach
                            </div>
                        </td>
                        <td class="px-5 py-3">
                            <p class="text-sm text-gray-600">{{ $member->phone ?? '—' }}</p>
                            <p class="text-xs text-gray-400">{{ $member->email ?? '—' }}</p>
                        </td>
                        <td class="px-5 py-3 text-sm text-gray-600">
                            {{ $member->contract_label }}
                        </td>
                        <td class="px-5 py-3 text-center">
                            @if($member->is_active)
                                <span class="px-2.5 py-0.5 inline-flex text-xs font-semibold rounded-full bg-green-50 text-green-700">Actif</span>
                            @else
                                <span class="px-2.5 py-0.5 inline-flex text-xs font-semibold rounded-full bg-orange-50 text-orange-700">En congé</span>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-right">
                            <div class="flex items-center justify-end gap-1">
                                <a href="{{ route('staff.show', $member) }}" class="p-1.5 rounded-lg text-gray-400 hover:text-[#A35200] hover:bg-orange-50">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </a>
                                @can('manage-staff')
                                <a href="{{ route('staff.edit', $member) }}" class="p-1.5 rounded-lg text-gray-400 hover:text-[#A35200] hover:bg-orange-50">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                    </svg>
                                </a>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-5 py-12 text-center text-gray-400">
                            Aucun membre du personnel enregistré.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($staff->hasPages())
        <div class="mt-8 border-t border-gray-100 pt-5">{{ $staff->onEachSide(1)->links() }}</div>
    @endif

    @can('manage-staff')
    <a href="{{ route('staff.create') }}"
       class="group fixed bottom-3 right-3 z-50 flex h-12 w-12 items-center justify-center rounded-full bg-[#A35200] text-white shadow-lg transition-all duration-300 hover:w-auto hover:px-4 hover:shadow-xl sm:bottom-4 sm:right-4 sm:h-14 sm:w-14 md:h-14 md:w-14 lg:h-16 lg:w-16">
        <svg class="h-5 w-5 transition-all duration-300 group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
        </svg>
        <span class="ml-2 hidden whitespace-nowrap text-sm font-semibold group-hover:inline-flex">Ajouter un Enseignant/Personnel</span>
    </a>
    @endcan
</div>
@endsection
