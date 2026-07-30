@extends('layouts.app')

@section('title', 'Gestion Globale des Frais Scolaires')
@section('page-title', 'Gestion Globale des Frais')
@section('page-subtitle', "Vue d'ensemble de la santé financière de l'établissement")

@push('styles')
@include('finances.partials.finance-ui-styles')
@endpush

@section('content')
@php
    $expected = (float) ($globalStats['expected'] ?? 0);
    $collected = (float) ($globalStats['collected'] ?? 0);
    $remaining = (float) ($globalStats['remaining'] ?? 0);
    $collectionRate = $expected > 0 ? min(100, max(0, (int) round(($collected / $expected) * 100))) : 0;
    $paidRate = min(100, max(0, (int) $paidInFullRate));
    $monthlyTotal = $monthlyData->sum('total');
    $maxMonthValue = $monthlyData->max('total') ?: 1;
    $debtorsCount = (int) ($globalStats['debtors'] ?? $debtors->count());
@endphp

@include('finances.partials.management-header', [
    'active' => 'global',
    'selectedYear' => $selectedYear,
    'years' => $years,
    'actions' => view('finances.partials.header-actions-global', compact('selectedYear'))->render(),
])

{{-- KPI --}}
<div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
    @include('finances.partials.kpi-card', [
        'label' => 'Frais attendus',
        'value' => number_format($expected),
        'suffix' => 'FCFA',
        'hint' => 'Prévisions · ' . number_format($totalEnrolled) . ' élève(s)',
        'color' => '#1A3A6B',
        'bg' => '#EBF3FB',
        'icon' => '<svg class="h-5 w-5" style="color:#1A3A6B;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-9 4h16a2 2 0 002-2V7a2 2 0 00-2-2H4a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>',
    ])

    @include('finances.partials.kpi-card', [
        'label' => 'Frais collectés',
        'value' => number_format($collected),
        'suffix' => 'FCFA',
        'hint' => $collectionRate . '% du prévisionnel',
        'color' => '#1A5C2A',
        'bg' => '#EAF5EA',
        'progress' => $collectionRate,
        'delay' => '.04s',
        'icon' => '<svg class="h-5 w-5 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2z"/></svg>',
    ])

    @include('finances.partials.kpi-card', [
        'label' => 'Bourses accordées',
        'value' => number_format($totalScholarships),
        'suffix' => 'FCFA',
        'color' => '#4F46E5',
        'bg' => '#EEF2FF',
        'delay' => '.08s',
        'icon' => '<svg class="h-5 w-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
        'footer' => '<a href="' . route('finances.scholarships', ['year_id' => $selectedYear?->id]) . '" class="text-xs font-bold text-indigo-600 hover:underline">Voir le détail →</a>',
    ])

    @include('finances.partials.kpi-card', [
        'label' => 'Reste à collecter',
        'value' => number_format($remaining),
        'suffix' => 'FCFA',
        'hint' => $debtorsCount . ' élève(s) débiteur(s)',
        'color' => '#DC2626',
        'bg' => '#FEF2F2',
        'delay' => '.12s',
        'icon' => '<svg class="h-5 w-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>',
    ])

    <div class="fin-kpi rounded-2xl border border-gray-100 bg-white p-5 shadow-sm relative overflow-hidden" style="animation-delay:.16s;">
        <div class="relative flex items-center justify-between gap-4">
            <div>
                <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Élèves à jour</p>
                <p class="mt-2 text-2xl font-black" style="color:#C8A415;">{{ $paidRate }}%</p>
                <p class="mt-1 text-xs text-gray-400">{{ max(0, $totalEnrolled - $debtorsCount) }} / {{ $totalEnrolled }} élèves</p>
            </div>
            <div class="relative h-14 w-14 shrink-0">
                <svg class="h-14 w-14 -rotate-90" viewBox="0 0 56 56">
                    <circle cx="28" cy="28" r="22" stroke="#F3F4F6" stroke-width="6" fill="none"/>
                    <circle cx="28" cy="28" r="22" stroke="#C8A415" stroke-width="6" fill="none" stroke-linecap="round"
                            stroke-dasharray="138.2" stroke-dashoffset="{{ 138.2 - (138.2 * $paidRate) / 100 }}"/>
                </svg>
                <span class="absolute inset-0 flex items-center justify-center text-[10px] font-black" style="color:#9A741B;">{{ $paidRate }}%</span>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
    <div class="space-y-6 xl:col-span-2">
        {{-- Collecte mensuelle --}}
        <div class="fin-panel rounded-2xl border border-gray-100 bg-white p-5 shadow-sm lg:p-6">
            <div class="mb-5 flex flex-col gap-3 border-b border-gray-100 pb-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-sm font-black" style="color:#1A3A6B;">Collecte mensuelle</h3>
                    <p class="mt-0.5 text-xs text-gray-400">Encaissements sur la période scolaire</p>
                </div>
                <div class="rounded-xl px-4 py-2 text-right" style="background:#F8FAFC;">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Total période</p>
                    <p class="text-sm font-black" style="color:#1A3A6B;">{{ number_format($monthlyTotal) }} FCFA</p>
                </div>
            </div>

            @if($monthlyData->isEmpty() || $monthlyTotal == 0)
                <div class="flex min-h-[200px] flex-col items-center justify-center rounded-xl border border-dashed border-gray-200 bg-gray-50">
                    <svg class="mb-2 h-10 w-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    <p class="text-sm font-semibold text-gray-400">Aucune collecte enregistrée sur la période</p>
                </div>
            @else
                <div id="global-chart-bars" class="flex items-end gap-1.5" style="height:168px;">
                    @foreach($monthlyData as $i => $m)
                        @php
                            $pct = $maxMonthValue > 0 ? round(($m->total / $maxMonthValue) * 100) : 0;
                            if ($m->total > 0 && $pct < 3) $pct = 3;
                            $shortValue = $m->total >= 1000000
                                ? number_format($m->total / 1000000, 1) . 'M'
                                : ($m->total >= 1000 ? number_format($m->total / 1000, 0) . 'k' : number_format($m->total, 0));
                        @endphp
                        <div class="group flex min-w-0 flex-1 flex-col items-center gap-1"
                             title="{{ $m->full_label ?? $m->label }} : {{ number_format($m->total) }} FCFA">
                            <span class="w-full truncate text-center text-[8.5px] font-bold text-gray-400" style="min-height:14px;">
                                @if($m->total > 0){{ $shortValue }}@endif
                            </span>
                            <div class="relative w-full flex-1 overflow-hidden rounded-t-lg" style="background:#EBF3FB; min-height:110px;">
                                <div class="fin-bar global-bar absolute bottom-0 left-0 right-0 rounded-t-lg"
                                     data-pct="{{ $pct }}"
                                     data-delay="{{ $i * 120 }}"
                                     style="height:0; background:linear-gradient(to top, #1A3A6B, #3B82F6);"></div>
                            </div>
                            <span class="w-full truncate text-center text-[10px] font-bold uppercase text-gray-500">{{ $m->label }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Collecte par section --}}
        <div class="fin-panel rounded-2xl border border-gray-100 bg-white p-5 shadow-sm lg:p-6">
            <div class="mb-5 border-b border-gray-100 pb-4">
                <h3 class="text-sm font-black" style="color:#1A3A6B;">Collecte par section</h3>
                <p class="mt-0.5 text-xs text-gray-400">Progression par grand pôle pédagogique</p>
            </div>
            <div class="space-y-4">
                @forelse($sectionStats as $secData)
                    @php
                        $rate = $secData->expected > 0
                            ? min(100, max(0, (int) round(($secData->collected / $secData->expected) * 100)))
                            : 0;
                    @endphp
                    <div class="rounded-xl border border-gray-100 bg-gray-50/50 p-4 transition hover:border-gray-200 hover:bg-white">
                        <div class="mb-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="font-bold text-gray-800">{{ $secData->section->name }}</p>
                                <p class="text-xs text-gray-400">Reste : {{ number_format($secData->remaining) }} FCFA</p>
                            </div>
                            <div class="text-left sm:text-right">
                                <p class="text-sm font-black text-gray-700">
                                    {{ number_format($secData->collected) }} / {{ number_format($secData->expected) }} FCFA
                                </p>
                                <p class="text-xs font-bold" style="color:#1A3A6B;">{{ $rate }}%</p>
                            </div>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-gray-100">
                            <div class="fin-progress h-full rounded-full" style="background:#1A3A6B; width:0;" data-width="{{ $rate }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="rounded-xl border border-dashed border-gray-200 py-8 text-center text-sm text-gray-400">
                        Aucune section configurée.
                    </p>
                @endforelse
            </div>
        </div>
    </div>

    <aside class="space-y-6">
        {{-- Tranches --}}
        <div class="fin-panel rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
            <h3 class="border-b border-gray-100 pb-4 text-sm font-black" style="color:#1A3A6B;">Tranches de paiement</h3>
            <div class="mt-4 space-y-4">
                @forelse($installmentStats as $is)
                    @php
                        $rate = $is->expected > 0
                            ? min(100, max(0, (int) round(($is->collected / $is->expected) * 100)))
                            : 0;
                        $tone = $rate >= 70 ? '#1A5C2A' : ($rate >= 40 ? '#C8A415' : '#EF4444');
                    @endphp
                    <div>
                        <div class="mb-1.5 flex items-center justify-between gap-3 text-xs font-bold">
                            <span class="truncate text-gray-700">{{ $is->label }}</span>
                            <span style="color:{{ $tone }};">{{ $rate }}%</span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-gray-100">
                            <div class="fin-progress h-full rounded-full" style="background:{{ $tone }}; width:0;" data-width="{{ $rate }}%"></div>
                        </div>
                        <p class="mt-1 text-[11px] text-gray-400">
                            {{ number_format($is->collected) }} / {{ number_format($is->expected) }} FCFA · {{ $is->payers }} payeur(s)
                        </p>
                    </div>
                @empty
                    <p class="py-6 text-center text-sm text-gray-400">Aucune tranche configurée.</p>
                @endforelse
            </div>
        </div>

        {{-- Paiements récents --}}
        <div class="fin-panel rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between border-b border-gray-100 pb-4">
                <h3 class="text-sm font-black" style="color:#1A3A6B;">Paiements récents</h3>
                <a href="{{ route('finances.payments') }}" class="text-xs font-bold hover:underline" style="color:#1A3A6B;">Tout voir</a>
            </div>
            <div class="mt-4 space-y-3">
                @forelse($recentPayments as $p)
                    <div class="rounded-xl border border-gray-100 bg-gray-50/80 p-3 transition hover:border-gray-200 hover:bg-white">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate text-xs font-bold text-gray-800">{{ $p->studentEnrollment->student->full_name }}</p>
                                <p class="mt-0.5 truncate text-[11px] text-gray-400">
                                    {{ $p->studentEnrollment->classGroup->full_name }} · {{ $p->feeInstallment?->label }}
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="whitespace-nowrap text-xs font-black text-green-600">
                                    +{{ number_format($p->amount_paid + ($p->scholarship_amount ?? 0)) }}
                                </p>
                                <p class="text-[11px] text-gray-400">{{ $p->payment_date->format('d/m') }}</p>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="py-6 text-center text-sm text-gray-400">Aucun paiement récent.</p>
                @endforelse
            </div>
        </div>

        {{-- Bourses récentes --}}
        <div class="fin-panel rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between border-b border-gray-100 pb-4">
                <h3 class="text-sm font-black" style="color:#1A3A6B;">Bourses accordées</h3>
                <a href="{{ route('finances.scholarships', ['year_id' => $selectedYear?->id]) }}"
                   class="text-xs font-bold hover:underline" style="color:#1A3A6B;">Tout voir</a>
            </div>
            <div class="mt-4 space-y-3">
                @forelse($recentScholarships as $scholarship)
                    <div class="rounded-xl border border-indigo-100 bg-indigo-50/40 p-3">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate text-xs font-bold text-gray-800">
                                    {{ $scholarship->studentEnrollment?->student?->full_name ?? '—' }}
                                </p>
                                <p class="mt-0.5 truncate text-[11px] text-gray-500">
                                    {{ $scholarship->studentEnrollment?->classGroup?->full_name ?? '—' }}
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="whitespace-nowrap text-xs font-black text-indigo-700">
                                    {{ number_format($scholarship->scholarship_amount) }}
                                </p>
                                <p class="text-[11px] text-gray-400">{{ $scholarship->payment_date?->format('d/m') }}</p>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="py-6 text-center text-sm text-gray-400">Aucune bourse accordée.</p>
                @endforelse
            </div>
        </div>
    </aside>
