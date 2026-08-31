@extends('layouts.app')

@section('title', 'Bourses')
@section('page-title', 'Gestion des Bourses')
@section('page-subtitle', 'Suivi des aides financières accordées aux élèves')

@push('styles')
@include('finances.partials.finance-ui-styles')
<style>
.type-btn {
    flex: 1 1 120px;
    min-width: 0;
    border: none;
    cursor: pointer;
    white-space: normal;
}
.type-btn.active {
    background: #1A3A6B !important;
    color: #fff !important;
}
.type-btn:hover {
    background: #1A3A6B;
    color: #fff;
}
#filtersForm select { padding-right: 2.5rem; }
</style>
@endpush

@section('content')
<div class="finance-page -mx-2 -mt-2 -mb-2 lg:-mx-4 lg:-mt-4 lg:-mb-4">
@php
    $months = [
        1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
        5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
        9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre',
    ];
    $averageScholarship = $scholarshipCount > 0 ? round($totalScholarships / $scholarshipCount) : 0;
    $cashierName = $cashierId ? ($cashiers->firstWhere('id', (int) $cashierId)?->name ?? '—') : 'Tous';
@endphp

{{-- Contexte période --}}
<div class="fin-panel mb-6 rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex items-start gap-4">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-indigo-50">
                <svg class="h-6 w-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V7m0 9v1m9-5a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <h2 class="mt-2 text-lg font-black" style="color:#1A3A6B;">Registre des bourses accordées</h2>
                <p class="mt-1 max-w-2xl text-sm text-gray-500">
                    Consultez les attributions par période, responsable et classe. Imprimez la liste filtrée pour archivage.
                </p>
            </div>
        </div>
        <div class="rounded-xl border border-gray-100 bg-gray-50 px-4 py-3 text-right">
            <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Responsable filtré</p>
            <p class="mt-1 text-sm font-bold text-gray-700">{{ $cashierName }}</p>
        </div>
    </div>
</div>

{{-- KPI --}}
<div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
    @include('finances.partials.kpi-card', [
        'label' => 'Montant total accordé',
        'value' => number_format($totalScholarships),
        'suffix' => 'FCFA',
        'hint' => 'Somme des bourses du filtre actif',
        'color' => '#1A3A6B',
        'bg' => '#EBF3FB',
        'delay' => '0s',
        'icon' => '<svg class="h-5 w-5" style="color:#1A3A6B;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c0-.552 0-1 0-1m0 9v1m9-5a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
    ])

    @include('finances.partials.kpi-card', [
        'label' => 'Nombre de bourses',
        'value' => number_format($scholarshipCount),
        'hint' => 'Élèves bénéficiaires sur la période',
        'color' => '#1A5C2A',
        'bg' => '#EAF5EA',
        'delay' => '.05s',
        'icon' => '<svg class="h-5 w-5 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1m0-4a4 4 0 100-8 4 4 0 000 8z"/></svg>',
    ])
</div>

