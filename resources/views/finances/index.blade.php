@extends('layouts.app')

@section('title', 'Finances')
@section('page-title', 'Finances')
@section('page-subtitle', 'Gestion des frais scolaires et paiements')

@section('content')

{{-- ── SÉLECTEUR D'ANNÉE ───────────────────────────────────────────────── --}}
<div class="flex flex-col sm:flex-row sm:items-center
            justify-between gap-4 mb-6">
    <form method="GET" action="{{ route('finances.index') }}"
          class="flex flex-wrap items-center gap-2">
        <div class="flex items-center gap-2">
            <label class="text-sm text-gray-500">Année :</label>
            <select name="year_id" onchange="this.form.submit()"
                    class="w-[13rem] min-w-[13rem] px-3 py-2 pr-10 border border-gray-200 rounded-lg text-sm
                           focus:outline-none bg-white"
                    style="color:#1A3A6B;">
                @foreach($years as $year)
                <option value="{{ $year->id }}"
                        {{ $selectedYear?->id == $year->id ? 'selected' : '' }}>
                    {{ $year->label }}
                    {{ $year->is_active ? '(Active)' : '' }}
                </option>
                @endforeach
            </select>
        </div>

        <div class="flex items-center gap-2">
            <label class="text-sm text-gray-500">Section :</label>
            <select name="section_id" onchange="this.form.submit()"
                    class="w-[14rem] min-w-[14rem] px-3 py-2 pr-10 border border-gray-200 rounded-lg text-sm
                           focus:outline-none bg-white"
                    style="color:#1A3A6B;">
                <option value="">Toutes les sections</option>
                @foreach($sections as $section)
                <option value="{{ $section->id }}"
                        {{ (string)($selectedSectionId ?? '') === (string)$section->id ? 'selected' : '' }}>
                    {{ $section->name }}
                </option>
                @endforeach
            </select>
        </div>
    </form>

    <a href="{{ route('finances.payments') }}"
       class="flex items-center gap-2 px-4 py-2 rounded-xl border
              text-sm font-medium text-gray-600 border-gray-200
              hover:bg-gray-50 transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor"
             viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round"
                  stroke-width="2"
                  d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2
                     2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0
                     012-2h2a2 2 0 012 2"/>
        </svg>
        Tous les paiements
    </a>
</div>

@if(auth()->user()->hasAnyRole(['super-admin', 'directeur', 'fondateur']))
{{-- ── STATISTIQUES ─────────────────────────────────────────────────────── --}}
<div class="grid grid-cols-1 md:grid-cols-3 xl:grid-cols-5 gap-4 mb-6">

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">
            Attendu
        </p>
        <p class="text-xl font-black" style="color:#1A3A6B;">
            {{ number_format($stats['expected']) }}
            <span class="text-sm font-normal text-gray-400">FCFA</span>
        </p>
        <p class="text-xs text-gray-400 mt-0.5">
            {{ $stats['students'] }} élève(s)
        </p>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">
            Collecté
        </p>
        <p class="text-xl font-black text-green-600">
            {{ number_format($stats['collected']) }}
            <span class="text-sm font-normal text-gray-400">FCFA</span>
        </p>
        <div class="flex items-center gap-2 mt-1">
            <div class="flex-1 h-1.5 bg-gray-100 rounded-full">
                <div class="h-full rounded-full bg-green-500"
                     style="width:{{ $stats['rate'] }}%"></div>
            </div>
            <span class="text-xs text-green-600 font-semibold">
                {{ $stats['rate'] }}%
            </span>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">
            Bourses accordées
        </p>
        <p class="text-xl font-black text-indigo-600">
            {{ number_format($stats['scholarships'] ?? 0) }}
            <span class="text-sm font-normal text-gray-400">FCFA</span>
        </p>
        <p class="text-xs text-gray-400 mt-0.5">
            Réductions sur frais scolaires
        </p>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">
            Restant dû
        </p>
        <p class="text-xl font-black text-red-500">
            {{ number_format($stats['outstanding']) }}
            <span class="text-sm font-normal text-gray-400">FCFA</span>
        </p>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">
            Taux collecte
        </p>
        <p class="text-xl font-black"
           style="color:{{ $stats['rate'] >= 80
               ? '#1A5C2A' : ($stats['rate'] >= 50 ? '#C8A415' : '#EF4444') }}">
            {{ $stats['rate'] }}%
        </p>
    </div>

</div>
@endif

