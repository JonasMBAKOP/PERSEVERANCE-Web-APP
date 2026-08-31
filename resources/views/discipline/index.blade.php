@extends('layouts.app')
@section('title', 'Discipline')
@section('page-title', 'Discipline')
@section('page-subtitle', 'Suivi des incidents disciplinaires')

@push('styles')
<style>
.discipline-filter select {
    min-width: 11rem;
    padding-right: 2.75rem;
}
.discipline-filter .discipline-search { min-width: 14rem; }
.discipline-table-scroll { width: 100%; overflow-x: auto; }
.discipline-table { min-width: 1120px; }
@media (min-width: 1024px) {
    .discipline-table { min-width: 0; table-layout: fixed; }
    .discipline-table th:nth-child(1), .discipline-table td:nth-child(1) { width: 3%; }
    .discipline-table th:nth-child(2), .discipline-table td:nth-child(2) { width: 12%; }
    .discipline-table th:nth-child(3), .discipline-table td:nth-child(3) { width: 33%; }
    .discipline-table th:nth-child(4), .discipline-table td:nth-child(4) { width: 16%; }
    .discipline-table th:nth-child(5), .discipline-table td:nth-child(5) { width: 14%; }
    .discipline-table th:nth-child(6), .discipline-table td:nth-child(6) { width: 10%; }
    .discipline-table th:nth-child(7), .discipline-table td:nth-child(7) { width: 12%; }
    .discipline-table th,
    .discipline-table td { overflow-wrap: anywhere; }
    .discipline-table-scroll { overflow-x: hidden; }
}
@media (max-width: 639px) {
    .discipline-filter > * { width: 100%; min-width: 0; }
    .discipline-filter select,
    .discipline-filter .discipline-search { width: 100%; min-width: 0; }
}
</style>
@endpush

@section('content')

<div class="finance-page -mx-2 -mt-2 -mb-2 lg:-mx-4 lg:-mt-4 lg:-mb-4">

{{-- ── STATS ───────────────────────────────────────────────────────────── --}}
<div class="grid grid-cols-2 sm:grid-cols-5 gap-4 mb-6">
    @foreach([
        ['label'=>'Total',       'value'=>$stats['total'],       'color'=>'#1A3A6B','bg'=>'#EBF3FB'],
        ['label'=>'En attente',  'value'=>$stats['pending'],     'color'=>'#C8A415','bg'=>'#FBF5E6'],
        ['label'=>'Résolus',     'value'=>$stats['resolved'],    'color'=>'#1A5C2A','bg'=>'#EAF5EA'],
        ['label'=>'Renvois',     'value'=>$stats['suspensions'], 'color'=>'#E87722','bg'=>'#FEF3EA'],
        ['label'=>'Exclusions',  'value'=>$stats['exclusions'],  'color'=>'#EF4444','bg'=>'#FEE2E2'],
    ] as $s)
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <p class="text-2xl font-black" style="color:{{ $s['color'] }}">
            {{ $s['value'] }}
        </p>
        <p class="text-xs text-gray-400 uppercase tracking-wider mt-0.5">
            {{ $s['label'] }}
        </p>
    </div>
    @endforeach
</div>

