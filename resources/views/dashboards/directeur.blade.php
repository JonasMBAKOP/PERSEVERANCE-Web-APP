@extends('layouts.app')
@section('title', 'Tableau de bord — Direction')
@section('page-title', 'Tableau de bord — Direction')
@section('page-subtitle')Vue d'ensemble de l'établissement — {{ now()->isoFormat('dddd D MMMM YYYY') }}@endsection

@push('styles')
<style>
@keyframes fadeUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
@keyframes barW{from{height:0}to{height:var(--h)}}
.anim-1{animation:fadeUp .4s ease .05s both}.anim-2{animation:fadeUp .4s ease .1s both}
.anim-3{animation:fadeUp .4s ease .15s both}.anim-4{animation:fadeUp .4s ease .2s both}
.anim-5{animation:fadeUp .4s ease .25s both}.anim-6{animation:fadeUp .4s ease .3s both}
.bar-v{animation:barW .7s cubic-bezier(.22,.68,0,1.2) .2s both}

/* Evo bars (vertical animated bars) */
.evo-bar { transition: height .65s cubic-bezier(.22,.68,0,1.2); min-width:3px; }
</style>
@endpush

@section('content')

@if(!$activeYear)
<div class="bg-amber-50 border border-amber-200 rounded-xl p-6 text-center">
    <p class="text-amber-700 font-semibold inline-flex items-center justify-center gap-2">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8v4"/><path d="M12 16h.01"/><path d="M5 20h14a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2z"/></svg>
        Aucune année scolaire active.
    </p>
</div>
@else

{{-- ── ALERTES PRIORITAIRES ───────────────────────────────────────────── --}}
@if($unconfiguredFees > 0 || $debtorsCount > 0 || $disciplinePending > 0)
<div class="flex flex-wrap gap-3 mb-6">
    @if($unconfiguredFees > 0)
    <a href="{{ route('finances.fees-list') }}"
       class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-amber-50 border border-amber-200
              text-amber-700 text-sm font-bold hover:bg-amber-100 transition-colors">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v2"/><path d="M12 15h.01"/><path d="M4 5h16v14H4z"/></svg>
        {{ $unconfiguredFees }} classe(s) sans frais configurés
    </a>
    @endif
    @if($debtorsCount > 0)
    <a href="{{ route('finances.insolvables') }}"
       class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-red-50 border border-red-200
              text-red-700 text-sm font-bold hover:bg-red-100 transition-colors">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8v8"/><path d="M8 12h8"/><path d="M6 4h12v16H6z"/></svg>
        {{ $debtorsCount }} élève(s) débiteur(s)
    </a>
    @endif
    @if($disciplinePending > 0)
    <a href="{{ route('discipline.index') }}"
       class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-purple-50 border border-purple-200
              text-purple-700 text-sm font-bold hover:bg-purple-100 transition-colors">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l7 4v5a7 7 0 0 1-14 0V7z"/><path d="M12 14v7"/></svg>
        {{ $disciplinePending }} incident(s) en attente
    </a>
    @endif
</div>
@endif

<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
    <a href="{{ route('students.create') }}" class="group bg-white rounded-2xl shadow-sm border border-gray-100 p-5 hover:shadow-md transition">
        <div class="flex items-center justify-between mb-4">
            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Nouvelle inscription</p>
            <div class="w-9 h-9 rounded-2xl bg-sky-100 text-sky-700 flex items-center justify-center">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
            </div>
        </div>
        <p class="text-sm text-gray-600">Créer un dossier élève rapidement.</p>
    </a>
    <a href="{{ route('grades.entry.form') }}" class="group bg-white rounded-2xl shadow-sm border border-gray-100 p-5 hover:shadow-md transition">
        <div class="flex items-center justify-between mb-4">
            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Saisie des notes</p>
            <div class="w-9 h-9 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16M4 12h10M4 17h16"/></svg>
            </div>
        </div>
        <p class="text-sm text-gray-600">Accéder à la saisie des notes.</p>
    </a>
    <a href="{{ route('finances.reports') }}" class="group bg-white rounded-2xl shadow-sm border border-gray-100 p-5 hover:shadow-md transition">
        <div class="flex items-center justify-between mb-4">
            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Rapports financiers</p>
            <div class="w-9 h-9 rounded-2xl bg-amber-100 text-amber-700 flex items-center justify-center">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l7 7-7 7-7-7 7-7z"/><path d="M12 9v10"/></svg>
            </div>
        </div>
        <p class="text-sm text-gray-600">Voir l’évolution de la trésorerie.</p>
    </a>
    <a href="{{ route('communication.announcements.index') }}" class="group bg-white rounded-2xl shadow-sm border border-gray-100 p-5 hover:shadow-md transition">
        <div class="flex items-center justify-between mb-4">
            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Annonces</p>
            <div class="w-9 h-9 rounded-2xl bg-slate-100 text-slate-700 flex items-center justify-center">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16v16H4z"/><path d="M8 8h8M8 12h8M8 16h5"/></svg>
            </div>
        </div>
        <p class="text-sm text-gray-600">Publier ou consulter les annonces.</p>
    </a>
