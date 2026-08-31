@extends('layouts.app')
@section('title', 'Vue Globale des Notes')
@section('page-title', 'Vue Globale des Notes')
@section('page-subtitle', 'Progression de la saisie par classe et par séquence')

@section('content')

<style>
    @media (max-width: 767px) {
        .grades-overview-table { min-width: 620px; }
        .grades-overview-table th:first-child,
        .grades-overview-table td:first-child { width: 100px !important; min-width: 100px !important; }
    }
</style>
<div class="grades-overview-page -mx-2 -mt-2 -mb-2 lg:-mx-4 lg:-mt-4 lg:-mb-4">

@if(!$activeYear)
<div class="bg-amber-50 border border-amber-200 rounded-xl p-6 text-center">
    <p class="text-amber-700 font-semibold">
        <svg class="inline h-4 w-4 mr-1 align-[-2px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>Aucune année scolaire active.
    </p>
</div>

@else

{{-- ── FILTRE SECTION ───────────────────────────────────────────────────── --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-5">
    <form method="GET" action="{{ route('grades.index') }}"
          class="flex flex-wrap items-end gap-3">
        <div>
            <label class="block text-xs font-bold text-gray-500 uppercase
                           tracking-wider mb-1">
                Section
            </label>
            <select name="section_id" onchange="this.form.submit()"
                    class="md:hidden w-full min-w-[220px] px-3 py-2.5 pr-10 border border-gray-200 rounded-xl text-sm font-medium bg-white"
                    style="color:#1A3A6B;">
                <option value="">— Choisir une section —</option>
                @foreach($sections as $section)
                <option value="{{ $section->id }}" {{ $selectedSectionId == $section->id ? 'selected' : '' }}>
                    {{ $section->name }}
                </option>
                @endforeach
            </select>
            <div class="hidden md:flex gap-2 flex-wrap">
                @foreach($sections as $section)
                <button type="submit" name="section_id" value="{{ $section->id }}"
                        class="px-4 py-2 rounded-xl text-sm font-bold border-2
                               transition-all
                               {{ $selectedSectionId == $section->id
                                   ? 'text-white border-transparent'
                                   : 'border-gray-200 text-gray-600 hover:border-gray-300' }}"
                        style="{{ $selectedSectionId == $section->id
                            ? 'background-color:#1A3A6B;' : '' }}">
                    {{ $section->name }}
                </button>
                @endforeach
            </div>
        </div>
    </form>
</div>

@if(!$selectedSection)
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-10
            text-center text-gray-400">
    <p class="text-sm">Sélectionnez une section pour voir les classes.</p>
</div>
@else

{{-- ── EN-TÊTE SÉQUENCES ───────────────────────────────────────────────── --}}
<div class="flex items-center mb-4">
    {{-- <div>
        <h3 class="font-black text-base" style="color:#1A3A6B;">
            {{ $selectedSection->name }}
        </h3>
        <p class="text-xs text-gray-500 mt-0.5">
            {{ $activeYear->label }}
        </p>
    </div> --}}
    {{-- <div class="flex gap-2">
        @foreach($sequences as $seq)
        <span class="px-3 py-1 rounded-full text-xs font-bold border"
              style="{{ $seq->is_grades_locked
                  ? 'background:#FEE2E2;color:#991B1B;border-color:#FCA5A5;'
                  : 'background:#EBF3FB;color:#1A3A6B;border-color:#BFDBFE;' }}">
            {{ $seq->label }}
        </span>
        @endforeach
    </div> --}}
    
    {{-- Légende --}}
    <div class="flex items-center flex-wrap gap-4 mt-3 text-xs text-gray-500">
        @foreach([
            ['color' => '#D1D5DB', 'label' => 'À saisir'],
            ['color' => '#2D6FD4', 'label' => 'En cours'],
            ['color' => '#1A5C2A', 'label' => 'Complet'],
            ['color' => '#EF4444', 'label' => 'Verrouillé'],
        ] as $leg)
        <div class="flex items-center gap-1.5">
            <div class="w-3 h-3 rounded-full"
                style="background-color:{{ $leg['color'] }}"></div>
            {{ $leg['label'] }}
        </div>
        @endforeach
        <span class="ml-2 text-gray-400">
            · Cliquez sur un cercle pour voir le détail de la séquence
        </span>
    </div>
</div>

{{-- ── TABLEAU CLASSES × SÉQUENCES ────────────────────────────────────── --}}
@php
    $sectionClasses = $selectedSection->levels->flatMap->classGroups;
@endphp

@if($sectionClasses->isEmpty())
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-10
            text-center text-gray-400 text-sm">
    Aucune classe dans cette section pour l'année active.
