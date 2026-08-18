@extends('layouts.app')

@section('title', 'Planning de passage')
@section('page-title', 'Planning de passage')
@section('page-subtitle', 'Liste des personnels programmés par jour')

@section('breadcrumb')
    <a href="{{ route('staff.index') }}" class="hover:text-gray-700 transition-colors">
        Personnel
    </a>
    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
    </svg>
    <span class="font-semibold text-[#1A3A6B]">Planning de passage</span>
@endsection

@section('content')
<div class="space-y-6">
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.25em] text-slate-400">Filtre</p>
                <h2 class="mt-1 text-2xl font-black text-[#132F55]">Passage du personnel</h2>
            </div>
            <form method="GET" action="{{ route('staff.passage-planning') }}" class="flex flex-col gap-3 md:flex-row md:items-end">
                <div class="w-full md:w-64">
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-500">Jour</label>
                    <select name="day" class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                        @foreach($days as $dayId => $dayLabel)
                            <option value="{{ $dayId }}" {{ (int) $selectedDay === (int) $dayId ? 'selected' : '' }}>{{ $dayLabel }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="w-full md:w-64">
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-500">Contrat</label>
                    <select name="contract" class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                        <option value="" {{ $contract === null ? 'selected' : '' }}>Tous</option>
                        @foreach($contracts as $key => $label)
                            <option value="{{ $key }}" {{ $contract === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-center gap-2">
                    <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-[#1A3A6B] px-4 py-2.5 text-sm font-bold text-white">Filtrer</button>
                    <a href="{{ route('staff.passage-planning.preview', array_merge(['day' => $selectedDay], $contract ? ['contract' => $contract] : [])) }}" target="_blank" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50">Imprimer</a>
                </div>
            </form>
        </div>
    </div>

    @if(! $activeYear)
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-8 text-center">
            <p class="font-bold text-amber-800">Aucune année scolaire active.</p>
            <p class="mt-1 text-sm text-amber-700">Activez une année pour lancer le planning.</p>
        </div>
    @else
        <div class="grid gap-4 lg:grid-cols-2">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wide text-gray-400">Jour sélectionné</p>
                <p class="mt-2 text-2xl font-black text-[#1A3A6B]">{{ $days[$selectedDay] }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wide text-gray-400">Personnels programmés</p>
                <p class="mt-2 text-2xl font-black text-[#1A5C2A]">{{ $scheduleItems->total() }}</p>
            </div>
        </div>

        @if($scheduleItems->isEmpty())
            <div class="rounded-2xl border border-dashed border-gray-200 bg-white p-10 text-center shadow-sm">
                <p class="font-bold text-gray-700">Aucun personnel programmé pour ce jour.</p>
                <p class="mt-1 text-sm text-gray-500">Les créneaux apparaîtront ici après la saisie dans l’emploi du temps.</p>
            </div>
        @else
            <div class="space-y-4">
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm overflow-x-auto">
                    <table class="min-w-full bordereau-table text-sm">
                        <thead>
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-bold uppercase tracking-wide text-gray-400">#</th>
                                <th class="px-3 py-2 text-left text-xs font-bold uppercase tracking-wide text-gray-400">Nom</th>
                                <th class="px-3 py-2 text-left text-xs font-bold uppercase tracking-wide text-gray-400">Contrat</th>
                                <th class="px-3 py-2 text-left text-xs font-bold uppercase tracking-wide text-gray-400">Téléphone</th>
                                <th class="px-3 py-2 text-left text-xs font-bold uppercase tracking-wide text-gray-400">Poste(s)</th>
                                <th class="px-3 py-2 text-left text-xs font-bold uppercase tracking-wide text-gray-400">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($scheduleItems as $item)
                                <tr class="bg-white border-t">
                                    <td class="px-3 py-2 font-semibold text-gray-800">{{ $scheduleItems->firstItem() + $loop->index }}</td>
                                    <td class="px-3 py-2 font-semibold text-gray-800">{{ $item['staff']->full_name }}</td>
                                    <td class="px-3 py-2 text-gray-700">{{ $item['staff']->contract_label }}</td>
                                    <td class="px-3 py-2 text-gray-700">{{ $item['staff']->phone ?: '—' }}</td>
                                    <td class="px-3 py-2 text-gray-700">{{ $item['staff']->positions->pluck('position')->map(fn($p) => ucfirst(str_replace('_', ' ', $p)))->join(' • ') ?: 'Aucun' }}</td>
                                    <td class="px-3 py-2 text-gray-700">
                                        <a href="{{ route('timetable.teacher', ['staff_id' => $item['staff']->id]) }}" class="rounded-md bg-blue-600 px-3 py-1 text-white text-sm">Voir</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">
                    {{ $scheduleItems->withQueryString()->links() }}
                </div>
            </div>
        @endif
    @endif
</div>
@endsection