{{-- Filtres --}}
<form method="GET" action="{{ route('finances.scholarships') }}"
      id="filtersForm"
      class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 mb-6">

    <div class="grid gap-5 lg:grid-cols-[1fr_auto] items-end">
        <div class="space-y-5">
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">
                    Type de rapport
                </label>
                <div class="flex flex-wrap rounded-2xl overflow-hidden border border-gray-200 bg-white">
                    @php
                        $reportTypes = [
                            'journalier'   => 'Journalier',
                            'hebdomadaire' => 'Hebdomadaire',
                            'mensuel'      => 'Mensuel',
                            'annuel'       => 'Annuel',
                            'entre-2-dates'=> 'Entre 2 dates',
                        ];
                    @endphp
                    @foreach($reportTypes as $val => $lbl)
                    <button type="button"
                            onclick="setType('{{ $val }}')"
                            class="px-3 py-2 text-center text-sm font-bold transition-colors type-btn {{ $type === $val ? 'active' : '' }}"
                            data-type="{{ $val }}"
                            style="{{ $type === $val ? 'background:#1A3A6B;color:#fff;' : 'background:white;color:#6B7280;' }}">
                        {{ $lbl }}
                    </button>
                    @endforeach
                </div>
                <input type="hidden" name="type" id="type-input" value="{{ $type }}">
            </div>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">
                        Année scolaire
                    </label>
                    <select name="year_id"
                            class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none bg-white font-medium"
                            style="color:#1A3A6B;">
                        @foreach($years as $yr)
                        <option value="{{ $yr->id }}"
                                {{ $selectedYear?->id == $yr->id ? 'selected' : '' }}>
                            {{ $yr->label }} {{ $yr->is_active ? '(Active)' : '' }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div id="date-filter" style="display: {{ $type === 'journalier' ? 'block' : 'none' }};">
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Date</label>
                    <input type="date" name="date" value="{{ old('date', $date ?? now()->toDateString()) }}" class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm focus:border-[#1A3A6B] focus:outline-none">
                </div>

                <div id="week-filter" style="display: {{ $type === 'hebdomadaire' ? 'block' : 'none' }};">
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Semaine</label>
                    <input type="week" name="week" value="{{ old('week', $week ?? now()->format('o-\WW')) }}" class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm focus:border-[#1A3A6B] focus:outline-none">
                </div>

                <div id="month-filter" style="display: {{ $type === 'mensuel' ? 'block' : 'none' }};">
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Mois</label>
                    <select name="month"
                            class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none bg-white font-medium"
                            style="color:#1A3A6B;">
                        @foreach([
                            1=>'Janvier', 2=>'Février', 3=>'Mars', 4=>'Avril',
                            5=>'Mai', 6=>'Juin', 7=>'Juillet', 8=>'Août',
                            9=>'Septembre', 10=>'Octobre', 11=>'Novembre', 12=>'Décembre'
                        ] as $num => $name)
                        <option value="{{ $num }}"
                                {{ (int) $month === $num ? 'selected' : '' }}>
                            {{ $name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div id="range-filter" style="display: {{ $type === 'entre-2-dates' ? 'grid' : 'none' }}; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0.75rem;">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Début</label>
                        <input type="date" name="start_date" value="{{ old('start_date', $startDate ?? now()->toDateString()) }}" class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm focus:border-[#1A3A6B] focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Fin</label>
                        <input type="date" name="end_date" value="{{ old('end_date', $endDate ?? now()->toDateString()) }}" class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm focus:border-[#1A3A6B] focus:outline-none">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">
                        Responsable
                    </label>
                    <select name="cashier_id"
                            class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none bg-white font-medium"
                            style="color:#1A3A6B;">
                        <option value="">Tous les responsables</option>
                        @foreach($cashiers as $cashier)
                            <option value="{{ $cashier->id }}" {{ (string) $cashierId === (string) $cashier->id ? 'selected' : '' }}>
                                {{ $cashier->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="flex w-full flex-col gap-3 justify-end lg:w-auto">
            <button type="submit"
                    class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-[#1A3A6B] px-5 py-3 text-sm font-bold text-white transition hover:shadow-md lg:w-auto">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h18M6 8h12M10 12h4M11 16h2"/>
                </svg>
                Appliquer les filtres
            </button>
            <a href="{{ route('finances.scholarships.print', request()->query()) }}"
               target="_blank"
               class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-[#E87722] px-5 py-3 text-sm font-bold text-[#E87722] transition hover:bg-[#FFFBF0] lg:w-auto">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Imprimer
            </a>
        </div>
    </div>
</form>

{{-- Tableau --}}
<section class="fin-panel overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
    <div class="flex flex-col gap-2 border-b border-gray-100 bg-gray-50/80 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h3 class="text-sm font-black" style="color:#1A3A6B;">Liste des élèves boursiers</h3>
            <p class="text-xs text-gray-400">Tri du plus récent au plus ancien selon la date d'accord.</p>
        </div>
        <span class="fin-chip">{{ number_format($scholarships->total()) }} ligne(s)</span>
    </div>

    @if($scholarships->isEmpty())
        <div class="p-6">
            <div class="fin-empty">
                <svg class="mb-3 h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V7m0 9v1"/>
                </svg>
                <p class="text-sm font-bold text-gray-500">Aucune bourse ne correspond aux filtres.</p>
                <p class="mt-1 text-xs text-gray-400">Modifiez la période ou réinitialisez les filtres.</p>
            </div>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="fin-table w-full min-w-[980px] text-left">
                <thead class="border-b border-gray-100 bg-white">
                    <tr>
                        <th class="px-5 py-3">Date accord</th>
                        <th class="px-4 py-3">Élève</th>
                        <th class="px-4 py-3">Classe</th>
                        <th class="px-4 py-3 text-right">Montant</th>
                        <th class="px-4 py-3">Responsable</th>
                        <th class="px-5 py-3">Référence</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($scholarships as $scholarship)
                        <tr class="fin-row">
                            <td class="px-5 py-4 font-bold text-gray-800">
                                {{ $scholarship->payment_date?->format('d/m/Y') ?? '—' }}
                            </td>
                            <td class="px-4 py-4">
                                <p class="font-bold text-gray-800">
                                    {{ $scholarship->studentEnrollment?->student?->full_name ?? '—' }}
                                </p>
                                <p class="text-xs text-gray-400">
                                    {{ $scholarship->studentEnrollment?->student?->matricule ?? '—' }}
                                </p>
                            </td>
                            <td class="px-4 py-4">
                                {{ $scholarship->studentEnrollment?->classGroup?->full_name ?? '—' }}
                            </td>
                            <td class="px-4 py-4 text-right font-black text-indigo-700">
                                {{ number_format($scholarship->scholarship_amount) }} FCFA
                            </td>
                            <td class="px-4 py-4">
                                {{ $scholarship->recordedBy?->name ?? '—' }}
                            </td>
                            <td class="px-5 py-4 text-gray-500">
                                @if($scholarship->receipt_number)
                                    <a href="{{ route('finances.receipt', $scholarship) }}"
                                       target="_blank"
                                       class="cursor-pointer font-semibold text-[#1A3A6B] hover:underline">
                                        {{ $scholarship->reference ?: $scholarship->receipt_number }}
                                    </a>
                                @else
                                    {{ $scholarship->reference ?: '—' }}
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if($scholarships->hasPages())
        <div class="border-t border-gray-100 px-5 py-4">
            {{ $scholarships->links() }}
        </div>
    @endif
</section>
</div>
@endsection

@push('scripts')
<script>
function setType(val) {
    document.getElementById('type-input').value = val;
    document.querySelectorAll('.type-btn').forEach(btn => {
        const active = btn.dataset.type === val;
        btn.style.background = active ? '#1A3A6B' : 'white';
        btn.style.color      = active ? '#fff'    : '#6B7280';
    });

    document.getElementById('date-filter').style.display =
        val === 'journalier' ? 'block' : 'none';
    document.getElementById('week-filter').style.display =
        val === 'hebdomadaire' ? 'block' : 'none';
    document.getElementById('month-filter').style.display =
        val === 'mensuel' ? 'block' : 'none';
    document.getElementById('range-filter').style.display =
        val === 'entre-2-dates' ? 'grid' : 'none';
}
</script>
@endpush
