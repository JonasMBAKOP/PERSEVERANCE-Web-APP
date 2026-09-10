@extends('layouts.app')
@section('title', 'Tableau de bord — Enseignant')
@section('page-title', 'Tableau de bord')
@section('page-subtitle')Bonjour, {{ auth()->user()->name }} — {{ now()->isoFormat('dddd D MMMM YYYY') }}@endsection

@push('styles')
<style>
.tt-panel { background:#fff; border:1px solid #E5EDF5; box-shadow:0 10px 26px rgba(26,58,107,.055); }
.tt-grid-wrap table th { background:#F8FBFE; color:#334155; }
.tt-grid-wrap table tbody tr:hover td { background-color:#FAFCFE; }
.tt-grid-wrap table td, .tt-grid-wrap table th { border-color:#E8EEF5; }
</style>
@endpush

@section('content')

@if($noStaff)
<div class="bg-amber-50 border border-amber-200 rounded-xl p-6 text-center">
    <p class="text-amber-700 font-semibold inline-flex items-center justify-center gap-2">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 14h4M12 10v4"/><path d="M12 3a9 9 0 1 0 9 9A9 9 0 0 0 12 3z"/></svg>
        Aucun dossier personnel associé à votre compte.
    </p>
</div>
@elseif(!$activeYear)
<div class="bg-amber-50 border border-amber-200 rounded-xl p-6 text-center">
    <p class="text-amber-700 font-semibold inline-flex items-center justify-center gap-2">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8v4"/><path d="M12 16h.01"/><path d="M5 20h14a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2z"/></svg>
        Aucune année scolaire active.
    </p>
</div>
@else

{{-- ── BANNIÈRE D'ACCUEIL ──────────────────────────────────────────────── --}}
<div class="rounded-2xl p-6 mb-6 relative overflow-hidden"
     style="background:linear-gradient(135deg,#1A3A6B 0%,#0D2040 100%);">
    <div class="absolute top-0 right-0 w-64 h-64 opacity-10"
         style="background:radial-gradient(circle,#fff 0%,transparent 70%);transform:translate(30%,-30%);">
    </div>
    <h2 class="text-2xl font-black text-white mb-1">
        {{ now()->hour < 18 ? 'Bonjour' : 'Bonsoir' }},
        Prof. {{ explode(' ', auth()->user()->name)[0] }}
        — Année scolaire {{ $activeYear->label }}
    </h2>
    <p class="text-white/70 text-sm mb-5">
        Vous enseignez {{ $totalSubjects }} matière(s) dans {{ $totalClasses }} classe(s)
    </p>
    <div class="grid grid-cols-3 gap-4 max-w-xl">
        <div class="rounded-xl p-4 bg-white/10 backdrop-blur-sm border border-white/10">
            <p class="text-xs font-bold text-white/60 uppercase tracking-wider">Matières</p>
            <p class="text-2xl font-black text-white mt-1">{{ str_pad($totalSubjects,2,'0',STR_PAD_LEFT) }}</p>
        </div>
        <div class="rounded-xl p-4 bg-white/10 backdrop-blur-sm border border-white/10">
            <p class="text-xs font-bold text-white/60 uppercase tracking-wider">Classes</p>
            <p class="text-2xl font-black text-white mt-1">{{ str_pad($totalClasses,2,'0',STR_PAD_LEFT) }}</p>
        </div>
        <div class="rounded-xl p-4 bg-white/10 backdrop-blur-sm border border-white/10">
            <p class="text-xs font-bold text-white/60 uppercase tracking-wider">Élèves totaux</p>
            <p class="text-2xl font-black text-white mt-1">{{ $totalStudents }}</p>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-6">
    <a href="{{ route('grades.entry.form') }}" class="rounded-2xl bg-white shadow-sm border border-gray-100 p-4 flex items-center gap-3 hover:shadow-md transition">
        <div class="w-12 h-12 rounded-2xl bg-sky-100 text-sky-700 flex items-center justify-center">
            <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3 8-8"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h11"/></svg>
        </div>
        <div>
            <p class="text-sm font-semibold text-gray-900">Saisie des notes</p>
            <p class="text-xs text-gray-500">Accéder à la saisie par classe.</p>
        </div>
    </a>
    <a href="{{ route('classes.index') }}" class="rounded-2xl bg-white shadow-sm border border-gray-100 p-4 flex items-center gap-3 hover:shadow-md transition">
        <div class="w-12 h-12 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center">
            <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
        </div>
        <div>
            <p class="text-sm font-semibold text-gray-900">Mes classes</p>
            <p class="text-xs text-gray-500">Voir la liste de vos classes.</p>
        </div>
    </a>
    <a href="{{ route('timetable.teacher') }}" class="rounded-2xl bg-white shadow-sm border border-gray-100 p-4 flex items-center gap-3 hover:shadow-md transition">
        <div class="w-12 h-12 rounded-2xl bg-amber-100 text-amber-700 flex items-center justify-center">
            <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 7V3h8v4"/><path d="M5 21h14a2 2 0 0 0 2-2V7H3v12a2 2 0 0 0 2 2z"/></svg>
        </div>
        <div>
            <p class="text-sm font-semibold text-gray-900">Emploi du temps</p>
            <p class="text-xs text-gray-500">Voir votre planning semaine.</p>
        </div>
    </a>
</div>

{{-- ── MES CLASSES ───────────────────────────────────────────────────────── --}}
<div class="flex items-center justify-between mb-4">
    <h3 class="font-black text-base" style="color:#1A3A6B;">Mes Classes</h3>
    <a href="{{ route('classes.index') }}" class="text-xs font-bold hover:underline" style="color:#E87722;">Voir tout →</a>
</div>

@if($myClasses->isEmpty())
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-10 text-center text-gray-400 text-sm mb-6">
    Aucune classe assignée pour cette année.
</div>
@else
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    @foreach($myClasses as $mc)
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5
                hover:shadow-md transition-all relative">
        <div class="flex items-start justify-between mb-2">
            <h4 class="font-black text-base" style="color:#1A3A6B;">{{ $mc['class']->full_name }}</h4>
        </div>
        <p class="text-sm text-gray-600 mb-3">
            {{ $mc['subject']->name_fr }} — Coef. {{ $mc['coefficient'] }}
        </p>
        <div class="flex items-center gap-1.5 text-sm text-gray-500 mb-3">
            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6
                         6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
            </svg>
            {{ $mc['enrolled'] }} élèves
        </div>

        @if($mc['seqStatus'])
        @php
            $st = $mc['seqStatus'];
            $badge = $st['locked']
                ? ['Verrouillée','#FEE2E2','#991B1B']
                : ($st['complete']
                    ? [$st['label'].' : Validée','#D1FAE5','#065F46']
                    : [$st['label'].' : Non saisie','#FEF3E2','#92400E']);
        @endphp
        <span class="inline-block px-3 py-1 rounded-lg text-xs font-bold mb-3"
              style="background:{{ $badge[1] }};color:{{ $badge[2] }};">
            {{ $badge[0] }}
        </span>
        @endif

        <a href="{{ route('grades.entry.form', [
                'section_id'  => $mc['class']->level->section_id,
                'subject_id'  => $mc['subject']->id,
                'class_id'    => $mc['class']->id,
                'sequence_id' => $currentSeq?->id,
            ]) }}"
           class="block w-full py-2 rounded-xl text-center text-sm font-bold border-2 transition-all"
           style="border-color:#1A3A6B; color:#1A3A6B;">
            {{ ($mc['seqStatus']['complete'] ?? false) || ($mc['seqStatus']['locked'] ?? false)
                ? 'Voir les notes' : 'Saisir les notes' }}
        </a>
    </div>
    @endforeach
</div>
@endif

{{-- ── MON EMPLOI DU TEMPS PAR SEMAINE ─────────────────────────────────── --}}
<div class="tt-panel overflow-hidden rounded-2xl">
    <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
        <div>
            <h3 class="font-black text-base" style="color:#1A3A6B;">Mon emploi du temps cette semaine</h3>
            <p class="text-xs text-gray-400">Présenté avec le même style que le module Emploi du temps.</p>
        </div>
        <a href="{{ route('timetable.teacher') }}" class="text-xs font-bold hover:underline" style="color:#E87722;">
            Vue complète →
        </a>
    </div>
    @if($mySlots->isEmpty())
    <div class="px-5 py-10 text-center text-sm text-gray-400 italic">
        Aucun créneau programmé.
    </div>
    @else
    <div class="tt-grid-wrap overflow-x-auto">
        @include('timetable.partials.grid', [
            'mode' => 'teacher',
            'printable' => false,
            'days' => $days,
            'gridRows' => $gridRows,
            'slots' => $mySlots,
            'conflicts' => collect(),
            'teacherSubjectCount' => $mySlots->pluck('classSubject.subject_id')->unique()->count(),
        ])
    </div>
    @endif
</div>

@endif
@endsection