{{-- ── CLASSES — ÉTAT FINANCIER ────────────────────────────────────────── --}}
@if($classes->isEmpty())
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-12
            text-center text-gray-400">
    <p class="text-sm">Aucune classe trouvée pour cette année.</p>
</div>
@else

{{-- Groupées par section --}}
@foreach($classes->groupBy('level.section.name') as $sectionName => $sectionClasses)
<div class="mb-6">
    <h3 class="text-sm font-semibold uppercase tracking-wider mb-3"
        style="color:#1A3A6B;">
        {{ $sectionName }}
    </h3>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100
                overflow-hidden">
        <div class="overflow-x-auto overscroll-x-contain">
        <table class="w-full min-w-[760px]">
            <thead>
                <tr style="background-color:#F8FAFC;"
                    class="border-b border-gray-100">
                    <th class="text-left px-5 py-3 text-xs font-semibold
                               text-gray-400 uppercase tracking-wider">
                        Classe
                    </th>
                    <th class="text-right px-4 py-3 text-xs font-semibold
                               text-gray-400 uppercase tracking-wider
                               hidden sm:table-cell">
                        Élèves
                    </th>
                    <th class="text-right px-4 py-3 text-xs font-semibold
                               text-gray-400 uppercase tracking-wider
                               hidden md:table-cell">
                        Frais totaux
                    </th>
                    <th class="text-right px-4 py-3 text-xs font-semibold
                               text-gray-400 uppercase tracking-wider">
                        Collecté
                    </th>
                    <th class="text-center px-4 py-3 text-xs font-semibold
                               text-gray-400 uppercase tracking-wider">
                        Taux
                    </th>
                    <th class="text-right px-5 py-3 text-xs font-semibold
                               text-gray-400 uppercase tracking-wider">
                        Actions
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($sectionClasses as $class)
                @php
                    $fee        = $class->feeStructures->first();
                    $enrolled   = $class->studentEnrollments->count();
                    $feeTotal   = $fee?->installments->sum('amount') ?? 0;
                    $expected   = $feeTotal * $enrolled;
                    $collected  = \App\Models\StudentPayment::visible()->whereHas(
                        'studentEnrollment', fn($q) =>
                            $q->where('class_group_id', $class->id)
                              ->where('status', 'active')
                    )->sum(\Illuminate\Support\Facades\DB::raw('amount_paid + COALESCE(scholarship_amount, 0)'));
                    $scholarships = \App\Models\StudentPayment::visible()->whereHas(
                        'studentEnrollment', fn($q) =>
                            $q->where('class_group_id', $class->id)
                              ->where('status', 'active')
                    )->sum('scholarship_amount');
                    $effectiveCollected = $collected;
                    $rate = $expected > 0
                        ? round(($effectiveCollected / $expected) * 100) : 0;
                @endphp
                <tr class="hover:bg-gray-50/50 transition-colors">
                    <td class="px-5 py-4">
                        <div>
                            <p class="text-sm font-semibold text-gray-800">
                                {{ $class->full_name }}
                            </p>
                            @if(!$fee)
                            <span class="text-xs text-amber-600">
                                <svg class="inline h-4 w-4 mr-1 align-[-2px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>Frais non configurés
                            </span>
                            @endif
                        </div>
                    </td>
                    <td class="px-4 py-4 text-right text-sm text-gray-600
                               hidden sm:table-cell">
                        {{ $enrolled }}
                    </td>
                    <td class="px-4 py-4 text-right text-sm text-gray-600
                               hidden md:table-cell">
                        @if($fee)
                        {{ number_format($feeTotal) }} FCFA
                        @else
                        <span class="text-gray-300">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-4 text-right">
                        <p class="text-sm font-semibold text-green-600">
                            {{ number_format($effectiveCollected) }}
                            <span class="text-xs font-normal text-gray-400">
                                FCFA
                            </span>
                        </p>
                        @if($expected > 0 && $effectiveCollected < $expected)
                        <p class="text-xs text-red-500">
                            -{{ number_format($expected - $effectiveCollected) }}
                        </p>
                        @endif
                    </td>
                    <td class="px-4 py-4 text-center">
                        @if($expected > 0)
                        <div class="flex flex-col items-center gap-1">
                            <span class="text-sm font-bold
                                {{ $rate >= 80 ? 'text-green-600'
                                    : ($rate >= 50 ? 'text-amber-600'
                                    : 'text-red-500') }}">
                                {{ $rate }}%
                            </span>
                            <div class="w-16 h-1.5 bg-gray-100 rounded-full
                                        overflow-hidden">
                                <div class="h-full rounded-full"
                                     style="width:{{ $rate }}%;
                                            background-color:
                                            {{ $rate >= 80 ? '#1A5C2A'
                                               : ($rate >= 50 ? '#C8A415'
                                               : '#EF4444') }}">
                                </div>
                            </div>
                        </div>
                        @else
                        <span class="text-gray-300 text-sm">—</span>
                        @endif
                    </td>
                    <td class="px-5 py-4 text-right">
                        <div class="flex items-center justify-end gap-1">
                            {{-- Voir les comptes élèves --}}
                            <a href="{{ route('finances.class-students', $class) }}"
                            class="p-1.5 rounded-lg text-gray-400
                                    hover:text-green-600 hover:bg-green-50
                                    transition-colors"
                            title="Gérer les paiements">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0
                                            002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2
                                            2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2
                                            0 014 0z"/>
                                </svg>
                            </a>

                            {{-- Configurer les frais --}}
                            @can('configure-fees')
                            @if(!$selectedYear?->isClosed())
                            <a href="{{ route('finances.fees', $class) }}"
                               class="p-1.5 rounded-lg text-gray-400
                                      hover:text-blue-600 hover:bg-blue-50
                                      transition-colors"
                               title="Configurer les frais">
                                <svg class="w-4 h-4" fill="none"
                                     stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round"
                                          stroke-linejoin="round"
                                          stroke-width="2"
                                          d="M10.325 4.317c.426-1.756 2.924-1.756
                                             3.35 0a1.724 1.724 0 002.573 1.066c1.543
                                             -.94 3.31.826 2.37 2.37a1.724 1.724 0
                                             001.065 2.572c1.756.426 1.756 2.924 0
                                             3.35a1.724 1.724 0 00-1.066 2.573c.94
                                             1.543-.826 3.31-2.37 2.37a1.724 1.724
                                             0 00-2.572 1.065c-.426 1.756-2.924
                                             1.756-3.35 0a1.724 1.724 0 00-2.573
                                             -1.066c-1.543.94-3.31-.826-2.37-2.37a1.724
                                             1.724 0 00-1.065-2.572c-1.756-.426-1.756
                                             -2.924 0-3.35a1.724 1.724 0 001.066
                                             -2.573c-.94-1.543.826-3.31 2.37-2.37.996
                                             .608 2.296.07 2.572-1.065z"/>
                                    <path stroke-linecap="round"
                                          stroke-linejoin="round"
                                          stroke-width="2"
                                          d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </a>
                            @endif
                            @endcan
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        </div>
    </div>
