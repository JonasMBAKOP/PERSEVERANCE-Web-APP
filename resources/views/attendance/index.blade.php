@extends('layouts.app')

@section('title', 'Appel du jour')
@section('page-title', 'Appel du jour')
@section('page-subtitle', 'Appel journalier et recueil des absences')

@section('content')
<div class="-mx-2 -mt-2 space-y-5 pb-2">
    <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
        <form method="GET" action="{{ route('attendance.index') }}" class="grid gap-4 md:grid-cols-[1fr_220px_auto] md:items-end">
            <div>
                <label class="mb-1.5 block text-xs font-black uppercase tracking-wider text-gray-500">Classe</label>
                <select name="class_id" onchange="this.form.submit()" class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm font-semibold text-[#1A3A6B] focus:border-[#1A3A6B] focus:outline-none">
                    <option value="">Selectionner une classe</option>
                    @foreach($classes->groupBy('level.section.name') as $sectionName => $sectionClasses)
                        <optgroup label="{{ $sectionName }}">
                            @foreach($sectionClasses as $class)
                                <option value="{{ $class->id }}" @selected($selectedClass?->id === $class->id)>{{ $class->full_name }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-black uppercase tracking-wider text-gray-500">Date de l'appel</label>
                <input type="date" name="date" value="{{ $date->toDateString() }}" @if(!$isUnrestricted) max="{{ now()->toDateString() }}" @endif onchange="this.form.submit()" @disabled(auth()->user()->hasAnyRole(['enseignant', 'assistant-direction']) && ! $isUnrestricted) class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm font-semibold text-[#1A3A6B] focus:border-[#1A3A6B] focus:outline-none disabled:cursor-not-allowed disabled:bg-gray-100 disabled:opacity-70">
            </div>
            <a href="{{ route('absences.index', ['class_id' => $selectedClass?->id]) }}" class="inline-flex items-center justify-center rounded-xl border border-gray-200 px-4 py-2.5 text-sm font-bold text-gray-600 hover:bg-gray-50">Voir les absences</a>
        </form>
    </div>

    @if(!$selectedClass)
        <div class="rounded-2xl border border-dashed border-gray-300 bg-white px-6 py-16 text-center shadow-sm">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-50 text-amber-600">
                <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="1.8" d="M8 7V3m8 4V3M4 11h16M5 5h14a1 1 0 011 1v13a1 1 0 01-1 1H5a1 1 0 01-1-1V6a1 1 0 011-1z"/></svg>
            </div>
            <h2 class="mt-4 text-lg font-black text-gray-800">Choisissez une classe</h2>
            <p class="mt-1 text-sm text-gray-500">L'appel est effectue une seule fois pour toute la journee, independamment de l'emploi du temps.</p>
        </div>
    @else
        <form method="POST" action="{{ route('attendance.store') }}" class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
            @csrf
            <input type="hidden" name="class_group_id" value="{{ $selectedClass->id }}">
            <input type="hidden" name="absence_date" value="{{ $date->toDateString() }}">
            <div class="border-b border-gray-100 bg-[#1A3A6B] px-5 py-4 text-white">
                <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="font-black">{{ $selectedClass->full_name }}</h2>
                        <p class="text-xs text-blue-100">{{ $date->translatedFormat('l d F Y') }} &middot; Appel journalier</p>
                    </div>
                    <span class="text-xs font-bold text-blue-100">Par defaut, tous les eleves sont absents</span>
                </div>
            </div>

            @if($enrollments->isEmpty())
                <div class="px-5 py-12 text-center text-sm text-gray-500">Aucun eleve actif dans cette classe.</div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[900px] text-sm">
                        <thead class="bg-gray-50 text-left text-xs uppercase tracking-wider text-gray-400">
                            <tr class="border-b border-gray-100">
                                <th class="px-5 py-3">Eleve</th>
                                <th class="px-4 py-3 text-center">Statut</th>
                                <th class="px-4 py-3 text-center">Heure d'arrivee</th>
                                <th class="px-5 py-3">Observations</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                        @foreach($enrollments as $enrollment)
                            @php($record = $records->get($enrollment->id))
                            @php($status = $record?->status === 'present' ? 'present' : 'absent')
                            <tr class="attendance-row hover:bg-gray-50/60" data-row="{{ $enrollment->id }}">
                                <td class="px-5 py-3">
                                    <div class="font-bold text-gray-800">{{ $enrollment->student->full_name }}</div>
                                    <div class="text-xs text-gray-400">{{ $enrollment->student->matricule }}</div>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <select name="attendance[{{ $enrollment->id }}][status]" onchange="toggleAttendanceRow(this)" class="attendance-status rounded-xl border border-gray-200 px-3 py-2 text-sm font-bold text-[#1A3A6B] focus:border-[#1A3A6B] focus:outline-none">
                                        <option value="absent" @selected($status === 'absent')>Absent</option>
                                        <option value="present" @selected($status === 'present')>Present</option>
                                    </select>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <input type="time" name="attendance[{{ $enrollment->id }}][arrival_time]" value="{{ $record?->arrival_time ? substr((string) $record->arrival_time, 0, 5) : '' }}" class="attendance-arrival rounded-xl border border-gray-200 px-3 py-2 text-sm font-semibold text-[#1A3A6B] focus:border-[#1A3A6B] focus:outline-none disabled:cursor-not-allowed disabled:bg-gray-100 disabled:opacity-60">
                                </td>
                                <td class="px-5 py-3">
                                    <input type="text" name="attendance[{{ $enrollment->id }}][observation]" value="{{ $record?->observation }}" placeholder="Observation facultative" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm text-gray-700 focus:border-[#1A3A6B] focus:outline-none">
                                    @if($record?->delay_minutes > 0)
                                        <span class="mt-1 block text-xs font-bold text-amber-600">Retard: {{ $record->delay_minutes }} min</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="flex flex-col gap-3 border-t border-gray-100 bg-gray-50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-xs text-gray-500">Tout retard apres 07:30 est enregistre en minutes et ajoute au total d'heures d'absence de l'eleve.</p>
                    <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-[#E87722] px-5 py-2.5 text-sm font-black text-white shadow-sm hover:bg-[#d96818]">Enregistrer l'appel</button>
                </div>
            @endif
        </form>
    @endif
</div>

@push('scripts')
<script>
    function toggleAttendanceRow(select) {
        const row = select.closest('.attendance-row');
        const present = select.value === 'present';
        const arrival = row.querySelector('.attendance-arrival');
        arrival.disabled = !present;
        if (!present) arrival.value = '';
    }

    document.querySelectorAll('.attendance-status').forEach(toggleAttendanceRow);
</script>
@endpush
@endsection
