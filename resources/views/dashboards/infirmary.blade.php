@extends('layouts.app')

@section('title', 'Tableau de bord infirmerie')
@section('page-title', 'Tableau de bord')
@section('page-subtitle', 'Pilotage quotidien de l infirmerie scolaire')

@section('content')
<div class="space-y-6 pb-12">
    <section class="relative overflow-hidden rounded-2xl bg-slate-900 px-6 py-7 text-white shadow-sm sm:px-8">
        <div class="relative z-10 max-w-2xl">
            <p class="mb-2 text-xs font-bold uppercase tracking-[0.2em] text-teal-300">Espace santé</p>
            <h1 class="text-2xl font-bold tracking-tight sm:text-3xl">Bonjour, {{ auth()->user()->name }}</h1>
            <p class="mt-2 text-sm leading-6 text-slate-300">Retrouvez rapidement l activité de l infirmerie et les derniers passages enregistrés.</p>
            <div class="mt-5 flex flex-wrap gap-3">
                @can('manage-health')
                    <a href="{{ route('infirmary.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-teal-500 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-teal-400">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14m-7-7h14"/></svg>
                        Nouvelle consultation
                    </a>
                @endcan
                <a href="{{ route('infirmary.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-600 px-4 py-2.5 text-sm font-semibold text-slate-100 transition hover:bg-slate-800">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H5v4m0-4 5 5m5-5h4v4m0-4-5 5M9 19H5v-4m0 4 5-5m5 5h4v-4m0 4-5-5"/></svg>
                    Voir le registre
                </a>
            </div>
        </div>
        <div class="pointer-events-none absolute -right-8 -top-12 h-56 w-56 rounded-full border-[28px] border-teal-400/10"></div>
        <div class="pointer-events-none absolute -bottom-20 right-20 h-48 w-48 rounded-full border-[22px] border-sky-400/10"></div>
    </section>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach([
            ['label' => 'Passages aujourd hui', 'value' => $todayVisits, 'hint' => 'Activité du jour', 'color' => 'teal'],
            ['label' => 'Passages ce mois', 'value' => $monthVisits, 'hint' => 'Depuis le début du mois', 'color' => 'sky'],
            ['label' => 'Passages de l année', 'value' => $yearVisits, 'hint' => $academicYearLabel, 'color' => 'indigo'],
            ['label' => 'Élèves suivis', 'value' => $studentsSeen, 'hint' => 'Élèves distincts', 'color' => 'amber'],
        ] as $stat)
            <article class="rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <p class="text-sm font-semibold text-gray-500">{{ $stat['label'] }}</p>
                    <span class="h-2.5 w-2.5 rounded-full" style="background-color: {{ ['teal' => '#14b8a6', 'sky' => '#0ea5e9', 'indigo' => '#6366f1', 'amber' => '#f59e0b'][$stat['color']] }}"></span>
                </div>
                <p class="mt-4 text-3xl font-bold text-gray-900">{{ number_format($stat['value'], 0, ',', ' ') }}</p>
                <p class="mt-1 text-xs text-gray-400">{{ $stat['hint'] }}</p>
            </article>
        @endforeach
    </div>

    <section class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
        <div class="flex flex-col gap-3 border-b border-gray-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-base font-bold text-gray-900">Derniers passages</h2>
                <p class="mt-1 text-sm text-gray-500">Les consultations les plus récemment enregistrées.</p>
            </div>
            <a href="{{ route('infirmary.index') }}" class="text-sm font-bold text-teal-700 hover:text-teal-800">Ouvrir le registre</a>
        </div>
        <div class="divide-y divide-gray-100">
            @forelse($recentVisits as $visit)
                <div class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex min-w-0 items-center gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-teal-50 text-sm font-bold text-teal-700">{{ strtoupper(substr((string) $visit->student_name, 0, 1)) }}</div>
                        <div class="min-w-0">
                            <p class="truncate font-semibold text-gray-900">{{ $visit->student_name }}</p>
                            <p class="mt-0.5 truncate text-xs text-gray-500">{{ $visit->class_name ?: 'Classe non renseignée' }} · {{ $visit->visit_reason }}</p>
                        </div>
                    </div>
                    <div class="flex shrink-0 items-center gap-4 text-right text-xs text-gray-500">
                        <span>{{ $visit->visit_date?->format('d/m/Y') }}</span>
                        <span class="font-semibold text-gray-700">{{ substr((string) $visit->visit_time, 0, 5) }}</span>
                    </div>
                </div>
            @empty
                <div class="px-5 py-12 text-center text-sm text-gray-400">Aucune consultation enregistrée pour le moment.</div>
            @endforelse
        </div>
    </section>
</div>
@endsection