{{-- ── FILTRES ──────────────────────────────────────────────────────────── --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-4">
    <form method="GET" action="{{ route('discipline.index') }}"
          class="discipline-filter flex flex-wrap gap-3 items-end">
        <div class="discipline-search relative flex-1 min-w-40">
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Rechercher un élève..."
                   class="w-full pl-4 pr-10 py-2 border border-gray-200
                          rounded-lg text-sm focus:outline-none">
            <button type="submit" aria-label="Lancer la recherche"
                    class="absolute inset-y-0 right-2 flex items-center text-gray-400 hover:text-[#1A3A6B]">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </button>
        </div>
        <select name="class_id" class="px-3 py-2 border border-gray-200 rounded-lg text-sm bg-white">
            <option value="">Toutes les classes</option>
            @foreach($classes as $c)
            <option value="{{ $c->id }}" {{ request('class_id')==$c->id?'selected':'' }}>
                {{ $c->full_name }}
            </option>
            @endforeach
        </select>
        <select name="sanction" class="px-3 py-2 border border-gray-200 rounded-lg text-sm bg-white">
            <option value="">Toutes sanctions</option>
            @foreach(\App\Models\DisciplineIncident::SANCTIONS as $v => $l)
            <option value="{{ $v }}" {{ request('sanction')==$v?'selected':'' }}>{{ $l }}</option>
            @endforeach
        </select>
        <select name="status" class="px-3 py-2 border border-gray-200 rounded-lg text-sm bg-white">
            <option value="">Tous statuts</option>
            @foreach(\App\Models\DisciplineIncident::STATUSES as $v => $l)
            <option value="{{ $v }}" {{ request('status')==$v?'selected':'' }}>{{ $l }}</option>
            @endforeach
        </select>
        <button type="submit" class="px-4 py-2 rounded-lg text-white text-sm font-bold"
                style="background:#1A3A6B;">Filtrer</button>
        @can('manage-discipline')
        <a href="{{ route('discipline.create') }}"
           class="flex items-center gap-2 px-4 py-2 rounded-lg text-white text-sm font-bold"
           style="background:#E87722;">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
            </svg>
            Nouvel incident
        </a>
        @endcan
    </form>
</div>

{{-- ── TABLE ────────────────────────────────────────────────────────────── --}}
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    @if($incidents->isEmpty())
    <div class="px-5 py-12 text-center text-sm text-gray-400 italic">
        Aucun incident disciplinaire enregistré.
    </div>
    @else
    <form method="POST" action="{{ route('discipline.bulk-convocations') }}">
        @csrf
        <div class="px-5 py-4 border-b border-gray-100 flex flex-col lg:flex-row items-start lg:items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-3">
                @can('manage-discipline')
                <button type="submit" id="bulk-convocations-btn" class="px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm font-bold hover:bg-emerald-700 transition-all disabled:cursor-not-allowed disabled:opacity-50" disabled>
                    Générer convocations sélectionnées
                </button>
                @endcan
                <a href="{{ route('discipline.reports') }}" class="px-4 py-2 rounded-lg border border-[#1A3A6B] text-sm font-bold text-[#1A3A6B] hover:bg-[#F8FAFC] transition-all">
                    Rapport d'incidents
                </a>
                <span class="text-xs text-gray-500">Sélectionnez uniquement les incidents dont les parents ont été convoqués.</span>
            </div>
            <div class="text-xs text-gray-500">Convocation disponible pour <strong>{{ $incidents->where('parent_convoked', true)->count() }}</strong> incident(s)</div>
        </div>
        <div class="discipline-table-scroll">
        <table class="discipline-table w-full">
        <thead>
            <tr style="background:#F8FAFC; border-bottom:1px solid #E5E7EB;">
                <th class="text-left px-5 py-3 text-xs font-bold text-gray-400 uppercase tracking-wider">
                    <span class="sr-only">Sélection</span>
                </th>
                @foreach(['Date','Élève','Type d\'incident','Sanction','Statut','Actions'] as $th)
                <th class="text-left px-5 py-3 text-xs font-bold text-gray-400
                           uppercase tracking-wider {{ $loop->last ? 'text-right' : '' }}">
                    {{ $th }}
                </th>
                @endforeach
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @foreach($incidents as $inc)
            @php
                $typeColors = [
                    'retard'        => ['bg'=>'#FEF3C7','text'=>'#92400E','label'=>'Retard'],
                    'comportement'  => ['bg'=>'#FEE2E2','text'=>'#991B1B','label'=>'Comportement'],
                    'fraude'        => ['bg'=>'#EDE9FE','text'=>'#6D28D9','label'=>'Fraude'],
                    'violence'      => ['bg'=>'#FEE2E2','text'=>'#7F1D1D','label'=>'Violence'],
                    'autre'         => ['bg'=>'#F3F4F6','text'=>'#374151','label'=>'Autre'],
                ];
                $sanctionConf = [
                    'observation'           => ['bg'=>'#EBF3FB','text'=>'#1A3A6B','label'=>'Observation'],
                    'warning'               => ['bg'=>'#FEF3C7','text'=>'#92400E','label'=>'Avertissement'],
                    'detention'             => ['bg'=>'#EDE9FE','text'=>'#6D28D9','label'=>'Retenue'],
                    'temporary_suspension'  => ['bg'=>'#FEE2E2','text'=>'#991B1B','label'=>'Renvoi temp.'],
                    'definitive_exclusion'  => ['bg'=>'#450A0A','text'=>'#fff',   'label'=>'Exclusion définitive'],
                ];
                $statusConf = [
                    'open'   => ['bg'=>'#FEF3C7','text'=>'#92400E','label'=>'Ouvert'],
                    'closed' => ['bg'=>'#D1FAE5','text'=>'#065F46','label'=>'Clôturé'],
                ];
                $tc = $typeColors[$inc->incident_type]    ?? $typeColors['autre'];
                $sc = $sanctionConf[$inc->sanction_type]  ?? null;
                $st = $statusConf[$inc->status]           ?? $statusConf['open'];
            @endphp
            <tr class="hover:bg-gray-50/50 transition-colors">
                <td class="px-5 py-3.5">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="incident_ids[]" value="{{ $inc->id }}"
                               {{ $inc->parent_convoked ? '' : 'disabled' }}
                               class="bulk-convocation-checkbox h-4 w-4 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500"
                               data-parent-convoked="{{ $inc->parent_convoked ? '1' : '0' }}">
                        @if(! $inc->parent_convoked)
                        <span class="text-[10px] text-gray-400">non conv.</span>
                        @endif
                    </label>
                </td>
                <td class="px-5 py-3.5">
                    <p class="text-sm font-bold text-gray-800">
                        {{ $inc->incident_date->format('d/m/Y') }}
                    </p>
                    @if($inc->incident_time)
                    <p class="text-xs text-gray-400">{{ $inc->incident_time }}</p>
                    @endif
                </td>
                <td class="px-5 py-3.5">
                    <p class="text-sm font-bold text-gray-800">
                        {{ $inc->studentEnrollment?->student?->full_name }}
                    </p>
                    <p class="text-xs text-gray-400">
                        {{ $inc->studentEnrollment?->classGroup?->full_name }}
                    </p>
                </td>
                <td class="px-5 py-3.5">
                    <span class="px-2.5 py-1 rounded-full text-xs font-bold"
                          style="background:{{ $tc['bg'] }};color:{{ $tc['text'] }};">
                        {{ $tc['label'] }}
                    </span>
                </td>
                <td class="px-5 py-3.5">
                    @if($sc)
                    <span class="px-2.5 py-1 rounded-full text-xs font-bold"
                          style="background:{{ $sc['bg'] }};color:{{ $sc['text'] }};">
                        {{ $sc['label'] }}
                    </span>
                    @else
                    <span class="text-gray-300 text-xs">Aucune</span>
                    @endif
                </td>
                <td class="px-5 py-3.5">
                    <span class="px-2.5 py-1 rounded-full text-xs font-bold"
                          style="background:{{ $st['bg'] }};color:{{ $st['text'] }};">
                        {{ $st['label'] }}
                    </span>
                </td>
                <td class="px-5 py-3.5 text-right">
                    <div class="flex items-center justify-end gap-1.5">
                        <a href="{{ route('discipline.show', $inc) }}"
                           title="Consulter l'incident"
                           class="p-1.5 rounded-lg text-blue-600 hover:bg-blue-50 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </a>
                        <a href="{{ route('discipline.edit', $inc) }}"
                           title="Modifier l'incident"
                           class="p-1.5 rounded-lg text-amber-600 hover:bg-amber-50 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </a>
                        @can('manage-discipline')
                        <button type="button"
                                onclick="if(confirm('Êtes-vous sûr de vouloir supprimer définitivement cet incident ? Cette action est irréversible.')){ document.getElementById('delete-incident-form-{{ $inc->id }}').submit(); }"
                                title="Supprimer l'incident"
                                class="p-1.5 rounded-lg text-red-600 hover:bg-red-50 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                        @endcan
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    </div>
    </form>

    @can('manage-discipline')
    @foreach($incidents as $inc)
    <form id="delete-incident-form-{{ $inc->id }}" method="POST" action="{{ route('discipline.destroy', $inc) }}" class="hidden">
        @csrf
        @method('DELETE')
    </form>
    @endforeach
    @endcan
    @if($incidents->hasPages())
    <div class="px-5 py-4 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-4">
        <span class="text-sm text-gray-500 font-medium">
            Affichage {{ $incidents->firstItem() }}-{{ $incidents->lastItem() }} sur {{ $incidents->total() }} incidents
        </span>
        <div>{{ $incidents->links('vendor.pagination.custom') }}</div>
    </div>
    @endif
    @endif
</div>
</div>

<div id="report-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 px-4 py-6">
    <div class="w-full max-w-3xl overflow-hidden rounded-3xl bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
            <div>
                <h2 class="text-lg font-black text-[#1A3A6B]">Rapport d'incidents</h2>
                <p class="text-sm text-gray-500">Choisissez les filtres puis cliquez sur Générer.</p>
            </div>
            <button id="close-report-modal" class="text-gray-400 hover:text-gray-600">Fermer</button>
        </div>
        <div class="p-6">
            <form id="report-form" method="GET" action="{{ route('discipline.reports') }}" target="_blank" class="space-y-4">
                <div class="grid gap-4 lg:grid-cols-2">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-1.5">Type de rapport</label>
                        <div class="flex flex-wrap rounded-2xl border border-gray-200 bg-white p-1">
                            @php $reportTypes = ['journalier'=>'Journalier','hebdomadaire'=>'Hebdomadaire','mensuel'=>'Mensuel','annuel'=>'Annuel','entre-2-dates'=>'Entre 2 dates']; @endphp
                            @foreach($reportTypes as $val => $lbl)
                            <button type="button" data-report-type="{{ $val }}" class="report-type-btn type-btn px-4 py-2 text-sm font-bold transition-colors {{ $val === 'mensuel' ? 'active' : '' }}" style="{{ $val === 'mensuel' ? 'background:#1A3A6B;color:#fff;' : 'background:white;color:#6B7280;' }}">{{ $lbl }}</button>
                            @endforeach
                        </div>
                        <input type="hidden" name="type" id="report-type-input" value="mensuel">
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-1.5">Année scolaire</label>
                        <select name="year_id" class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm bg-white">
                            @foreach(App\Models\AcademicYear::orderByDesc('start_date')->get() as $year)
                                <option value="{{ $year->id }}">{{ $year->label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div id="report-filters" class="grid gap-4 lg:grid-cols-3">
                    <div id="filter-journalier" class="hidden">
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-1.5">Date</label>
                        <input type="date" name="date" class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm">
                    </div>
                    <div id="filter-hebdomadaire" class="hidden">
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-1.5">Semaine</label>
                        <input type="week" name="week" class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm">
                    </div>
                    <div id="filter-mensuel">
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-1.5">Mois</label>
                        <select name="month" class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm bg-white">
                            @foreach(range(1,12) as $num)
                                <option value="{{ $num }}">{{ \Carbon\Carbon::create()->month($num)->locale('fr')->monthName }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div id="filter-entre-2-dates" class="hidden gap-3 lg:grid-cols-2">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-1.5">Début</label>
                            <input type="date" name="start_date" class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-1.5">Fin</label>
                            <input type="date" name="end_date" class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm">
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <button type="button" id="close-report-modal-bottom" class="rounded-xl border border-gray-200 px-5 py-2.5 text-sm font-semibold text-gray-700">Annuler</button>
                    <button type="submit" class="rounded-xl bg-[#1A3A6B] px-5 py-2.5 text-sm font-bold text-white">Générer</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const button = document.getElementById('bulk-convocations-btn');
        const checkboxes = Array.from(document.querySelectorAll('.bulk-convocation-checkbox'));
        const reportModal = document.getElementById('report-modal');
        const openReportModal = document.getElementById('open-report-modal');
        const closeReportModal = document.getElementById('close-report-modal');
        const closeReportModalBottom = document.getElementById('close-report-modal-bottom');
        const reportTypeInput = document.getElementById('report-type-input');
        const reportTypeButtons = Array.from(document.querySelectorAll('.report-type-btn'));
        const filterMap = {
            journalier: 'filter-journalier',
            hebdomadaire: 'filter-hebdomadaire',
            mensuel: 'filter-mensuel',
            annuel: 'filter-mensuel',
            'entre-2-dates': 'filter-entre-2-dates',
        };

        const setActiveReportType = (type) => {
            reportTypeInput.value = type;
            reportTypeButtons.forEach((btn) => {
                const isActive = btn.dataset.reportType === type;
                btn.classList.toggle('active', isActive);
                btn.style.background = isActive ? '#1A3A6B' : 'white';
                btn.style.color = isActive ? '#fff' : '#6B7280';
            });
            Object.entries(filterMap).forEach(([key, id]) => {
                const el = document.getElementById(id);
                if (!el) return;
                el.style.display = key === type ? (key === 'entre-2-dates' ? 'grid' : 'block') : 'none';
            });
        };

        const toggleButton = () => {
            if (!button) return;
            const hasChecked = checkboxes.some((box) => box.checked);
            button.disabled = !hasChecked;
        };

        checkboxes.forEach((box) => box.addEventListener('change', toggleButton));
        toggleButton();

        if (openReportModal) {
            openReportModal.addEventListener('click', () => {
                reportModal.classList.remove('hidden');
                reportModal.classList.add('flex');
            });
        }
        const closeModal = () => {
            reportModal.classList.add('hidden');
            reportModal.classList.remove('flex');
        };
        if (closeReportModal) closeReportModal.addEventListener('click', closeModal);
        if (closeReportModalBottom) closeReportModalBottom.addEventListener('click', closeModal);

        reportTypeButtons.forEach((btn) => {
            btn.addEventListener('click', () => setActiveReportType(btn.dataset.reportType));
        });

        setActiveReportType('mensuel');
    });
</script>
@endpush
