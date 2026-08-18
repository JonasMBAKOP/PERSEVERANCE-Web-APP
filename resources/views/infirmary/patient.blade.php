@extends('layouts.app')

@section('title', 'Dossier patient - ' . $student->full_name)
@section('page-title', 'Dossier Patient')
@section('page-subtitle', 'Historique des consultations infirmières du patient')

@section('content')
<div class="space-y-6 pb-20">
    <section class="rounded-2xl border border-sky-100 bg-white p-5 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-center gap-4">
                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-sky-50 text-sky-700">
                    <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-lg font-black text-gray-900">{{ $student->full_name }}</h2>
                    <p class="text-sm text-gray-500">Matricule : {{ $student->matricule ?? '—' }}</p>
                </div>
            </div>
            <a href="{{ route('infirmary.patients') }}" class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Retour aux dossiers
            </a>
        </div>
    </section>

    <section class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
        <div class="border-b border-gray-100 p-5">
            <h3 class="text-base font-bold text-gray-900">Historique des consultations</h3>
            <p class="mt-1 text-sm text-gray-500">{{ $visits->total() }} consultation(s) enregistrée(s) pour cet élève.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-bold uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Date / Heure</th>
                        <th class="px-4 py-3">Classe</th>
                        <th class="px-4 py-3">Température</th>
                        <th class="px-4 py-3">Motif</th>
                        <th class="px-4 py-3">Traitement</th>
                        <th class="px-4 py-3">Saisi par</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-gray-700">
                    @forelse($visits as $visit)
                        <tr class="align-top hover:bg-sky-50/30">
                            <td class="whitespace-nowrap px-4 py-3">
                                <p class="font-semibold text-gray-800">{{ $visit->visit_date?->format('d/m/Y') }}</p>
                                <p class="text-xs text-gray-500">{{ substr((string) $visit->visit_time, 0, 5) }}</p>
                            </td>
                            <td class="px-4 py-3">{{ $visit->class_name ?? '—' }}</td>
                            <td class="px-4 py-3">{{ $visit->temperature !== null ? number_format((float) $visit->temperature, 1, ',', ' ') . ' C' : '—' }}</td>
                            <td class="min-w-52 max-w-xs px-4 py-3 leading-5">{{ $visit->visit_reason }}</td>
                            <td class="min-w-52 max-w-xs px-4 py-3 leading-5">{{ $visit->treatment ?: '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3">{{ $visit->recorder_name }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-sm text-gray-400">Aucun historique de consultation pour cet élève.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($visits->hasPages())
            <div class="border-t border-gray-100 px-5 py-4">{{ $visits->links() }}</div>
        @endif
    </section>
</div>
@endsection
