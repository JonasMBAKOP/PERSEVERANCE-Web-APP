@extends('layouts.app')
@section('title', 'Absences')
@section('page-title', 'Gestion des Absences')
@section('page-subtitle', 'Suivi des absences par classe et par élève')

@section('content')

{{-- ── SÉLECTEUR CLASSE ────────────────────────────────────────────────── --}}
<div class="-mx-2 -mt-2 bg-white rounded-2xl shadow-sm border border-gray-100 p-5 mb-5">
    <form method="GET" action="{{ route('absences.index') }}"
          class="flex flex-wrap gap-3 items-end">
        <div class="min-w-[14rem]">
            <label class="block text-xs font-bold text-gray-500 uppercase
                           tracking-wider mb-1.5">
                Section
            </label>
            <select name="section_id" onchange="this.form.submit()"
                    class="w-full px-3 py-2.5 pr-10 border border-gray-200 rounded-xl
                           text-sm focus:outline-none bg-white font-medium"
                    style="color:#1A3A6B;">
                <option value="">— Toutes les sections —</option>
                @foreach($sections as $section)
                <option value="{{ $section->id }}"
                        {{ (string) request('section_id') === (string) $section->id ? 'selected' : '' }}>
                    {{ $section->name }}
                </option>
                @endforeach
            </select>
        </div>
        <div class="flex-1 min-w-48">
            <label class="block text-xs font-bold text-gray-500 uppercase
                           tracking-wider mb-1.5">
                Classe
            </label>
            <select name="class_id" onchange="this.form.submit()"
                    class="w-full px-3 py-2.5 border border-gray-200 rounded-xl
                           text-sm focus:outline-none bg-white font-medium"
                    style="color:#1A3A6B;">
                <option value="">— Toutes les classes —</option>
                @foreach($classes->groupBy('level.section.name') as $secName => $cls)
                <optgroup label="{{ $secName }}">
                    @foreach($cls as $c)
                    <option value="{{ $c->id }}"
                            {{ request('class_id') == $c->id ? 'selected' : '' }}>
                        {{ $c->full_name }}
                    </option>
                    @endforeach
                </optgroup>
                @endforeach
            </select>
        </div>
        @can('manage-absences')
        <a href="{{ route('absences.create', ['class_id' => request('class_id')]) }}"
           class="flex items-center gap-2 px-5 py-2.5 rounded-xl text-white
                  text-sm font-bold transition-all hover:shadow-md"
           style="background-color:#E87722;">
            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                 viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      stroke-width="2.5" d="M12 4v16m8-8H4"/>
            </svg>
            Saisir des absences
        </a>
        @endcan
    </form>
</div>

