@extends('layouts.app')

@section('title', 'Rapports Financiers')
@section('page-title', 'Rapports Financiers')
@section('page-subtitle')
    @if($isAdmin)
        Vue directeur · Analyses complètes
    @else
        Mes rapports · {{ auth()->user()->name }}
    @endif
@endsection

@push('styles')
@include('finances.partials.finance-ui-styles')
<style>
@keyframes fadeUp { from{opacity:0;transform:translateY(16px)} to{opacity:1;transform:translateY(0)} }
@keyframes barScale { from{transform:scaleX(0)} to{transform:scaleX(1)} }
.r-card { animation: fadeUp .4s ease both; }
.bar-h  { min-width:0; transform:none; animation:none; transition:width .85s cubic-bezier(.22,.68,0,1.12); }
.report-track { background:#E5E7EB; box-shadow:inset 0 1px 2px rgba(15,23,42,.08); }
.r-card:nth-child(1){animation-delay:.05s}
.r-card:nth-child(2){animation-delay:.10s}
.r-card:nth-child(3){animation-delay:.15s}
.r-card:nth-child(4){animation-delay:.20s}
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
#filtersForm .type-btn { min-width: 0; }
.report-payments-table { min-width: 980px; }
.report-payments-table th,
.report-payments-table td { display: table-cell !important; white-space: nowrap; }
.report-payments-scroll { width: 100%; overflow-x: auto !important; }
.report-payments-table { width: 980px; min-width: 980px !important; }
.report-payments-pagination button { min-width: 2rem; }
#report-payments-header {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) minmax(0, 1fr);
    align-items: center;
}
#report-payments-header h3 { min-width: 0; }
@media (max-width: 639px) {
    #filtersForm .flex.gap-2 { flex-wrap: wrap; }
    #filtersForm .flex.gap-2 > button,
    #filtersForm .flex.gap-2 > a { width: 100%; }
    #report-payments-header {
        grid-template-columns: minmax(0, 1.1fr) minmax(0, 1.1fr) auto;
        align-items: start;
        column-gap: .25rem;
    }
    #report-payments-header h3 {
        width: 6.5rem;
        max-width: 100%;
        min-width: 0 !important;
        line-height: 1.2;
    }
    #report-payments-header h3 span { display: block; margin-left: 0; }
    #report-payments-header > span { min-width: 0; line-height: 1.2; text-align: center; }
    #report-payments-header > a { grid-column: auto !important; justify-self: end; white-space: nowrap; }
}
</style>
@endpush

@section('content')
<div class="finance-page -mx-2 -mt-2 -mb-2 lg:-mx-4 lg:-mt-4 lg:-mb-4">

