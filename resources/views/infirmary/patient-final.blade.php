@extends('layouts.app')

@section('title', 'Dossier patient - ' . $student->full_name)
@section('page-title', 'Dossier patient')
@section('page-subtitle', 'Historique des consultations infirmieres du patient')

@section('content')
<div class="space-y-6 pb-20">
    <section class="rounded-2xl border border-sky-100 bg-white p-5 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="text-lg font-black text-gray-900">{{ $student->full_name }}</h2>
                <p class="text-sm text-gray-500">Matricule : {{ $student->matricule ?? '-' }}</p>
            </div>
            <a href="{{ route('infirmary.patients') }}" class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Retour aux dossiers</a>
        </div>
    </section>

    <section class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
        <div class="border-b border-gray-100 p-5">
            <h3 class="text-base font-bold text-gray-900">Historique des consultations</h3>
            <p class="mt-1 text-sm text-gray-500">{{ $visits->total() }} consultation(s) enregistree(s) pour cet eleve.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-[1120px] divide-y divide-gray-100 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-bold uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Date / Heure</th><th class="px-4 py-3">Sexe</th><th class="px-4 py-3">Classe / Age</th><th class="px-4 py-3">Parent</th><th class="px-4 py-3">Temperature</th><th class="px-4 py-3">Motif</th><th class="px-4 py-3">Traitement</th><th class="px-4 py-3">Saisi par</th><th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-gray-700">
                    @forelse($visits as $visit)
                        @php($parentPhones = $student->parentPhoneLines())
                        <tr class="align-top hover:bg-sky-50/30">
                            <td class="whitespace-nowrap px-4 py-3"><p class="font-semibold text-gray-800">{{ $visit->visit_date?->format('d/m/Y') }}</p><p class="text-xs text-gray-500">{{ substr((string) $visit->visit_time, 0, 5) }}</p></td>
                            <td class="px-4 py-3">{{ $student->gender === 'F' ? 'F' : ($student->gender === 'M' ? 'M' : '-') }}</td>
                            <td class="px-4 py-3"><p>{{ $visit->class_name ?? '-' }}</p><p class="text-xs text-gray-500">{{ $visit->student_age !== null ? $visit->student_age . ' ans' : '-' }}</p></td>
                            <td class="px-4 py-3 whitespace-pre-line">@forelse($parentPhones as $phone)<p>{{ $phone }}</p>@empty<span>-</span>@endforelse</td>
                            <td class="px-4 py-3">{{ $visit->temperature !== null ? number_format((float) $visit->temperature, 1, ',', ' ') . ' C' : '-' }}</td>
                            <td class="min-w-52 max-w-xs px-4 py-3 leading-5">{{ $visit->visit_reason }}</td>
                            <td class="min-w-52 max-w-xs px-4 py-3 leading-5">{{ $visit->treatment ?: '-' }}</td>
                            <td class="whitespace-nowrap px-4 py-3">{{ $visit->recorder_name }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-2">@can('manage-health')
                                    <a href="{{ route('infirmary.edit', $visit) }}" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100" title="Modifier" aria-label="Modifier">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.5-9.5a2.121 2.121 0 113 3L12 16l-4 1 1-4 7.5-7.5z"/></svg>
                                    </a>
                                    <form method="POST" action="{{ route('infirmary.destroy', $visit) }}" onsubmit="return confirm('Supprimer cette consultation ?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-rose-200 bg-rose-50 text-rose-700 hover:bg-rose-100" title="Supprimer" aria-label="Supprimer">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M3 7h18m-5 0V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3"/></svg>
                                        </button>
                                    </form>@endcan</div></td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="px-4 py-12 text-center text-sm text-gray-400">Aucun historique de consultation pour cet eleve.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($visits->hasPages())<div class="border-t border-gray-100 px-5 py-4">{{ $visits->links('vendor.pagination.custom') }}</div>@endif
    </section>
</div>
@endsection
