@extends('layouts.app')

@section('title', 'Absences — ' . $classGroup->full_name)
@section('page-title', 'Absences — ' . $classGroup->full_name)
@section('page-subtitle', 'Saisie et suivi des absences · ' . $classGroup->level?->section?->name)

@section('content')

{{-- ── NAVIGATION ──────────────────────────────────────────────────────────── --}}
<div class="flex items-center justify-between mb-6">
    <a href="{{ route('absences.index') }}"
       class="inline-flex items-center gap-2 text-sm font-semibold text-gray-500 hover:text-gray-800 transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        Retour aux absences
    </a>
    <div class="text-right">
        <p class="text-sm font-bold text-gray-700">{{ $classGroup->full_name }}</p>
        <p class="text-xs text-gray-400">{{ $enrollments->count() }} élève(s) · Année {{ $classGroup->academicYear?->label }}</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- ── FORMULAIRE DE SAISIE ─────────────────────────────────────────────── --}}
    @can('manage-absences')
    <div class="lg:col-span-1">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 sticky top-6">
            <h3 class="font-black text-sm mb-4" style="color:#1A3A6B;">
                <svg class="w-4 h-4 inline mr-1.5 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Saisir une absence
            </h3>

            <form method="POST" action="{{ route('absences.store', $classGroup->id) }}" class="space-y-4">
                @csrf

                {{-- Élève --}}
                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-1.5">Élève *</label>
                    <select name="student_enrollment_id" required
                            class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm font-semibold
                                   focus:ring-2 focus:ring-blue-200 focus:border-blue-400 focus:outline-none transition-all">
                        <option value="">— Sélectionner —</option>
                        @foreach($enrollments as $e)
                        <option value="{{ $e->id }}">
                            {{ $e->student->full_name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                {{-- Date --}}
                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-1.5">Date *</label>
                    <input type="date" name="absence_date" required
                           value="{{ old('absence_date', now()->format('Y-m-d')) }}"
                           max="{{ now()->format('Y-m-d') }}"
                           class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm font-semibold
                                  focus:ring-2 focus:ring-blue-200 focus:border-blue-400 focus:outline-none transition-all">
                </div>

                {{-- Période & Durée --}}
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1.5">Période</label>
                        <select name="period"
                                class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm font-semibold
                                       focus:ring-2 focus:ring-blue-200 focus:border-blue-400 focus:outline-none transition-all">
                            <option value="">— Toute la journée —</option>
                            <option value="matin">Matin</option>
                            <option value="apres-midi">Après-midi</option>
                            <option value="cours_1">1ère heure</option>
                            <option value="cours_2">2e heure</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1.5">Durée (h) *</label>
                        <input type="number" name="hours" required
                               min="0.5" max="8" step="0.5"
                               value="{{ old('hours', '1') }}"
                               class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm font-semibold
                                      focus:ring-2 focus:ring-blue-200 focus:border-blue-400 focus:outline-none transition-all">
                    </div>
                </div>

                {{-- Matière --}}
                @if($classGroup->classSubjects->isNotEmpty())
                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-1.5">Matière concernée</label>
                    <select name="class_subject_id"
                            class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm font-semibold
                                   focus:ring-2 focus:ring-blue-200 focus:border-blue-400 focus:outline-none transition-all">
                        <option value="">— Toutes matières —</option>
                        @foreach($classGroup->classSubjects as $cs)
                        <option value="{{ $cs->id }}">{{ $cs->subject->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                {{-- Justifiée --}}
                <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl">
                    <input type="checkbox" name="is_justified" id="is_justified" value="1"
                           class="w-4 h-4 rounded border-gray-300 text-green-600 focus:ring-green-200">
                    <label for="is_justified" class="text-sm font-semibold text-gray-700 cursor-pointer">
                        Absence justifiée
                    </label>
                </div>

                {{-- Justification --}}
                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-1.5">Motif / Justification</label>
                    <textarea name="justification" rows="2"
                              placeholder="Maladie, rendez-vous médical, décès..."
                              class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm font-semibold
                                     focus:ring-2 focus:ring-blue-200 focus:border-blue-400 focus:outline-none
                                     transition-all resize-none">{{ old('justification') }}</textarea>
                </div>

                <button type="submit"
                        class="w-full py-2.5 rounded-xl text-sm font-bold text-white hover:opacity-90 transition-all shadow-sm"
                        style="background:#1A3A6B;">
                    Enregistrer l'absence
                </button>
            </form>
        </div>
    </div>
    @endcan

    {{-- ── LISTE DES ABSENCES ───────────────────────────────────────────────── --}}
    <div class="{{ auth()->user()->can('manage-absences') ? 'lg:col-span-2' : 'lg:col-span-3' }}">

        {{-- Récapitulatif par élève --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-6">
            <div class="px-5 py-4 border-b border-gray-50">
                <h3 class="font-black text-sm" style="color:#1A3A6B;">Récapitulatif par élève</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead>
                        <tr class="border-b border-gray-100 text-gray-400 text-3xs uppercase font-black tracking-wider"
                            style="background:#F8FAFC;">
                            <th class="text-left px-5 py-3">Élève</th>
                            <th class="text-center px-4 py-3">Total h</th>
                            <th class="text-center px-4 py-3">Justifiées</th>
                            <th class="text-center px-4 py-3">Non justifiées</th>
                            <th class="text-center px-4 py-3">Alerte</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($enrollments as $e)
                        @php
                            $totalH    = $e->absences->sum('effective_hours');
                            $justH     = $e->absences->where('is_justified', true)->sum('effective_hours');
                            $unjustH   = $e->absences->where('is_justified', false)->sum('effective_hours');
                            $alertColor= $unjustH >= 30 ? 'text-red-600' : ($unjustH >= 10 ? 'text-yellow-600' : 'text-gray-300');
                        @endphp
                        @if($totalH > 0)
                        <tr class="hover:bg-gray-50/40 font-semibold text-gray-700">
                            <td class="px-5 py-3 font-bold text-gray-800">
                                {{ $e->student->full_name }}
                            </td>
                            <td class="px-4 py-3 text-center font-black" style="color:#1A3A6B;">
                                {{ number_format($totalH, 1) }}h
                            </td>
                            <td class="px-4 py-3 text-center text-green-600">
                                {{ $justH > 0 ? number_format($justH, 1) . 'h' : '—' }}
                            </td>
                            <td class="px-4 py-3 text-center {{ $unjustH > 0 ? 'text-red-500 font-bold' : 'text-gray-300' }}">
                                {{ $unjustH > 0 ? number_format($unjustH, 1) . 'h' : '—' }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($unjustH >= 30)
                                <span class="text-xs font-bold text-red-600"><svg class="inline h-4 w-4 mr-1 align-[-2px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>Conseil</span>
                                @elseif($unjustH >= 10)
                                <span class="text-xs font-bold text-yellow-600"><svg class="inline h-4 w-4 mr-1 align-[-2px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>Convocation</span>
                                @else
                                <span class="text-gray-300">—</span>
                                @endif
                            </td>
                        </tr>
                        @endif
                        @endforeach
                        @if($enrollments->sum(fn($e) => $e->absences->sum('effective_hours')) == 0)
                        <tr>
                            <td colspan="5" class="px-5 py-8 text-center text-gray-400 italic">
                                Aucune absence enregistrée pour cette classe.
                            </td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Journal des absences récentes --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-50">
                <h3 class="font-black text-sm" style="color:#1A3A6B;">Journal des absences</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead>
                        <tr class="border-b border-gray-100 text-gray-400 text-3xs uppercase font-black tracking-wider"
                            style="background:#F8FAFC;">
                            <th class="text-left px-5 py-3">Élève</th>
                            <th class="text-center px-4 py-3">Date</th>
                            <th class="text-left px-4 py-3">Matière</th>
                            <th class="text-center px-4 py-3">Durée</th>
                            <th class="text-center px-4 py-3">Statut</th>
                            @can('manage-absences')
                            <th class="text-center px-4 py-3">Actions</th>
                            @endcan
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($recentAbsences as $abs)
                        <tr class="hover:bg-gray-50/40 font-semibold text-gray-700">
                            <td class="px-5 py-3">
                                <p class="font-bold text-gray-800">
                                    {{ $abs->studentEnrollment->student->full_name }}
                                </p>
                                @if($abs->justification)
                                <p class="text-3xs text-gray-400 truncate max-w-[160px]">
                                    {{ $abs->justification }}
                                </p>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center text-gray-600">
                                {{ $abs->absence_date->format('d/m/Y') }}
                                @if($abs->period)
                                <br><span class="text-3xs text-gray-400">{{ $abs->period }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-500">
                                {{ $abs->classSubject?->subject?->name ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-center font-black" style="color:#1A3A6B;">
                                {{ number_format($abs->effective_hours, 1) }}h
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($abs->is_justified)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-3xs font-bold"
                                      style="background:rgba(26,92,42,0.1);color:#1A5C2A;"><svg class="inline h-4 w-4 mr-1 align-[-2px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>Justifiée</span>
                                @else
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-3xs font-bold bg-red-50 text-red-500">
                                    <svg class="inline h-4 w-4 align-[-2px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>Non justifiée
                                </span>
                                @endif
                            </td>
                            @can('manage-absences')
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-center gap-1.5">
                                    {{-- Basculer statut justification --}}
                                    <form method="POST" action="{{ route('absences.justify', $abs->id) }}" class="inline">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="is_justified" value="{{ $abs->is_justified ? '0' : '1' }}">
                                        <button type="submit" title="{{ $abs->is_justified ? 'Retirer justification' : 'Justifier' }}"
                                                class="p-1.5 rounded-lg border transition-all
                                                       {{ $abs->is_justified
                                                           ? 'border-red-100 text-red-400 hover:bg-red-50'
                                                           : 'border-green-100 text-green-600 hover:bg-green-50' }}">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="{{ $abs->is_justified ? 'M6 18L18 6M6 6l12 12' : 'M5 13l4 4L19 7' }}"/>
                                            </svg>
                                        </button>
                                    </form>
                                    {{-- Supprimer --}}
                                    <form method="POST" action="{{ route('absences.destroy', $abs->id) }}" class="inline">
                                        @csrf @method('DELETE')
                                        <button type="submit"
                                                onclick="return confirm('Supprimer cette absence ?')"
                                                title="Supprimer"
                                                class="p-1.5 rounded-lg border border-gray-100 text-gray-400 hover:bg-red-50 hover:text-red-500 hover:border-red-100 transition-all">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                            @endcan
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-5 py-10 text-center text-gray-400 italic">
                                Aucune absence enregistrée pour cette classe.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

@endsection