{{-- ════════════════════════════════════════════════════════════════════ --}}
{{-- FILTRES                                                               --}}
{{-- ════════════════════════════════════════════════════════════════════ --}}
<form method="GET" action="{{ route('finances.reports') }}"
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

                @if($isAdmin)
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">
                        Responsable
                    </label>
                    <select name="who"
                            class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none bg-white font-medium"
                            style="color:#1A3A6B;">
                        <option value="global"  {{ $whoFilter === 'global'  ? 'selected' : '' }}>
                            Tous les responsables
                        </option>
                        <option value="me"      {{ $whoFilter === 'me'      ? 'selected' : '' }}>
                            Moi ({{ auth()->user()->name }})
                        </option>
                        @foreach($economes as $eco)
                        <option value="econome" {{ $whoFilter === 'econome' ? 'selected' : '' }}>
                            Économe — {{ $eco->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                @endif
            </div>
        </div>

        <div class="flex w-full flex-col gap-3 justify-end lg:w-auto">
            <button type="submit"
                    class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-[#1A3A6B] px-5 py-3 text-sm font-bold text-white transition hover:shadow-md lg:w-auto">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                Générer le rapport
            </button>
            <a href="{{ route('finances.reports.export', request()->query()) }}"
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

{{-- ════════════════════════════════════════════════════════════════════ --}}
{{-- EN-TÊTE DU RAPPORT                                                   --}}
{{-- ════════════════════════════════════════════════════════════════════ --}}
<div class="flex items-center justify-between mb-5">
    <div>
        <h3 class="font-black text-base" style="color:#1A3A6B;">
            Rapport {{ [
                'journalier'   => 'Journalier',
                'hebdomadaire' => 'Hebdomadaire',
                'mensuel'      => 'Mensuel',
                'annuel'       => 'Annuel',
                'entre-2-dates'=> 'Entre 2 dates',
            ][$type] ?? 'Financier' }}
            @if($type === 'journalier')
                — {{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}
            @elseif($type === 'hebdomadaire')
                — Semaine {{ \Carbon\Carbon::parse($week . '-1')->format('W, o') }}
            @elseif($type === 'mensuel')
                — {{ ['Janvier','Février','Mars','Avril','Mai','Juin',
                       'Juillet','Août','Septembre','Octobre','Novembre','Décembre'][$month-1] }}
            @elseif($type === 'entre-2-dates')
                — {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }}
                → {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}
            @endif
            · {{ $selectedYear?->label ?? '—' }}
        </h3>
        <p class="text-xs text-gray-500 mt-0.5">
            @if($whoFilter === 'global') Tous les enregistrements
            @elseif($whoFilter === 'me') Mes enregistrements ({{ auth()->user()->name }})
            @else Enregistrements de l'économe
            @endif
            · Généré le {{ now()->format('d/m/Y à H:i') }}
        </p>
    </div>
    <div class="text-right">
        <p class="text-xs text-gray-400">{{ $allPayments->count() }} paiement(s)</p>
    </div>
</div>

{{-- ════════════════════════════════════════════════════════════════════ --}}
{{-- KPI PRINCIPAUX                                                        --}}
{{-- ════════════════════════════════════════════════════════════════════ --}}
<div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-6">

    {{-- Total --}}
    <div class="r-card bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
        <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Total collecté</p>
        @php $totalWithScholarships = $totalCollected + (int)$allPayments->sum('scholarship_amount'); @endphp
        <p class="text-2xl font-black" style="color:#1A3A6B;" data-count-up="{{ (int) $totalWithScholarships }}" data-count-suffix=" FCFA">
            {{ number_format($totalWithScholarships) }}
            <span class="text-sm font-normal text-gray-400">FCFA</span>
        </p>
    </div>
    
    {{-- Bourses Accordées --}}
    <div class="r-card bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
        <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Bourses Accordées</p>
        @php $scholarships = $allPayments->sum('scholarship_amount'); @endphp
        <p class="text-2xl font-black" style="color:#7C3AED;" data-count-up="{{ (int) $scholarships }}" data-count-suffix=" FCFA">
            {{ number_format($scholarships) }}
            <span class="text-sm font-normal text-gray-400">FCFA</span>
        </p>
    </div>

    {{-- Paiements --}}
    <div class="r-card bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
        <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Paiements</p>
        <p class="text-2xl font-black text-green-600" data-count-up="{{ $allPayments->count() }}">{{ $allPayments->count() }}</p>
        <p class="text-xs text-gray-400 mt-0.5">opérations</p>
    </div>

    {{-- Especes --}}
    <div class="r-card bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
        <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Espèces</p>
        @php $cash = $allPayments->where('payment_method','cash')->sum('amount_paid'); @endphp
        <p class="text-2xl font-black" style="color:#C8A415;" data-count-up="{{ (int) $cash }}" data-count-suffix=" FCFA">
            {{ number_format($cash) }}
            <span class="text-sm font-normal text-gray-400">FCFA</span>
        </p>
    </div>

    {{-- Paiements Mobile --}}
    <div class="r-card bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
        <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Paiements Mobile</p>
        @php $mm = $allPayments->whereIn('payment_method',['orange_money','mtn_momo'])->sum('amount_paid'); @endphp
        <p class="text-2xl font-black" style="color:#7C3AED;" data-count-up="{{ (int) $mm }}" data-count-suffix=" FCFA">
            {{ number_format($mm) }}
            <span class="text-sm font-normal text-gray-400">FCFA</span>
        </p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-5">

    <div class="r-card bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
        <h3 class="font-black text-sm mb-4 pb-2 border-b border-gray-100" style="color:#1A3A6B;">Par tranche de paiement</h3>
        @forelse($byInstallment as $inst)
        @php
            $instPct = min(100, max(0, (int) ($inst['rate'] ?? 0)));
            $instExpected = (int) ($inst['expected'] ?? 0);
            $instTone = $instPct >= 70 ? '#1A5C2A' : ($instPct >= 40 ? '#C8A415' : '#EF4444');
        @endphp
        <div class="mb-4 last:mb-0">
            <div class="flex justify-between text-sm mb-1">
                <span class="font-semibold text-gray-700">{{ $inst['label'] }}</span>
                <div class="text-right">
                    <span class="font-black" style="color:{{ $instTone }};">{{ number_format($inst['total']) }} / {{ number_format($instExpected) }} FCFA</span>
                    <span class="text-xs text-gray-400 ml-1.5">{{ $instPct }}% ({{ $inst['count'] }})</span>
                </div>
            </div>
            <div class="h-2 overflow-hidden rounded-full bg-gray-100 report-track" style="background:#E5E7EB; box-shadow:inset 0 1px 2px rgba(15,23,42,.08);">
                <div class="fin-progress bar-h h-full rounded-full" style="width:{{ $instPct }}%; background:{{ $instTone }};"></div>
            </div>
        </div>
        @empty
        <p class="text-sm text-gray-400 italic">Aucune donnée.</p>
        @endforelse
    </div>

    <div class="r-card bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
        <h3 class="font-black text-sm mb-4 pb-2 border-b border-gray-100" style="color:#1A3A6B;">Par mode de paiement</h3>
        @php
            $byMethodWithScholarships = $byMethod->map(function($m) use ($allPayments) {
                $methodScholarships = $allPayments->where('payment_method', $m['method'])->sum('scholarship_amount');
                $m['total'] = $m['total'] + $methodScholarships;
                return $m;
            });
            $methodTotal = $byMethodWithScholarships->sum('total') ?: 1;
        @endphp
        @forelse($byMethodWithScholarships as $i => $m)
        @php
            $methodPct = $methodTotal > 0 ? min(100, max(0, (int) round(($m['total'] / $methodTotal) * 100))) : 0;
            $methodColor = $methodPct >= 70 ? '#1A5C2A' : ($methodPct >= 40 ? '#C8A415' : '#EF4444');
            $methodTextColor = $methodColor;
        @endphp
        <div class="mb-4 last:mb-0">
            <div class="flex justify-between text-sm mb-1">
                <span class="font-semibold text-gray-700">{{ $m['label'] }}</span>
                <div class="text-right">
                    <span class="font-black" style="color:{{ $methodTextColor }};">{{ number_format($m['total']) }} FCFA</span>
                    <span class="text-xs text-gray-400 ml-1.5">{{ $methodPct }}% ({{ $m['count'] }})</span>
                </div>
            </div>
            <div class="h-2 overflow-hidden rounded-full bg-gray-100 report-track" style="background:#E5E7EB; box-shadow:inset 0 1px 2px rgba(15,23,42,.08);">
                <div class="fin-progress bar-h h-full rounded-full" style="width:{{ $methodPct }}%; background:{{ $methodColor }};"></div>
            </div>
        </div>
        @empty
        <p class="text-sm text-gray-400 italic">Aucune donnée.</p>
        @endforelse
    </div>

</div>
@if($type === 'annuel' && count($evolution))
<div class="r-card bg-white rounded-2xl shadow-sm border border-gray-100 p-5 mb-5">
    <div class="flex flex-wrap items-start justify-between gap-3 mb-5 pb-2 border-b border-gray-100">
        <div>
            <h3 class="font-black text-sm" style="color:#1A3A6B;">
                Évolution mensuelle — {{ $selectedYear?->label }}
            </h3>
            @if($selectedYear)
            <p class="text-xs text-gray-400 mt-0.5">
                {{ $selectedYear->start_date?->locale('fr')->translatedFormat('F Y') }}
                → {{ $selectedYear->end_date?->locale('fr')->translatedFormat('F Y') }}
            </p>
            @endif
        </div>
        @php
            $evoTotal = collect($evolution)->sum('total');
            $evoMax   = collect($evolution)->max('total') ?: 1;
        @endphp
        <div class="text-right">
            <p class="text-xs text-gray-400">Total période</p>
            <p class="text-sm font-black" style="color:#1A3A6B;" data-count-up="{{ (int) $evoTotal }}" data-count-suffix=" FCFA">
                {{ number_format($evoTotal) }} <span class="text-xs font-normal text-gray-400">FCFA</span>
            </p>
        </div>
    </div>

    <div id="evo-chart-wrap" class="relative">
        <div class="flex items-end gap-1.5 pl-1" style="height:148px;" id="evo-chart-bars">
            @foreach($evolution as $i => $evo)
            @php $pct = round(($evo['total'] / $evoMax) * 100); @endphp
            <div class="flex-1 flex flex-col items-center gap-1 group min-w-0"
                 title="{{ $evo['full_label'] ?? $evo['label'] }} : {{ number_format($evo['total']) }} FCFA ({{ $evo['count'] }})">
                <span class="text-gray-400 font-bold truncate w-full text-center"
                      style="font-size:8.5px; min-height:14px;">
                    @if($evo['total'] > 0)
                        @if($evo['total'] >= 1000000)
                            {{ number_format($evo['total']/1000000, 1) }}M
                        @elseif($evo['total'] >= 1000)
                            {{ number_format($evo['total']/1000, 0) }}k
                        @else
                            {{ number_format($evo['total'], 0) }}
                        @endif
                    @endif
                </span>
                <div class="w-full relative rounded-t-lg overflow-hidden flex-1"
                     style="background:#EBF3FB; min-height:100px;">
                    <div class="evo-bar absolute bottom-0 left-0 right-0 rounded-t-lg"
                         data-pct="{{ $pct }}"
                         data-delay="{{ $i * 70 }}"
                         style="height:0;
                                background:linear-gradient(to top,#0B2040,#2D6FD4);
                                transition:height .65s cubic-bezier(.22,.68,0,1.2);">
                    </div>
                </div>
                <span class="text-gray-500 group-hover:text-blue-700 transition-colors truncate w-full text-center"
                      style="font-size:9px; font-weight:700;">
                    {{ $evo['label'] }}
                </span>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endif

{{-- ── Tableau détaillé des paiements ─────────────────────────────────── --}}
<div class="r-card bg-white rounded-2xl shadow-sm border border-gray-100
            overflow-hidden">
    <div id="report-payments-header" class="px-5 py-4 border-b border-gray-100 grid grid-cols-2 lg:grid-cols-3 items-center gap-x-3 gap-y-1">
        <h3 class="font-black text-sm" style="color:#1A3A6B;">
            Détail des paiements
            <span class="text-gray-400 font-normal text-xs ml-1">
                ({{ $allPayments->count() }})
            </span>
        </h3>
        <span class="text-center text-xs text-gray-400 lg:text-center">
            Triés du plus récent au plus ancien
        </span>
        <a href="{{ route('finances.payments', request()->query()) }}"
           class="text-right text-xs font-bold text-[#1A3A6B] hover:underline lg:col-start-3">
            Tout voir <span aria-hidden="true">&#8594;</span>
        </a>
    </div>

    @if($allPayments->isEmpty())
    <div class="px-5 py-10 text-center text-sm text-gray-400 italic">
        Aucun paiement pour la période sélectionnée.
    </div>
    @else
    <div class="report-payments-scroll">
        <table class="report-payments-table w-full">
            <thead>
                <tr style="background:#F8FAFC; border-bottom:1px solid #E5E7EB;">
                    <th class="text-left px-5 py-3 text-xs font-bold
                               text-gray-400 uppercase tracking-wider">
                        Élève
                    </th>
                    <th class="text-left px-4 py-3 text-xs font-bold
                               text-gray-400 uppercase tracking-wider
                               hidden sm:table-cell">
                        Tranche
                    </th>
                    <th class="text-right px-4 py-3 text-xs font-bold
                               text-gray-400 uppercase tracking-wider">
                        Montant
                    </th>
                    <th class="text-left px-4 py-3 text-xs font-bold
                               text-gray-400 uppercase tracking-wider
                               hidden md:table-cell">
                        Mode
                    </th>
                    <th class="text-left px-4 py-3 text-xs font-bold
                               text-gray-400 uppercase tracking-wider
                               hidden lg:table-cell">
                        Date
                    </th>
                    <th class="text-left px-4 py-3 text-xs font-bold
                               text-gray-400 uppercase tracking-wider
                               hidden lg:table-cell">
                        Caissier
                    </th>
                    <th class="text-left px-4 py-3 text-xs font-bold
                               text-gray-400 uppercase tracking-wider
                               hidden xl:table-cell">
                        N° Reçu
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($allPayments as $p)
                <tr class="report-payment-row hover:bg-gray-50/50 transition-colors">
                    <td class="px-5 py-3">
                        <p class="text-sm font-semibold text-gray-800">
                            {{ $p->studentEnrollment?->student?->full_name }}
                        </p>
                        <p class="text-xs text-gray-400">
                            {{ $p->studentEnrollment?->classGroup?->full_name }}
                        </p>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600
                               hidden sm:table-cell">
                        {{ $p->is_bulk ? 'Paiement groupé' : ($p->feeInstallment?->label ?? '—') }}
                    </td>
                    <td class="px-4 py-3 text-right">
                        <span class="text-sm font-black text-green-600">
                            {{ number_format($p->amount_paid + $p->scholarship_amount) }}
                        </span>
                        <span class="text-xs text-gray-400">F</span>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600
                               hidden md:table-cell">
                        {{ $p->payment_method_label }}
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600
                               hidden lg:table-cell">
                        {{ $p->payment_date->format('d/m/Y') }}
                        {{ $p->created_at->format('H:i') }}
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600
                               hidden lg:table-cell">
                        {{ $p->recordedBy?->name ?? '—' }}
                    </td>
                    <td class="px-4 py-3 hidden xl:table-cell">
                        <a href="{{ route('finances.receipt', $p) }}" target="_blank"
                           class="font-mono text-xs font-bold hover:underline"
                           style="color:#1A3A6B;">
                            {{ $p->receipt_number }}
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="background:#F8FAFC; border-top:2px solid #E5E7EB;">
                    <td class="px-5 py-3 text-sm font-black uppercase text-gray-500"
                        colspan="2">
                        TOTAL
                    </td>
                    <td class="px-4 py-3 text-right">
                        <span class="text-base font-black" style="color:#1A3A6B;">
                            {{ number_format($totalCollected + (int)$allPayments->sum('scholarship_amount')) }}
                            <span class="text-xs font-normal text-gray-400">FCFA</span>
                        </span>
                    </td>
                    <td colspan="4"></td>
                </tr>
            </tfoot>
        </table>
    </div>
    <div class="report-payments-pagination px-5 py-4 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-4"
         id="report-payments-pagination" data-page-size="30"></div>
    @endif
</div>

</div>
@endsection

@push('scripts')
<script>
// Type toggle
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

// Barres évolution mensuelle
document.addEventListener('DOMContentLoaded', () => {
    const bars = document.querySelectorAll('.evo-bar[data-pct]');
    const wrap = document.getElementById('evo-chart-bars');
    if (!bars.length || !wrap) return;

    const io = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (!entry.isIntersecting) return;
            bars.forEach(bar => {
                const pct   = parseInt(bar.dataset.pct) || 0;
                const delay = parseInt(bar.dataset.delay) || 0;
                setTimeout(() => { bar.style.height = pct + '%'; }, delay);
            });
            io.unobserve(entry.target);
        });
    }, { threshold: 0.25 });

    io.observe(wrap);
});

