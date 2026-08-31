@extends('layouts.app')
@section('title', 'Incident — ' . $disciplineIncident->studentEnrollment->student->full_name)
@section('page-title', 'Détail de l\'incident')
@section('page-subtitle'){{ $disciplineIncident->incident_date->format('d/m/Y') }}@endsection

@section('content')

<style>
@media (max-width: 639px) {
    .incident-quick-actions { width: 100%; }
    .incident-quick-actions > a,
    .incident-quick-actions > form { width: 100%; }
    .incident-quick-actions > a,
    .incident-quick-actions > form > button { width: 100%; justify-content: center; }
    .incident-secondary-actions { width: 100%; justify-content: space-between; }
}
</style>

<div class="finance-page -mx-2 -mt-2 -mb-2 lg:-mx-4 lg:-mt-4 lg:-mb-4">

@php
    $typeLabels   = \App\Models\DisciplineIncident::INCIDENT_TYPES;
    $statusLabels = \App\Models\DisciplineIncident::STATUSES;
@endphp

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

    <div class="incident-actions lg:col-span-3 flex flex-wrap items-center justify-between gap-4 mb-4">
        <div class="space-y-2">
            <p class="text-xs uppercase tracking-[0.25em] text-gray-400 font-bold">Actions rapides</p>
            <div class="incident-quick-actions flex flex-wrap gap-2">
                <a href="{{ route('discipline.print', $disciplineIncident) }}" target="_blank"
                   class="inline-flex items-center gap-2 rounded-xl border border-blue-200 bg-blue-50 px-4 py-2 text-xs font-semibold text-blue-700 hover:bg-blue-100 transition-all">
                    Prévisualiser fiche
                </a>
                @if($disciplineIncident->parent_convoked)
                <a href="{{ route('discipline.convocation', $disciplineIncident) }}" target="_blank"
                   class="inline-flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2 text-xs font-semibold text-emerald-700 hover:bg-emerald-100 transition-all">
                    Prévisualiser convocation
                </a>
                @endif
                <a href="{{ route('discipline.edit', $disciplineIncident) }}"
                   class="inline-flex items-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-4 py-2 text-xs font-semibold text-amber-700 hover:bg-amber-100 transition-all">
                    Modifier
                </a>
                @can('manage-discipline')
                <form method="POST" action="{{ route('discipline.destroy', $disciplineIncident) }}"
                      onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer définitivement cet incident ? Cette action retirera complètement l\'incident du dossier de l\'élève.');"
                      class="inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="inline-flex items-center gap-2 rounded-xl border border-red-200 bg-red-50 px-4 py-2 text-xs font-semibold text-red-700 hover:bg-red-100 transition-all">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Supprimer l'incident
                    </button>
                </form>
                @endcan
            </div>
        </div>

        <div class="incident-secondary-actions flex flex-wrap items-center gap-3">
            @can('manage-discipline')
            <form method="POST" action="{{ route('discipline.status', $disciplineIncident) }}"
                  class="inline-flex items-center gap-2">
                @csrf @method('PATCH')
                <input type="hidden" name="status"
                       value="{{ $disciplineIncident->status === 'open' ? 'closed' : 'open' }}">
                <button type="submit"
                        class="inline-flex items-center gap-2 rounded-full px-4 py-2 text-xs font-semibold transition-all"
                        style="background:{{ $disciplineIncident->status === 'closed' ? '#D1FAE5' : '#FEF3C7' }}; color:{{ $disciplineIncident->status === 'closed' ? '#065F46' : '#92400E' }}; border:1px solid {{ $disciplineIncident->status === 'closed' ? '#A7F3D0' : '#FCD34D' }};">
                    {{ $disciplineIncident->status === 'closed' ? 'Clôturé' : 'Ouvert' }}
                </button>
            </form>
            @else
            <span class="inline-flex items-center rounded-full px-4 py-2 text-xs font-semibold"
                  style="background:{{ $disciplineIncident->status === 'closed' ? '#D1FAE5' : '#FEF3C7' }}; color:{{ $disciplineIncident->status === 'closed' ? '#065F46' : '#92400E' }};">
                {{ $statusLabels[$disciplineIncident->status] ?? ucfirst($disciplineIncident->status) }}
            </span>
            @endcan

            <a href="{{ route('discipline.index') }}"
               class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50 transition-all">
                Retour à la liste
            </a>
        </div>
    </div>

    {{-- ── Colonne principale ──────────────────────────────────────────── --}}
    <div class="lg:col-span-2 space-y-4">

        <div class="bg-white rounded-3xl shadow-xl border border-gray-100 p-6">
            <div class="flex items-center justify-between gap-4 mb-6">
                <span class="px-3 py-1 rounded-full text-xs font-bold"
                      style="background:#FEE2E2;color:#991B1B;">
                    {{ $disciplineIncident->incident_type_label }}
                </span>
                <div class="text-right">
                    <p class="text-sm font-black text-gray-900">{{ $disciplineIncident->studentEnrollment->student->full_name }}</p>
                    <p class="text-xs text-gray-400">
                        {{ $disciplineIncident->studentEnrollment->classGroup->full_name }}
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <p class="text-xs uppercase tracking-[0.25em] text-gray-400">Classe</p>
                    <p class="text-sm font-semibold text-gray-800">{{ $disciplineIncident->studentEnrollment->classGroup->full_name }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-[0.25em] text-gray-400">Date</p>
                    <p class="text-sm font-semibold text-gray-800">{{ $disciplineIncident->incident_date->format('d/m/Y') }} {{ $disciplineIncident->incident_time ? 'à ' . $disciplineIncident->incident_time : '' }}</p>
                </div>
            </div>
            @if($disciplineIncident->location)
            <p class="text-sm text-gray-500 mb-4">Lieu : {{ $disciplineIncident->location_label }}</p>
            @endif

            <div class="bg-gray-50 rounded-xl p-4">
                <p class="text-sm text-gray-700 leading-relaxed">
                    {{ $disciplineIncident->description }}
                </p>
            </div>
        </div>

        {{-- Sanction --}}
        @if($disciplineIncident->sanction_type)
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-sm font-black mb-3" style="color:#1A3A6B;">
                Sanction appliquée
            </h3>
            <p class="text-base font-bold text-gray-800">
                {{ $disciplineIncident->sanction_label }}
            </p>
            @if($disciplineIncident->sanction_duration_days)
            <p class="text-sm text-gray-500 mt-1">
                Durée : {{ $disciplineIncident->sanction_duration_days }} jour(s)
            </p>
            @endif
            @if($disciplineIncident->parent_convoked)
            <div class="mt-3 flex items-center gap-2 text-sm text-amber-700
                        bg-amber-50 px-3 py-2 rounded-lg">
                <svg class="inline h-4 w-4 mr-1 align-[-2px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>Parent convoqué
                @if($disciplineIncident->convocation_date)
                le {{ $disciplineIncident->convocation_date->format('d/m/Y') }}
                @endif
            </div>
            @endif
        </div>
        @endif

    </div>

    {{-- ── Colonne droite ───────────────────────────────────────────────── --}}
    <div class="space-y-4">

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <h3 class="text-sm font-black mb-3" style="color:#1A3A6B;">
                Informations
            </h3>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <dt class="text-gray-500">Signalé par</dt>
                    <dd class="font-semibold text-gray-800">
                        {{ $disciplineIncident->reportedBy?->name ?? '—' }}
                    </dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Décidé par</dt>
                    <dd class="font-semibold text-gray-800">
                        {{ $disciplineIncident->decidedBy?->name ?? '—' }}
                    </dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Créé le</dt>
                    <dd class="font-semibold text-gray-800">
                        {{ $disciplineIncident->created_at->format('d/m/Y H:i') }}
                    </dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Modifié par</dt>
                    <dd class="font-semibold text-gray-800">
                        {{ $disciplineIncident->decidedBy?->name ?? '—' }}
                    </dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Dernière modification</dt>
                    <dd class="font-semibold text-gray-800">
                        {{ $disciplineIncident->updated_at->format('d/m/Y H:i') }}
                    </dd>
                </div>
            </dl>
        </div>

        {{-- Historique élève --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <h3 class="text-sm font-black mb-3" style="color:#1A3A6B;">
                Autres incidents
                <span class="text-gray-400 font-normal text-xs ml-1">
                    ({{ $otherIncidents->count() }})
                </span>
            </h3>
            @if($otherIncidents->isEmpty())
            <p class="text-xs text-gray-400 italic">Aucun autre incident.</p>
            @else
            <div class="space-y-2">
                @foreach($otherIncidents as $oi)
                <a href="{{ route('discipline.show', $oi) }}"
                   class="block p-2.5 rounded-lg hover:bg-gray-50
                          transition-colors border border-gray-100">
                    <p class="text-xs font-bold text-gray-700">
                        {{ $typeLabels[$oi->incident_type] ?? 'Autre' }}
                    </p>
                    <p class="text-xs text-gray-400">
                        {{ $oi->incident_date->format('d/m/Y') }}
                    </p>
                </a>
                @endforeach
            </div>
            @endif
        </div>

    </div>

</div>
</div>

@endsection
