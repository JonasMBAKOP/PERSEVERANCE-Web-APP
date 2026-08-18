@extends('layouts.app')

@section('title', 'Dossiers Patients')
@section('page-title', 'Dossiers Patients')
@section('page-subtitle', 'Liste des élèves ayant un historique à l’infirmerie')

@section('content')
<div class="space-y-6 pb-20">
    <section class="rounded-2xl border border-sky-100 bg-white p-5 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="text-base font-bold text-gray-900">Recherche d’élève</h2>
                <p class="mt-1 text-sm text-gray-500">Cherchez un élève puis ouvrez son dossier complet.</p>
            </div>
            <form method="GET" class="flex w-full max-w-xl gap-2">
                <input type="search" name="q" value="{{ $search }}" placeholder="Nom, prénom ou matricule" class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm outline-none focus:border-sky-500 focus:ring-2 focus:ring-sky-100">
                <button type="submit" class="rounded-xl bg-sky-700 px-4 py-2.5 text-sm font-bold text-white hover:bg-sky-800">Rechercher</button>
            </form>
        </div>
    </section>

    <section class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
        <div class="border-b border-gray-100 p-5">
            <h3 class="text-base font-bold text-gray-900">Elèves avec historique infirmier</h3>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-bold uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Élève</th>
                        <th class="px-4 py-3">Matricule</th>
                        <th class="px-4 py-3">Consultations</th>
                        <th class="px-4 py-3">Dernière visite</th>
                        <th class="px-4 py-3 text-right">Dossier</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-gray-700">
                    @forelse($patients as $patient)
                        @php
                            $lastVisitDate = $patient->infirmary_visits_max_visit_date;
                            $lastVisitLabel = $lastVisitDate
                                ? \Illuminate\Support\Carbon::parse($lastVisitDate)->format('d/m/Y')
                                : '—';
                        @endphp
                        <tr class="hover:bg-sky-50/30">
                            <td class="px-4 py-3 font-semibold text-gray-900">{{ $patient->full_name }}</td>
                            <td class="px-4 py-3">{{ $patient->matricule ?? '—' }}</td>
                            <td class="px-4 py-3">{{ $patient->infirmary_visits_count }}</td>
                            <td class="px-4 py-3">{{ $lastVisitLabel }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('infirmary.patients.show', $patient) }}" class="rounded-lg border border-sky-200 bg-sky-50 px-3 py-1.5 text-xs font-semibold text-sky-700 hover:bg-sky-100">Voir dossier</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-12 text-center text-sm text-gray-400">Aucun dossier patient trouvé pour ce filtre.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($patients->hasPages())
            <div class="border-t border-gray-100 px-5 py-4">{{ $patients->links() }}</div>
        @endif
    </section>
</div>
@endsection
