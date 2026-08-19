@extends('layouts.app')

@section('title', 'Présences du personnel')
@section('page-title', 'Présences')
@section('page-subtitle', 'Marquer les présences du personnel pour une date donnée')

@section('content')
<div class="max-w-6xl mx-auto space-y-6">
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
            <form method="GET" action="{{ route('staff.presences.index') }}" class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-end">
                <div class="w-full sm:w-[180px]">
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-500">Date</label>
                    <input type="date" name="date" value="{{ $date->toDateString() }}" class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                </div>
                <div class="w-full sm:w-[200px]">
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-500">Contrat</label>
                    <select name="contract_type" class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                        <option value="all" {{ ($contractType ?? 'all') === 'all' ? 'selected' : '' }}>Tous les contrats</option>
                        <option value="permanent" {{ ($contractType ?? '') === 'permanent' ? 'selected' : '' }}>Permanents</option>
                        <option value="semi" {{ ($contractType ?? '') === 'semi' ? 'selected' : '' }}>Semi-permanents</option>
                        <option value="vacataire" {{ ($contractType ?? '') === 'vacataire' ? 'selected' : '' }}>Vacataires</option>
                    </select>
                </div>
                <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-blue-700">Filtrer</button>
            </form>

            <div class="flex flex-wrap items-center justify-end gap-3">
                <a href="{{ route('staff.presences.dossier', ['contract_type' => $contractType ?? 'all', 'dossier_type' => 'month', 'month' => $date->format('Y-m')]) }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-slate-100 px-4 py-2.5 text-sm font-bold text-slate-700 transition hover:bg-slate-200">Dossier de présences</a>
                <a href="{{ route('staff.presences.mark', ['date' => $date->toDateString(), 'contract_type' => $contractType ?? 'all']) }}" class="inline-flex items-center justify-center rounded-xl bg-green-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-green-700">Marquer les présences</a>
                <a target="_blank" rel="noopener" href="{{ route('staff.presences.print', ['date' => $date->toDateString(), 'contract_type' => $contractType ?? 'all']) }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9V4h12v5M6 18H5a2 2 0 01-2-2v-5a2 2 0 012-2h14a2 2 0 012 2v5h-1M7 14h10v6H7z"/></svg>
                    Imprimer
                </a>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="bg-white p-4 rounded shadow">
            <div class="text-sm text-gray-500">Total attendus</div>
            <div class="text-2xl font-bold">{{ $staff->count() }}</div>
        </div>
        <div class="bg-white p-4 rounded shadow">
            <div class="text-sm text-gray-500">Présents</div>
            <div class="text-2xl font-bold">{{ $presences->filter(fn($p) => $p->status === 'present')->count() }}</div>
        </div>
        <div class="bg-white p-4 rounded shadow">
            <div class="text-sm text-gray-500">Absents</div>
            <div class="text-2xl font-bold">{{ $absentees->count() }}</div>
        </div>
    </div>

        <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white shadow-sm">
            <table class="min-w-[760px] w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left">Nom</th>
                        <th class="px-4 py-3">Contrat</th>
                        <th class="px-4 py-3">Arrivée</th>
                        <th class="px-4 py-3">Départ</th>
                        <th class="px-4 py-3">Statut</th>
                        <th class="px-4 py-3">Observations</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    @forelse($staff as $member)
                        @php $p = $presences->get($member->id); @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3">{{ $member->full_name }}</td>
                            <td class="px-4 py-3 text-center">{{ ucfirst(str_replace('_', ' ', $member->contract_type)) }}</td>
                            <td class="px-4 py-3 text-center">{{ $p?->arrival_time ? substr((string) $p->arrival_time, 0, 5) : '—' }}</td>
                            <td class="px-4 py-3 text-center">{{ $p?->departure_time ? substr((string) $p->departure_time, 0, 5) : '—' }}</td>
                            <td class="px-4 py-3 text-center">
                                @if($p && $p->status === 'present')
                                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-semibold bg-green-100 text-green-800">Présent</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-semibold bg-red-100 text-red-800">Absent</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">{{ $p?->note ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-gray-500">Aucun membre du personnel trouvé pour ces filtres.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
</div>
@endsection