</div>
@else

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-x-auto">
    <table class="grades-overview-table w-full" style="border-collapse:separate;border-spacing:0;">
        <thead>
            <tr style="background:#F8FAFC; border-bottom:1px solid #F0F4F8;">
                <th class="text-left px-5 py-3.5 text-xs font-bold text-gray-400
                           uppercase tracking-wider sticky left-0 z-10"
                    style="background:#F8FAFC; min-width:140px;">
                    Classe
                </th>
                <th class="text-center px-4 py-3.5 text-xs font-bold text-gray-400
                           uppercase tracking-wider"
                    style="min-width:80px;">
                    Élèves
                </th>
                @foreach($sequences as $seq)
                <th class="text-center px-4 py-3.5 text-xs font-bold
                           text-gray-400 uppercase tracking-wider"
                    style="min-width:100px;">
                    <div>{{ $seq->label }}</div>
                    <div class="text-gray-300 font-normal text-xs mt-0.5">
                        T{{ $seq->trimester?->number }}
                    </div>
                </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($sectionClasses as $class)
            <tr class="border-b border-gray-50 hover:bg-blue-50/20
                       transition-colors">
                {{-- Nom classe --}}
                <td class="px-5 py-4 sticky left-0 bg-white z-5"
                    style="border-right:1px solid #F0F4F8;">
                    {{-- <div class="flex items-center gap-2.5"> --}}
                        {{-- <div class="w-9 h-9 rounded-xl flex items-center
                                    justify-center text-white font-bold
                                    text-xs flex-shrink-0"
                             style="background:#1A3A6B;">
                            {{ strtoupper(substr($class->name, 0, 2)) }}
                        </div> --}}
                        <div>
                            <p class="text-sm font-bold text-gray-800">
                                {{ $class->full_name }}
                            </p>
                            <p class="text-xs text-gray-400">
                                {{ $class->subjects_count }} matières
                            </p>
                        </div>
                    {{-- </div> --}}
                </td>

                {{-- Effectif --}}
                <td class="px-4 py-4 text-center">
                    <span class="text-sm font-black" style="color:#1A3A6B;">
                        {{ $class->enrolled }}
                    </span>
                </td>

                {{-- Cercles de progression par séquence --}}
                @foreach($sequences as $seq)
                @php
                    $isLocked = $locks->get($class->id)
                        ?->firstWhere('sequence_id', $seq->id)
                        ?->is_locked ?? false;

                    $count = $gradeCounts->get($class->id)
                        ?->firstWhere('sequence_id', $seq->id)
                        ?->cnt ?? 0;

                    $total = $class->enrolled * $class->subjects_count;
                    $pct   = ($total > 0) ? min(round(($count/$total)*100), 100) : 0;

                    $color  = $isLocked ? '#EF4444'
                        : ($pct >= 100 ? '#1A5C2A'
                        : ($pct > 0    ? '#2D6FD4' : '#D1D5DB'));
                    $bgFill = $isLocked ? '#FEE2E2'
                        : ($pct >= 100 ? '#D1FAE5'
                        : ($pct > 0    ? '#DBEAFE' : '#F3F4F6'));
                    $circ   = round(2 * 3.14159 * 18);
                    $offset = round($circ * (1 - $pct/100));
                @endphp
                <td class="px-4 py-4 text-center">
                    <a href="{{ route('grades.detail', [$class, $seq]) }}"
                       class="inline-flex flex-col items-center gap-1 group">
                        <div class="relative w-12 h-12">
                            <svg class="w-12 h-12 -rotate-90" viewBox="0 0 44 44">
                                <circle cx="22" cy="22" r="18" fill="none"
                                        stroke="#F0F5FF" stroke-width="4"/>
                                <circle cx="22" cy="22" r="18" fill="none"
                                        stroke="{{ $color }}"
                                        stroke-width="4"
                                        stroke-linecap="round"
                                        stroke-dasharray="{{ $circ }}"
                                        stroke-dashoffset="{{ $offset }}"
                                        style="transition:stroke-dashoffset .5s ease;"/>
                            </svg>
                            <span class="absolute inset-0 flex items-center
                                         justify-center text-xs font-black"
                                  style="color:{{ $color }};">
                                @if($isLocked) <svg class="inline h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4"/></svg> @else {{ $pct }}% @endif
                            </span>
                        </div>
                        <span class="text-xs font-semibold group-hover:underline
                                     transition-colors"
                              style="color:{{ $color }}; font-size:9px;">
                            @if($isLocked) Verrouillé
                            @elseif($pct >= 100) Complet
                            @elseif($pct > 0) En cours
                            @else À saisir @endif
                        </span>
                    </a>
                </td>
                @endforeach
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

{{-- Légende --}}
{{-- <div class="flex items-center flex-wrap gap-4 mt-3 text-xs text-gray-500">
    @foreach([
        ['color' => '#D1D5DB', 'label' => 'À saisir'],
        ['color' => '#2D6FD4', 'label' => 'En cours'],
        ['color' => '#1A5C2A', 'label' => 'Complet'],
        ['color' => '#EF4444', 'label' => 'Verrouillé'],
    ] as $leg)
    <div class="flex items-center gap-1.5">
        <div class="w-3 h-3 rounded-full"
             style="background-color:{{ $leg['color'] }}"></div>
        {{ $leg['label'] }}
    </div>
    @endforeach
    <span class="ml-2 text-gray-400">
        · Cliquez sur un cercle pour voir le détail de la séquence
    </span>
</div> --}}
@endif
@endif
@endif

</div>
@endsection
