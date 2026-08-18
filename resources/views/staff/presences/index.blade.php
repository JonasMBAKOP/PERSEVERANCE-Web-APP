@extends('layouts.app')

@section('title', 'Présences du personnel')
@section('page-title', 'Présences')
@section('page-subtitle', 'Marquer les présences du personnel pour une date donnée')

@section('content')
<div class="max-w-6xl mx-auto space-y-6">
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
            <form method="GET" action="{{ route('staff.presences.index') }}" class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-end">
                <div class="w-full sm:w-[200px]">
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-500">Date</label>
                    <input type="date" name="date" value="{{ $date->toDateString() }}" class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                </div>
                <div class="w-full sm:w-[220px]">
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
                            <td class="px-4 py-3 text-center">{{ $p?->arrival_time ?? '—' }}</td>
                            <td class="px-4 py-3 text-center">{{ $p?->departure_time ?? '—' }}</td>
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