</div>

@if($debtors->isNotEmpty())
    <div class="fin-panel mt-6 overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
        <div class="flex flex-col gap-2 border-b border-gray-100 bg-gray-50/80 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="text-sm font-black" style="color:#1A3A6B;">Élèves avec impayés</h3>
                <p class="text-xs text-gray-400">Top 10 des soldes restants les plus élevés</p>
            </div>
            <a href="{{ route('finances.insolvables', ['year_id' => $selectedYear?->id]) }}"
               class="inline-flex items-center gap-2 rounded-lg border border-red-100 bg-white px-3 py-2 text-xs font-bold text-red-600 transition hover:border-red-200 hover:bg-red-50">
                <span>{{ $debtors->count() }} débiteur(s)</span>
                <span>Voir la liste</span>
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[860px] text-left">
                <thead class="border-b border-gray-100 bg-white text-[11px] font-bold uppercase tracking-wider text-gray-400">
                    <tr>
                        <th class="px-5 py-3">Élève</th>
                        <th class="px-4 py-3">Classe</th>
                        <th class="px-4 py-3 text-right">Dû</th>
                        <th class="px-4 py-3 text-right">Payé</th>
                        <th class="px-4 py-3 text-right">Reste</th>
                        <th class="px-5 py-3 text-center no-print">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 text-xs font-semibold text-gray-700">
                    @foreach($debtors->take(10) as $d)
                        <tr class="fin-row transition">
                            <td class="px-5 py-4">
                                <p class="font-bold text-gray-800">{{ $d['enrollment']->student->full_name }}</p>
                                <p class="mt-0.5 text-[11px] text-gray-400">{{ $d['enrollment']->student->matricule }}</p>
                            </td>
                            <td class="px-4 py-4">{{ $d['enrollment']->classGroup->full_name }}</td>
                            <td class="px-4 py-4 text-right font-bold">{{ number_format($d['due']) }} FCFA</td>
                            <td class="px-4 py-4 text-right font-bold text-green-600">{{ number_format($d['paid']) }} FCFA</td>
                            <td class="px-4 py-4 text-right font-bold text-red-600">{{ number_format($d['remaining']) }} FCFA</td>
                            <td class="px-5 py-4 text-center no-print">
                                <a href="{{ route('finances.student', $d['enrollment']) }}"
                                   class="inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-[11px] font-bold transition hover:border-[#1A3A6B]/30 hover:bg-blue-50"
                                   style="color:#1A3A6B;">Voir dossier</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-width]').forEach((el) => {
        const width = el.dataset.width;
        requestAnimationFrame(() => { el.style.width = width; });
    });

    const chart = document.getElementById('global-chart-bars');
    const bars = document.querySelectorAll('.global-bar[data-pct]');
    if (!chart || !bars.length) return;

    const animateBars = () => {
        bars.forEach((bar) => {
            const pct = parseInt(bar.dataset.pct || '0', 10);
            const delay = parseInt(bar.dataset.delay || '0', 10);
            setTimeout(() => { bar.style.height = pct + '%'; }, delay);
        });
    };

    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) return;
                animateBars();
                observer.unobserve(entry.target);
            });
        }, { threshold: 0.2 });
        observer.observe(chart);
    } else {
        animateBars();
    }
});
</script>
@endpush