{{-- ── TABLEAU ÉLÈVES (si classe sélectionnée) ────────────────────────── --}}
@if($selectedClass && $enrollments->isNotEmpty())
<div class="-mx-2 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-5">
    <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
        <h3 class="font-black text-sm" style="color:#1A3A6B;">
            {{ $selectedClass->full_name }}
            <span class="font-normal text-gray-400 text-xs ml-1">
                — Récapitulatif des absences
            </span>
        </h3>
        @php
            $totalClass = $enrollments->sum('total_hours');
            $unjClass   = $enrollments->sum('unjustified_hours');
        @endphp
        <div class="flex gap-4 text-sm">
            <span class="text-gray-500">
                Total : <strong class="text-gray-800">{{ $totalClass }}h</strong>
            </span>
            <span class="text-red-500">
                Injust. : <strong>{{ $unjClass }}h</strong>
            </span>
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr style="background:#F8FAFC; border-bottom:1px solid #E5E7EB;">
                    <th class="text-left px-5 py-3 text-xs font-bold text-gray-400
                               uppercase tracking-wider">Élève</th>
                    <th class="text-center px-4 py-3 text-xs font-bold text-gray-400
                               uppercase tracking-wider">Total</th>
                    <th class="text-center px-4 py-3 text-xs font-bold text-gray-400
                               uppercase tracking-wider">Justifiées</th>
                    <th class="text-center px-4 py-3 text-xs font-bold text-gray-400
                               uppercase tracking-wider">Injustifiées</th>
                    <th class="text-right px-5 py-3 text-xs font-bold text-gray-400
                               uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($enrollments as $enr)
                <tr class="hover:bg-gray-50/50 transition-colors">
                    <td class="px-5 py-3.5">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-full flex items-center
                                        justify-center text-white text-xs font-bold
                                        flex-shrink-0"
                                 style="background:{{ $enr->student->gender==='M'
                                     ? '#1D4ED8' : '#BE185D' }};">
                                {{ strtoupper(substr($enr->student->last_name,0,1)
                                   .substr($enr->student->first_name,0,1)) }}
                            </div>
                            <div>
                                <p class="text-sm font-bold text-gray-800">
                                    {{ $enr->student->full_name }}
                                </p>
                                <p class="text-xs text-gray-400">
                                    {{ $enr->student->matricule }}
                                </p>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3.5 text-center">
                        <span class="text-sm font-black
                                     {{ $enr->total_hours > 0 ? 'text-amber-600' : 'text-green-600' }}">
                            {{ $enr->total_hours }}h
                        </span>
                    </td>
                    <td class="px-4 py-3.5 text-center">
                        <span class="text-sm font-semibold text-blue-600">
                            {{ $enr->justified_hours }}h
                        </span>
                    </td>
                    <td class="px-4 py-3.5 text-center">
                        <span class="text-sm font-black
                                     {{ $enr->unjustified_hours > 0 ? 'text-red-600' : 'text-gray-300' }}">
                            {{ $enr->unjustified_hours }}h
                        </span>
                    </td>
                    <td class="px-5 py-3.5 text-right">
                        <a href="{{ route('absences.student', $enr) }}"
                           class="text-xs font-bold hover:underline"
                           style="color:#1A3A6B;">
                            Détails →
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- ── ABSENCES RÉCENTES ────────────────────────────────────────────────── --}}
<div class="-mx-2 bg-white rounded-2xl shadow-sm border border-gray-100">
    <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
        <h3 class="font-black text-sm" style="color:#1A3A6B;">
            Absences récentes
        </h3>
    </div>
    @if($recentAbsences->isEmpty())
    <div class="px-5 py-8 text-center text-sm text-gray-400 italic">
        Aucune absence enregistrée.
    </div>
    @else
    <div class="max-h-[28rem] overflow-x-auto overflow-y-auto divide-y divide-gray-50 sm:max-h-none">
        @foreach($recentAbsences as $ab)
        <div class="min-w-[760px] px-5 py-3 flex items-center justify-between gap-4">
            <div class="flex items-center gap-3 min-w-0">
                <div class="w-8 h-8 rounded-full flex items-center justify-center
                            text-white text-xs font-bold flex-shrink-0"
                     style="background:#1A3A6B;">
                    {{ strtoupper(substr($ab->studentEnrollment?->student?->last_name??'?',0,1)) }}
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-bold text-gray-800 truncate">
                        {{ $ab->studentEnrollment?->student?->full_name ?? 'Élève transféré ou supprimé' }}
                    </p>
                    <p class="text-xs text-gray-400">
                        {{ $ab->studentEnrollment?->classGroup?->full_name ?? 'Classe inconnue' }}
                        @if($ab->classSubject)
                        · {{ $ab->classSubject->subject->name_fr }}
                        @endif
                        · Abs. {{ $ab->absence_date->format('d/m/Y') }}
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-4 flex-shrink-0">
                <div class="text-right">
                    <p class="text-sm font-black"
                       style="color:{{ $ab->is_justified ? '#1A5C2A' : '#EF4444' }};">
                        {{ $ab->hours }}h
                    </p>
                    <p class="text-xs text-gray-400">
                        {{ $ab->created_at->format('d/m/Y H:i') }}
                    </p>
                </div>
                <span class="px-2 py-0.5 rounded-full text-xs font-bold"
                      style="{{ $ab->is_justified
                          ? 'background:#D1FAE5;color:#065F46;'
                          : 'background:#FEE2E2;color:#991B1B;' }}">
                    {{ $ab->is_justified ? 'Justifiée' : 'Injustifiée' }}
                </span>
                @can('manage-absences')
                <form method="POST"
                      action="{{ route('absences.justify', $ab) }}">
                    @csrf @method('PATCH')
                    <button type="submit"
                            class="text-xs font-medium px-2.5 py-1 rounded-lg
                                   border border-gray-200 text-gray-600
                                   hover:bg-gray-50 transition-colors">
                        {{ $ab->is_justified ? 'Injustifier' : 'Justifier' }}
                    </button>
                </form>
                @endcan
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>

@endsection