</div>

{{-- ── KPI PRINCIPAUX ──────────────────────────────────────────────────── --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="anim-1 bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
        <div class="flex items-center justify-between mb-2">
            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Élèves inscrits</p>
            <div class="w-9 h-9 rounded-2xl bg-sky-100 text-sky-700 flex items-center justify-center">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 12c2.5 0 4-1.5 4-4s-1.5-4-4-4-4 1.5-4 4 1.5 4 4 4z"/><path d="M4 20v-1c0-2.2 1.8-4 4-4h8c2.2 0 4 1.8 4 4v1"/></svg>
            </div>
        </div>
        <p class="text-2xl font-black" style="color:#1A3A6B;">{{ $totalStudents }}</p>
        <p class="text-xs text-gray-400 mt-1">{{ $totalClasses }} classes</p>
    </div>
    <div class="anim-2 bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
        <div class="flex items-center justify-between mb-2">
            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Personnel</p>
            <div class="w-9 h-9 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            </div>
        </div>
        <p class="text-2xl font-black text-green-700">{{ $totalStaff }}</p>
        <p class="text-xs text-gray-400 mt-1">{{ $totalTeachers }} enseignant(s)</p>
    </div>
    <div class="anim-3 bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
        <div class="flex items-center justify-between mb-2">
            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Taux collecte</p>
            <div class="w-9 h-9 rounded-2xl bg-amber-100 text-amber-700 flex items-center justify-center">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 1v22"/><path d="M5 5h14"/><path d="M5 19h14"/></svg>
            </div>
        </div>
        <p class="text-2xl font-black" style="color:{{ $collectionRate>=80?'#1A5C2A':($collectionRate>=50?'#C8A415':'#EF4444') }}">
            {{ $collectionRate }}%
        </p>
        <p class="text-xs text-gray-400 mt-1">{{ number_format($totalCollected) }} / {{ number_format($totalExpected) }} F</p>
    </div>
    <div class="anim-4 bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
        <div class="flex items-center justify-between mb-2">
            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Progression notes</p>
            <div class="w-9 h-9 rounded-2xl bg-blue-100 text-blue-700 flex items-center justify-center">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3 8-8"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h11"/></svg>
            </div>
        </div>
        <p class="text-2xl font-black" style="color:{{ $gradeProgress>=80?'#1A5C2A':($gradeProgress>=50?'#C8A415':'#EF4444') }}">
            {{ $gradeProgress }}%
        </p>
        @if($currentSeq)<p class="text-xs text-gray-400 mt-1">{{ $currentSeq->label }}</p>@endif
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-5">

    {{-- ── Revenus mensuels (graphique) ────────────────────────────────── --}}
    <div class="lg:col-span-2 anim-5 bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
        <div class="flex items-center justify-between mb-5">
            <h3 class="font-black text-sm" style="color:#1A3A6B;">Recettes mensuelles</h3>
            <a href="{{ route('finances.reports') }}" class="text-xs font-bold hover:underline" style="color:#E87722;">
                Rapport complet →
            </a>
        </div>
        @php $maxRev = collect($revenueChart)->max('total') ?: 1; @endphp
        <div id="director-evo-chart-wrap" class="relative">
            <div class="flex items-end gap-1.5 pl-1" style="height:148px;" id="director-evo-chart-bars">
                @foreach($revenueChart as $i => $r)
                @php $pct = round(($r['total'] / $maxRev) * 100); @endphp
                <div class="flex-1 flex flex-col items-center gap-1 group min-w-0"
                     title="{{ $r['label'] }} : {{ number_format($r['total'], 0, ',', ' ') }} FCFA {{ isset($r['count']) ? '('.$r['count'].')' : '' }}">
                    <span class="text-gray-400 font-bold truncate w-full text-center"
                          style="font-size:8.5px; min-height:14px;">
                        @if($r['total'] > 0)
                            @if($r['total'] >= 1000000)
                                {{ number_format($r['total']/1000000, 1) }}M
                            @elseif($r['total'] >= 1000)
                                {{ number_format($r['total']/1000, 0) }}k
                            @else
                                {{ number_format($r['total'], 0) }}
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
                        {{ $r['label'] }}
                    </span>
                </div>
                @endforeach
            </div>
        </div>
        @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const bars = document.querySelectorAll('#director-evo-chart-bars .evo-bar[data-pct]');
                const wrap = document.getElementById('director-evo-chart-bars');
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
        </script>
        @endpush
    </div>

    {{-- ── Effectifs par section ───────────────────────────────────────── --}}
    <div class="anim-6 bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
        <h3 class="font-black text-sm mb-4" style="color:#1A3A6B;">Effectifs par section</h3>
        @php
            $donutTotal = $bySection->sum('count') ?: 1;
            $colors = ['#1D4ED8','orange','#991B1B','#1A5C2A'];
            $start = 0;
            $segments = [];
        @endphp
        @foreach($bySection as $row)
            @php
                $pct = round(($row['count'] / $donutTotal) * 100, 1);
                $end = $start + $pct;
                $segments[] = "{$colors[$loop->index % count($colors)]} {$start}% {$end}%";
                $start = $end;
            @endphp
        @endforeach
        <div class="flex flex-col items-center gap-4">
            <div class="relative">
                <div class="w-56 h-56 rounded-full" style="background:conic-gradient({{ implode(',', $segments) }});"></div>
                <div class="absolute inset-0 flex items-center justify-center">
                    <div class="w-28 h-28 rounded-full bg-white shadow-sm flex flex-col items-center justify-center">
                        <span class="text-2xl font-black text-gray-900">{{ $donutTotal }}</span>
                        <span class="text-xs text-gray-400 uppercase tracking-wide">élèves</span>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-1 gap-3 w-full">
                @foreach($bySection as $row)
                <div class="flex items-center justify-between gap-3 rounded-2xl bg-slate-50 p-3">
                    <div class="flex items-center gap-3">
                        <span class="h-3 w-3 rounded-full" style="background:{{ $colors[$loop->index % count($colors)] }}"></span>
                        <span class="text-sm font-semibold text-gray-700">{{ $row['section']->name }}</span>
                    </div>
                    <span class="text-sm font-black text-gray-900">{{ $row['count'] }} ({{ round(($row['count'] / $donutTotal) * 100) }}%)</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 mb-5">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
        <div>
            <h3 class="font-black text-sm" style="color:#1A3A6B;">Résultats par classe</h3>
            <p class="text-xs text-gray-400">Analyse par période et performance moyenne.</p>
        </div>
        <form method="get" class="flex flex-wrap gap-3 items-center" id="resultsFilterForm">
            <label class="sr-only" for="performance_scope">Période</label>
            <select id="performance_scope" name="performance_scope" class="rounded-xl border-gray-200 text-sm text-gray-700 px-4 py-3 shadow-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                @foreach($performanceScopes as $key => $label)
                <option value="{{ $key }}" @selected($performanceScope === $key)>{{ $label }}</option>
                @endforeach
            </select>

            <select id="sequence_select" name="sequence_target_id" class="rounded-xl border-gray-200 text-sm text-gray-700 px-4 py-3 shadow-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500" @if($performanceScope !== 'sequence') style="display:none;" @endif>
                @foreach($sequences as $seq)
                <option value="{{ $seq->id }}" @selected($performanceTargetId == $seq->id)>{{ $seq->label }}</option>
                @endforeach
            </select>

            <select id="trimester_select" name="trimester_target_id" class="rounded-xl border-gray-200 text-sm text-gray-700 px-4 py-3 shadow-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500" @if($performanceScope !== 'trimestre') style="display:none;" @endif>
                @foreach($trimesters as $trim)
                <option value="{{ $trim->id }}" @selected($performanceTargetId == $trim->id)>{{ $trim->label }}</option>
                @endforeach
            </select>

            <button type="submit" class="rounded-xl bg-sky-600 text-white px-4 py-3 text-sm font-bold hover:bg-sky-700 transition">Actualiser</button>
        </form>

        @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const scopeSelect = document.getElementById('performance_scope');
                const sequenceSelect = document.getElementById('sequence_select');
                const trimesterSelect = document.getElementById('trimester_select');

                function updateFilterVisibility() {
                    const scope = scopeSelect.value;
                    sequenceSelect.style.display = scope === 'sequence' ? 'block' : 'none';
                    trimesterSelect.style.display = scope === 'trimestre' ? 'block' : 'none';
                }

                scopeSelect.addEventListener('change', updateFilterVisibility);
                updateFilterVisibility();
            });
        </script>
        @endpush
    </div>
    <div class="space-y-4">
        @foreach($chartResultsByClass->take(6) as $row)
        <div class="grid grid-cols-12 gap-3 items-center">
            <div class="col-span-4 text-sm font-semibold text-gray-700">{{ $row['class']->full_name }}</div>
            <div class="col-span-5">
                <div class="h-2 rounded-full bg-gray-100 overflow-hidden">
                    <div class="h-full rounded-full" style="width:{{ $row['success_pct'] }}%;background:linear-gradient(90deg,#1D4ED8,#60A5FA);"></div>
                </div>
            </div>
            <div class="col-span-3 text-right text-sm text-gray-500">{{ $row['success_pct'] }}%</div>
        </div>
        @endforeach
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-5">

    {{-- ── Alertes prioritaires ─────────────────────────────────────────── --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="font-black text-sm" style="color:#1A3A6B;">Alertes prioritaires</h3>
                <p class="text-xs text-gray-400 mt-1">Éléments à traiter en priorité.</p>
            </div>
            <div class="w-10 h-10 rounded-2xl bg-amber-100 text-amber-700 flex items-center justify-center">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8v4"/><path d="M12 16h.01"/><path d="M5 20h14a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2z"/></svg>
            </div>
        </div>
        @if($unconfiguredFees + $debtorsCount + $disciplinePending === 0)
        <div class="px-4 py-8 text-center text-sm text-gray-400 italic">Aucune alerte prioritaire.</div>
        @else
        <div class="space-y-3">
            @if($unconfiguredFees > 0)
            <a href="{{ route('finances.fees-list') }}" class="block rounded-2xl border border-amber-100 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-700 hover:bg-amber-100 transition">
                {{ $unconfiguredFees }} classe(s) sans frais configurés
            </a>
            @endif
            @if($debtorsCount > 0)
            <a href="{{ route('finances.insolvables') }}" class="block rounded-2xl border border-red-100 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700 hover:bg-red-100 transition">
                {{ $debtorsCount }} élève(s) débiteur(s)
            </a>
            @endif
            @if($disciplinePending > 0)
            <a href="{{ route('discipline.index') }}" class="block rounded-2xl border border-purple-100 bg-purple-50 px-4 py-3 text-sm font-semibold text-purple-700 hover:bg-purple-100 transition">
                {{ $disciplinePending }} incident(s) en attente
            </a>
            @endif
        </div>
        @endif
    </div>

    {{-- ── Vie scolaire ─────────────────────────────────────────────────── --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
        <h3 class="font-black text-sm mb-4" style="color:#1A3A6B;">Vie scolaire</h3>
        <div class="space-y-3">
            <div class="flex justify-between items-center p-3 rounded-xl bg-red-50">
                <span class="text-sm font-semibold text-red-700">Absences ce mois</span>
                <span class="text-lg font-black text-red-600">{{ $monthAbsenceHours }}h</span>
            </div>
            <div class="flex justify-between items-center p-3 rounded-xl bg-amber-50">
                <span class="text-sm font-semibold text-amber-700">Incidents ce mois</span>
                <span class="text-lg font-black text-amber-600">{{ $disciplineThisMonth }}</span>
            </div>
            <div class="flex justify-between items-center p-3 rounded-xl bg-blue-50">
                <span class="text-sm font-semibold text-blue-700">Élèves débiteurs</span>
                <span class="text-lg font-black text-blue-600">{{ $debtorsCount }}</span>
            </div>
        </div>
    </div>

    {{-- ── Activités récentes ───────────────────────────────────────────── --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="font-black text-sm" style="color:#1A3A6B;">Activités récentes</h3>
                <p class="text-xs text-gray-400 mt-1">Suivi des actions et mises à jour importantes.</p>
            </div>
            <span class="inline-flex items-center gap-2 rounded-full bg-slate-50 px-3 py-1 text-xs font-semibold text-slate-600">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 6v6l4 2"/><circle cx="12" cy="12" r="10"/></svg>
                {{ $recentActivities->count() }} reçues
            </span>
        </div>
        <div class="space-y-3">
            @foreach($recentActivities->take(3) as $activity)
            <div class="rounded-2xl border border-slate-100 bg-slate-50 p-3">
                <p class="text-sm font-semibold text-gray-800 truncate">{{ $activity->message ?? ($activity->raw->action ?? 'Action') }}</p>
                <p class="text-xs text-gray-500 mt-1">{{ $activity->user?->name ?? 'Système' }} · {{ $activity->created_at->diffForHumans() }}</p>
            </div>
            @endforeach
        </div>
    </div>
</div>

<div class="grid grid-cols-1 gap-5 mt-5">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <div>
                <h3 class="font-black text-sm" style="color:#1A3A6B;">Inscriptions récentes</h3>
                <p class="text-xs text-gray-400">Les dernières admissions de l’année en cours.</p>
            </div>
            <span class="text-xs font-bold uppercase text-slate-500">{{ $recentEnrollments->count() }}/8</span>
        </div>
        @if($recentEnrollments->isEmpty())
        <div class="px-5 py-8 text-center text-sm text-gray-400 italic">Aucune inscription récente.</div>
        @else
        <div class="divide-y divide-gray-50">
            @foreach($recentEnrollments as $enrollment)
            <div class="px-5 py-4 flex items-start gap-4">
                <div class="w-10 h-10 rounded-2xl bg-slate-100 text-slate-600 flex items-center justify-center text-sm font-black">{{ strtoupper(substr($enrollment->student->last_name,0,1).substr($enrollment->student->first_name,0,1)) }}</div>
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-gray-800 truncate">{{ $enrollment->student->full_name }}</p>
                    <p class="text-xs text-gray-400">{{ $enrollment->classGroup->full_name }} · {{ $enrollment->created_at->diffForHumans() }}</p>
                    <p class="text-xs text-gray-500 mt-1">Inscrit par {{ $enrollment->enrollmentAudit?->user?->name ?? 'Système' }}</p>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <div>
                <h3 class="font-black text-sm" style="color:#1A3A6B;">Activités récentes</h3>
                <p class="text-xs text-gray-400">Suivi des actions de l’équipe et des mises à jour.</p>
            </div>
            <span class="text-xs font-bold uppercase text-slate-500">{{ $recentActivities->count() }}/6</span>
        </div>
        @if($recentActivities->isEmpty())
        <div class="px-5 py-8 text-center text-sm text-gray-400 italic">Aucune activité enregistrée.</div>
        @else
        <div class="divide-y divide-gray-50">
            @foreach($recentActivities as $activity)
            <div class="px-5 py-4">
                <p class="text-sm text-gray-800 truncate">{{ $activity->message ?? ($activity->raw->action ?? 'Action') }}</p>
                <p class="text-xs text-gray-400">{{ $activity->user?->name ?? 'Système' }} · {{ $activity->created_at->diffForHumans() }}</p>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</div>

@endif
@endsection
