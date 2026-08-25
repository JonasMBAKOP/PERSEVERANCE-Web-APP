@extends('layouts.app')
@section('title', 'Absences — ' . $enrollment->student->full_name)
@section('page-title', 'Absences de l\'élève')
@section('page-subtitle'){{ $enrollment->student->full_name }}@endsection

@section('breadcrumb')
    <a href="{{ route('absences.index') }}" class="hover:text-gray-700">Absences</a>
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
    </svg>
    <span style="color:#1A3A6B;" class="font-medium">{{ $enrollment->student->full_name }}</span>
@endsection

@section('content')

{{-- ── EN-TÊTE ───────────────────────────────────────────────────────────── --}}
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 mb-5">
    <div class="flex items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-full flex items-center justify-center
                        text-white font-black text-xl"
                 style="background:{{ $enrollment->student->gender==='M'
                     ? '#1D4ED8' : '#BE185D' }};">
                {{ strtoupper(substr($enrollment->student->last_name,0,1)
                   .substr($enrollment->student->first_name,0,1)) }}
            </div>
            <div>
                <h2 class="font-black text-lg" style="color:#1A3A6B;">
                    {{ $enrollment->student->full_name }}
                </h2>
                <p class="text-sm text-gray-500">
                    {{ $enrollment->classGroup->full_name }}
                    · {{ $enrollment->academicYear->label }}
                </p>
            </div>
        </div>
        <div class="grid grid-cols-3 gap-4 text-center">
            <div class="px-4 py-3 rounded-xl bg-gray-50">
                <p class="text-xl font-black text-gray-800">{{ $totalH }}h</p>
                <p class="text-xs text-gray-500">Total</p>
            </div>
            <div class="px-4 py-3 rounded-xl" style="background:#EAF5EA;">
                <p class="text-xl font-black text-green-700">{{ $justifiedH }}h</p>
                <p class="text-xs text-gray-500">Justifiées</p>
            </div>
            <div class="px-4 py-3 rounded-xl bg-red-50">
                <p class="text-xl font-black text-red-600">{{ $unjustifiedH }}h</p>
                <p class="text-xs text-gray-500">Injustifiées</p>
            </div>
        </div>
    </div>
</div>

