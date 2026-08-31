@extends('layouts.app')
@section('title', 'Mon emploi du temps')
@section('page-title', 'Emploi du temps professeur')
@section('page-subtitle', 'Consultation personnelle ou administrative par enseignant')
@push('styles')
<style>
.tt-shell { color:#1F2937; margin-top:0; padding-top:0; }
.tt-shell > :first-child { margin-top:0; }
.tt-hero { background:linear-gradient(135deg,#F8FBFE 0%,#EEF6FC 100%); border:1px solid #DCE8F3; box-shadow:0 12px 32px rgba(26,58,107,.07); }
.tt-panel { background:#fff; border:1px solid #E5EDF5; box-shadow:0 10px 26px rgba(26,58,107,.055); margin-top:0; }
.tt-panel-soft { background:#F8FBFE; border:1px solid #DCE8F3; }
.tt-field { border-color:#D6E2EE; background:#fff; transition:border-color .16s ease, box-shadow .16s ease; }
.tt-field:focus { border-color:#1A3A6B; box-shadow:0 0 0 3px rgba(26,58,107,.10); outline:none; }
.tt-btn-primary { background:#1A3A6B; color:#fff; box-shadow:0 8px 18px rgba(26,58,107,.16); }
.tt-btn-primary:hover { background:#122B50; }
.tt-btn-success { background:#1A5C2A; color:#fff; box-shadow:0 8px 18px rgba(26,92,42,.14); }
.tt-btn-success:hover { background:#12451F; }
.tt-btn-ghost { border:1px solid #DCE8F3; color:#334155; background:#fff; }
.tt-btn-ghost:hover { background:#F8FBFE; border-color:#C8D9EA; }
.tt-note { border:1px solid #CFE0EE; background:#F8FBFE; color:#1A3A6B; }
.tt-grid-wrap table th { background:#F8FBFE; color:#334155; }
.tt-grid-wrap table tbody tr:hover td { background-color:#FAFCFE; }
.tt-grid-wrap table td, .tt-grid-wrap table th { border-color:#E8EEF5; }
</style>
@endpush

@section('content')
@if(!$activeYear)
    <div class="rounded-2xl border border-amber-200 bg-amber-50 p-8 text-center">
        <p class="font-bold text-amber-800">Aucune année scolaire active.</p>
        <p class="mt-1 text-sm text-amber-700">L’emploi du temps enseignant sera disponible dès qu’une année sera active.</p>
    </div>
@else
<div class="tt-shell timetable-page -mx-2 -mt-2 -mb-2 space-y-6 lg:-mx-4 lg:-mt-4 lg:-mb-4">
    {{-- <section class="tt-hero overflow-hidden rounded-2xl p-5 lg:p-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <span class="inline-flex rounded-full bg-white px-3 py-1 text-[11px] font-black uppercase tracking-wide text-[#1A3A6B] shadow-sm">Consultation</span>
                <h2 class="mt-3 text-2xl font-black tracking-tight text-[#132F55]">Emploi du temps individuel</h2>
                <p class="mt-1 max-w-2xl text-sm font-medium text-slate-500">Vue enseignant avec classes, disciplines et periodes hebdomadaires synchronisees avec les emplois du temps des classes.</p>
            </div>
            <div class="rounded-2xl bg-white/80 p-4 text-right shadow-sm">
                <p class="text-[11px] font-black uppercase tracking-wide text-slate-400">Annee scolaire</p>
                <p class="mt-1 text-sm font-black text-[#1A5C2A]">{{ $activeYear?->label }}</p>
            </div>
        </div>
    </section> --}}
    <div class="rounded-3xl border border-slate-200 bg-gradient-to-br from-[#F8FBFE] via-white to-[#F4F8FC] p-5 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div class="max-w-2xl">
                <div class="inline-flex items-center gap-2 rounded-full bg-white px-3 py-1 text-[11px] font-black uppercase tracking-[0.2em] text-[#1A3A6B] shadow-sm">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Consultation enseignant
                </div>
                <h2 class="mt-3 text-2xl font-black tracking-tight text-[#132F55]">Emploi du temps du professeur</h2>
                <p class="mt-1 text-sm text-slate-500">Visualisez rapidement les cours, les matières et les classes associées à un enseignant.</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white/90 px-4 py-3 text-sm shadow-sm">
                <p class="text-[11px] font-black uppercase tracking-[0.2em] text-slate-400">Année scolaire</p>
                <p class="mt-1 font-black text-[#1A5C2A]">{{ $activeYear?->label }}</p>
            </div>
        </div>

        @if($staffList->count() > 1)
            <form method="GET" action="{{ route('timetable.teacher') }}" class="mt-5 flex flex-col gap-3 md:flex-row md:items-end">
                <div class="flex-1">
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-500">Enseignant</label>
                    <select name="staff_id" onchange="this.form.submit()" class="tt-field w-full rounded-xl border px-3 py-2.5 text-sm">
                        @foreach($staffList as $staff)
                            <option value="{{ $staff->id }}" {{ (int) $selectedStaffId === $staff->id ? 'selected' : '' }}>{{ $staff->full_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-center gap-2">
                    <button type="submit" class="tt-btn-ghost inline-flex items-center justify-center gap-2 rounded-xl border border-[#1A3A6B] px-4 py-2.5 text-sm font-bold text-[#1A3A6B] transition hover:bg-[#1A3A6B] hover:text-white">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.036 12.322a1 1 0 010-.644C3.423 7.51 7.36 5 12 5c4.64 0 8.577 2.51 9.964 6.678a1 1 0 010 .644C20.577 16.49 16.64 19 12 19c-4.64 0-8.577-2.51-9.964-6.678z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        Afficher
                    </button>
                    @if($selectedStaff)
                        <a href="{{ route('timetable.teacher.print', $selectedStaff) }}" target="_blank" class="tt-btn-ghost inline-flex items-center justify-center gap-2 rounded-xl border border-[#1A3A6B] px-4 py-2.5 text-sm font-bold text-[#1A3A6B] transition hover:bg-[#1A3A6B] hover:text-white">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 9V3h12v6M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2m-2 0v3H8v-3m-4-5h.01M18 12h.01"/>
                            </svg>
                            Imprimer
                        </a>
                    @endif
                </div>
            </form>
        @else
            <div class="mt-5 flex flex-wrap items-center gap-2">
                @if($selectedStaff)
                    <a href="{{ route('timetable.teacher.print', $selectedStaff) }}" target="_blank" class="tt-btn-ghost inline-flex items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-bold transition"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 9V3h12v6M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2m-2 0v3H8v-3m-4-5h.01M18 12h.01"/></svg>Imprimer</a>
                @endif
            </div>
        @endif
    </div>

    @if(!$selectedStaff)
        <div class="rounded-2xl border border-dashed border-gray-200 bg-white p-10 text-center shadow-sm">
            <p class="font-bold text-gray-700">Aucun dossier enseignant associé à votre compte.</p>
            <p class="mt-1 text-sm text-gray-500">Contactez l’administration pour rattacher votre compte à un personnel enseignant.</p>
        </div>
    @else
        <div class="grid gap-4 lg:grid-cols-[1.4fr_1fr_1fr]">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wide text-gray-400">Enseignant</p>
                        <h2 class="mt-1 text-xl font-black text-[#1A3A6B]">{{ $selectedStaff->full_name }}</h2>
                        <p class="mt-1 text-sm text-gray-500">{{ $activeYear?->label }}</p>
                    </div>
                    <div class="rounded-xl bg-[#EEF6FC] p-2 text-[#1A3A6B]">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zm-8 9a4 4 0 018 0v1H8v-1z"/>
                        </svg>
                    </div>
                </div>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wide text-gray-400">Cours par semaine</p>
                <p class="mt-2 text-2xl font-black text-gray-900">{{ $slots->count() }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wide text-gray-400">Périodes programmées</p>
                <p class="mt-2 text-2xl font-black text-[#1A5C2A]">{{ $slots->sum('periods_count') }}</p>
            </div>
        </div>

        <div class="tt-note rounded-2xl p-4 text-sm font-semibold">
            <strong>Structure commune :</strong> {{ $setting->period_duration_minutes }} min par période, pauses intégrées automatiquement.
        </div>

        <div class="tt-panel overflow-hidden rounded-2xl">
            <div class="border-b border-gray-100 px-5 py-4">
                <h3 class="font-black text-[#1A3A6B]">Emploi du temps du professeur</h3>
                <p class="text-xs text-gray-500">La classe est affichée en premier, la matière juste en dessous.</p>
            </div>
            <div class="tt-grid-wrap overflow-x-auto p-0">
                @include('timetable.partials.grid', [
                    'mode' => 'teacher',
                    'printable' => false,
                    'compact' => true,
                    'days' => $days,
                    'gridRows' => $gridRows,
                    'slots' => $slots,
                    'teacherSubjectCount' => $teacherSubjectCount,
                ])
            </div>
        </div>
    @endif
</div>
@endif
@endsection