document.addEventListener('DOMContentLoaded', () => {
    const rows = Array.from(document.querySelectorAll('.report-payment-row'));
    const pagination = document.getElementById('report-payments-pagination');
    if (!pagination || !rows.length) return;

    const pageSize = Number(pagination.dataset.pageSize) || 30;
    const pageCount = Math.ceil(rows.length / pageSize);
    let currentPage = 1;

    const render = () => {
        rows.forEach((row, index) => {
            row.style.display = index >= (currentPage - 1) * pageSize && index < currentPage * pageSize ? '' : 'none';
        });

        pagination.replaceChildren();

        const start = (currentPage - 1) * pageSize + 1;
        const end = Math.min(currentPage * pageSize, rows.length);
        const summary = document.createElement('span');
        summary.textContent = `Affichage ${start}-${end} sur ${rows.length} paiements`;
        summary.className = 'text-sm text-gray-500 font-medium';
        pagination.appendChild(summary);

        const nav = document.createElement('nav');
        nav.setAttribute('aria-label', 'Pagination des paiements');
        nav.className = 'flex items-center gap-1.5';
        pagination.appendChild(nav);

        const addButton = (label, page, disabled = false) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.disabled = disabled;
            button.className = 'inline-flex items-center justify-center w-8 h-8 rounded-full border border-gray-200 text-gray-500 transition-colors hover:bg-gray-50 hover:text-gray-700 disabled:text-gray-300 disabled:cursor-not-allowed';
            button.setAttribute('aria-label', label);
            button.innerHTML = label === 'Précédent'
                ? '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>'
                : '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>';
            if (!disabled) button.addEventListener('click', () => { currentPage = page; render(); });
            nav.appendChild(button);
        };

        addButton('Précédent', currentPage - 1, currentPage === 1);
        for (let page = 1; page <= pageCount; page++) {
            const button = document.createElement('button');
            button.type = 'button';
            button.textContent = page;
            button.className = `inline-flex items-center justify-center w-8 h-8 rounded-full text-sm transition-colors ${page === currentPage ? 'bg-[#9c4005] text-white font-semibold' : 'border border-gray-200 text-gray-600 hover:bg-gray-50 hover:text-gray-800 font-medium'}`;
            button.setAttribute('aria-label', `Aller à la page ${page}`);
            if (page === currentPage) button.setAttribute('aria-current', 'page');
            button.addEventListener('click', () => { currentPage = page; render(); });
            nav.appendChild(button);
        }
        addButton('Suivant', currentPage + 1, currentPage === pageCount);
    };

    render();
});
</script>
@endpush