{{-- ── LISTE ─────────────────────────────────────────────────────────────── --}}
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="px-5 py-4 border-b border-gray-100 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <h3 class="font-black text-sm" style="color:#1A3A6B;">
            Historique des absences ({{ $enrollment->absences->count() }})
        </h3>
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end lg:ml-auto">
            <form method="GET" action="{{ route('absences.student', $enrollment) }}" class="flex flex-wrap items-end gap-2">
                <div>
                    <label for="start_date" class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-gray-400">Du</label>
                    <input id="start_date" type="date" name="start_date" value="{{ $startDate }}" class="rounded-lg border border-gray-200 px-2.5 py-2 text-xs text-gray-700 focus:border-[#1A3A6B] focus:outline-none">
                </div>
                <div>
                    <label for="end_date" class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-gray-400">Au</label>
                    <input id="end_date" type="date" name="end_date" value="{{ $endDate }}" class="rounded-lg border border-gray-200 px-2.5 py-2 text-xs text-gray-700 focus:border-[#1A3A6B] focus:outline-none">
                </div>
                <button type="submit" class="rounded-lg bg-[#1A3A6B] px-3 py-2 text-xs font-bold text-white hover:bg-[#143052]">Filtrer</button>
                @if($startDate || $endDate)
                    <a href="{{ route('absences.student', $enrollment) }}" class="rounded-lg border border-gray-200 px-3 py-2 text-xs font-bold text-gray-600 hover:bg-gray-50">Réinitialiser</a>
                @endif
            </form>
            @can('manage-absences')
            <a href="{{ route('absences.create', ['class_id' => $enrollment->class_group_id]) }}" class="text-xs font-bold hover:underline sm:pb-2" style="color:#E87722;">+ Ajouter</a>
            @endcan
        </div>
    </div>

    @if($enrollment->absences->isEmpty())
    <div class="px-5 py-10 text-center text-sm text-gray-400 italic">
        Aucune absence enregistrée.
    </div>
    @else
    <table class="w-full">
        <thead>
            <tr style="background:#F8FAFC; border-bottom:1px solid #E5E7EB;">
                <th class="text-left px-5 py-3 text-xs font-bold text-gray-400
                           uppercase tracking-wider">Date</th>
                <th class="text-left px-4 py-3 text-xs font-bold text-gray-400
                           uppercase tracking-wider hidden sm:table-cell">Matière</th>
                <th class="text-left px-4 py-3 text-xs font-bold text-gray-400
                           uppercase tracking-wider hidden sm:table-cell">Période</th>
                <th class="text-center px-4 py-3 text-xs font-bold text-gray-400
                           uppercase tracking-wider">Heures</th>
                <th class="text-center px-4 py-3 text-xs font-bold text-gray-400
                           uppercase tracking-wider">Statut</th>
                @can('manage-absences')
                <th class="text-right px-5 py-3"></th>
                @endcan
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @foreach($enrollment->absences as $ab)
            <tr class="hover:bg-gray-50/50 transition-colors"
                x-data="{showJustify:false}">
                <td class="px-5 py-3.5">
                    <p class="text-sm font-bold text-gray-800">
                        {{ $ab->absence_date->format('d/m/Y') }}
                    </p>
                    <p class="text-xs text-gray-400">
                        Par : {{ $ab->recordedBy?->name ?? '—' }}
                    </p>
                </td>
                <td class="px-4 py-3.5 text-sm text-gray-700 hidden sm:table-cell">
                    {{ $ab->classSubject?->subject?->name_fr ?? 'Absence générale' }}
                </td>
                <td class="px-4 py-3.5 text-sm text-gray-600 hidden sm:table-cell">
                    @if($ab->timetableSlot)
                        {{ $ab->period ?? 'Période' }}
                        @php $periodOffset = max(0, (int) $ab->timetable_period_index - (int) $ab->timetableSlot->period_index); @endphp
                        <span class="block text-xs text-gray-400">
                            {{ $ab->timetableSlot->start_time?->copy()->addHours($periodOffset)->format('H:i') }}-{{ $ab->timetableSlot->start_time?->copy()->addHours($periodOffset + 1)->format('H:i') }}
                        </span>
                    @else
                        {{ ucfirst($ab->period ?? '—') }}
                    @endif
                </td>
                <td class="px-4 py-3.5 text-center">
                    <span class="text-base font-black"
                          style="color:{{ $ab->is_justified ? '#1A5C2A' : '#EF4444' }};">
                        {{ $ab->hours }}h
                    </span>
                </td>
                <td class="px-4 py-3.5 text-center">
                    <div class="flex flex-col items-center gap-1">
                        <span class="px-2 py-0.5 rounded-full text-xs font-bold"
                              style="{{ $ab->is_justified
                                  ? 'background:#D1FAE5;color:#065F46;'
                                  : 'background:#FEE2E2;color:#991B1B;' }}">
                            {{ $ab->is_justified ? 'Justifiée' : 'Injustifiée' }}
                        </span>
                        @if($ab->justification)
                        <p class="text-xs text-gray-400 italic">
                            {{ Str::limit($ab->justification, 30) }}
                        </p>
                        @endif
                    </div>
                </td>
                @can('manage-absences')
                <td class="px-5 py-3.5 text-right">
                    <div class="flex items-center justify-end gap-1">
                        <form method="POST"
                              action="{{ route('absences.justify', $ab) }}">
                            @csrf @method('PATCH')
                            <button type="submit"
                                    class="text-xs font-medium px-2.5 py-1.5
                                           rounded-lg border border-gray-200
                                           text-gray-600 hover:bg-gray-50">
                                {{ $ab->is_justified ? 'Injustifier' : 'Justifier' }}
                            </button>
                        </form>
                        <form method="POST"
                              action="{{ route('absences.destroy', $ab) }}"
                              onsubmit="return confirm('Supprimer cette absence ?')">
                            @csrf @method('DELETE')
                            <button type="submit"
                                    class="p-1.5 rounded-lg text-gray-400
                                           hover:text-red-600 hover:bg-red-50">
                                <svg class="w-3.5 h-3.5" fill="none"
                                     stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round"
                                          stroke-linejoin="round" stroke-width="2"
                                          d="M19 7l-.867 12.142A2 2 0 0116.138
                                             21H7.862a2 2 0 01-1.995-1.858L5 7m5
                                             4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1
                                             1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </form>
                    </div>
                </td>
                @endcan
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
</div>

@endsection