</div>
@endforeach

{{-- ── PAIEMENTS RÉCENTS ────────────────────────────────────────────────── --}}
@if($recentPayments->isNotEmpty())
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="px-5 py-4 border-b border-gray-100 flex items-center
                justify-between">
        <h3 class="font-semibold text-sm" style="color:#1A3A6B;">
            Paiements récents
        </h3>
        <a href="{{ route('finances.payments') }}"
           class="text-xs text-gray-400 hover:underline">
            Voir tous →
        </a>
    </div>
    <div class="divide-y divide-gray-50">
        @foreach($recentPayments as $p)
        <div class="px-5 py-3 flex items-center justify-between gap-4">
            <div class="flex items-center gap-3 min-w-0">
                <div class="w-8 h-8 rounded-full flex items-center
                            justify-center text-white text-xs font-bold
                            flex-shrink-0"
                     style="background-color:#1A3A6B;">
                    {{ strtoupper(substr(
                        $p->studentEnrollment?->student?->last_name ?? '?', 0, 1))
                    }}
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-medium text-gray-800 truncate">
                        {{ $p->studentEnrollment?->student?->full_name }}
                    </p>
                    <p class="text-xs text-gray-400">
                        {{ $p->studentEnrollment?->classGroup?->full_name }}
                        · {{ $p->is_bulk ? 'Paiement en bloc' : ($p->feeInstallment?->label ?? '—') }}
                    </p>
                </div>
            </div>
            <div class="text-right flex-shrink-0">
                <p class="text-sm font-bold text-green-600">
                    +{{ number_format($p->effective_amount_paid) }}
                    <span class="text-xs font-normal text-gray-400">FCFA</span>
                </p>
                <p class="text-xs text-gray-400">
                    {{ $p->payment_date->format('d/m/Y') }}
                </p>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif

@endif

@endsection
