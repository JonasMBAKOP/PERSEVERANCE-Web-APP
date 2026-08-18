@extends('layouts.app')

@section('title', 'Infirmerie')
@section('page-title', 'Infirmerie')
@section('page-subtitle', 'Registre des consultations scolaires')

@section('content')
<div class="space-y-6 px-0 pb-24">
    @if(session('success'))<div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800">{{ session('error') }}</div>@endif

    <section class="flex flex-col justify-between gap-4 rounded-2xl border border-sky-100 bg-white p-5 shadow-sm sm:flex-row sm:items-center">
        <div class="flex items-center gap-4"><div class="flex h-11 w-11 items-center justify-center rounded-xl bg-sky-50 text-sky-700"><svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4m16 0a8 8 0 11-16 0 8 8 0 0116 0z"/></svg></div><div><h2 class="text-base font-bold text-gray-900">Consultations enregistrees</h2><p class="mt-0.5 text-sm text-gray-500">{{ $academicYearLabel }}</p></div></div>
        <div class="flex items-center gap-2"><div class="rounded-lg bg-gray-50 px-4 py-2 text-sm text-gray-600"><span class="font-bold text-gray-900">{{ $visits->total() }}</span> consultation(s)</div><a href="{{ route('infirmary.print', request()->only(['date', 'class_group_id'])) }}" target="_blank" class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2m-2 0H8v4h8v-4z"/></svg>Imprimer</a></div>
    </section>

    <section class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
        <div class="flex flex-col gap-4 border-b border-gray-100 p-5 sm:flex-row sm:items-center sm:justify-between">
            <div><h2 class="text-base font-bold text-gray-900">Liste des patients</h2><p class="mt-1 text-sm text-gray-500">Historique des passages a l infirmerie.</p></div>
            <form method="GET" class="flex flex-wrap items-center gap-2">
                <input type="date" name="date" value="{{ $selectedDate }}" class="rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-sky-500">
                <select name="class_group_id" class="w-full max-w-full rounded-lg border border-gray-200 bg-white px-3 py-2 pr-10 text-sm outline-none focus:border-sky-500 sm:w-72"><option value="">Toutes les classes</option>@foreach($classes as $class)<option value="{{ $class->id }}" {{ $selectedClass === $class->id ? 'selected' : '' }}>{{ $class->full_name }}</option>@endforeach</select>
                <button type="submit" class="rounded-lg border border-sky-200 bg-sky-50 p-2 text-sky-700 hover:bg-sky-100" aria-label="Filtrer"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4h16M7 12h10m-7 8h4"/></svg></button>
            </form>
        </div>
        <div class="overflow-x-auto"><table class="min-w-full divide-y divide-gray-100 text-sm"><thead class="bg-gray-50 text-left text-xs font-bold uppercase tracking-wide text-gray-500"><tr><th class="px-4 py-3">Date / Heure</th><th class="px-4 py-3">Eleve</th><th class="px-4 py-3">Sexe</th><th class="px-4 py-3">Classe / Age</th><th class="px-4 py-3">Parent</th><th class="px-4 py-3">Temperature</th><th class="px-4 py-3">Motif</th><th class="px-4 py-3">Traitement</th><th class="px-4 py-3">Saisi par</th><th class="px-4 py-3 text-right">Actions</th></tr></thead><tbody class="divide-y divide-gray-100 text-gray-700">
            @forelse($visits as $visit)
                <tr class="align-top hover:bg-sky-50/30"><td class="whitespace-nowrap px-4 py-3"><p class="font-semibold text-gray-800">{{ $visit->visit_date?->format('d/m/Y') }}</p><p class="text-xs text-gray-500">{{ substr((string) $visit->visit_time, 0, 5) }}</p></td><td class="px-4 py-3 font-semibold text-gray-900">{{ $visit->student_name }}</td><td class="px-4 py-3">{{ $visit->student_gender === 'F' ? 'F' : ($visit->student_gender === 'M' ? 'M' : '-') }}</td><td class="px-4 py-3"><p>{{ $visit->class_name ?? '-' }}</p><p class="text-xs text-gray-500">{{ $visit->student_age !== null ? $visit->student_age . ' ans' : '-' }}</p></td>@php($parentPhones = collect([$visit->student?->father_phone, $visit->student?->mother_phone, $visit->student?->guardian_phone, $visit->parent_phone])->filter(fn ($phone) => filled($phone))->map(fn ($phone) => trim($phone))->unique()->values())<td class="px-4 py-3">@forelse($parentPhones as $phone)<p class="whitespace-nowrap">{{ $phone }}</p>@empty<span>-</span>@endforelse</td><td class="px-4 py-3">{{ $visit->temperature !== null ? number_format((float) $visit->temperature, 1, ',', ' ') . ' C' : '-' }}</td><td class="min-w-52 max-w-xs px-4 py-3 leading-5">{{ $visit->visit_reason }}</td><td class="min-w-52 max-w-xs px-4 py-3 leading-5">{{ $visit->treatment ?: '-' }}</td><td class="whitespace-nowrap px-4 py-3">{{ $visit->recorder_name }}</td><td class="px-4 py-3"><div class="flex items-center justify-end gap-2">@can('manage-health')<a href="{{ route('infirmary.edit', $visit) }}" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-amber-200 bg-amber-50 text-amber-700 transition hover:bg-amber-100" title="Modifier"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.5-9.5a2.121 2.121 0 113 3L12 16l-4 1 1-4 7.5-7.5z"/></svg></a><form method="POST" action="{{ route('infirmary.destroy', $visit) }}" onsubmit="return confirm('Supprimer cette consultation ?');">@csrf @method('DELETE')<button type="submit" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-rose-200 bg-rose-50 text-rose-700 transition hover:bg-rose-100" title="Supprimer"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M3 7h18m-5 0V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3"/></svg></button></form>@endcan</div></td></tr>
            @empty
                <tr><td colspan="10" class="px-4 py-12 text-center text-sm text-gray-400">Aucune consultation enregistree pour ce perimetre.</td></tr>
            @endforelse
        </tbody></table></div>
        @if($visits->hasPages())<div class="border-t border-gray-100 px-5 py-4">{{ $visits->links() }}</div>@endif
    </section>

    @can('manage-health')
        <a href="{{ route('infirmary.create') }}" class="fixed bottom-6 right-6 z-30 inline-flex h-14 items-center gap-2 rounded-full bg-sky-700 px-5 text-sm font-bold text-white shadow-lg transition hover:bg-sky-800 focus:outline-none focus:ring-4 focus:ring-sky-200" aria-label="Nouvelle consultation"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg><span class="hidden sm:inline">Nouvelle consultation</span></a>
    @endcan
</div>
@endsection
