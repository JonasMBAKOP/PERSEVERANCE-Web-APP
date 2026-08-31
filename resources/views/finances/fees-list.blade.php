@extends('layouts.app')

@section('title', 'Configuration des frais')
@section('page-title', 'Configuration des Frais')
@section('page-subtitle', 'Définir les frais scolaires par classe')

@push('styles')
<style>
.fee-list-filter select {
    min-width: 13rem;
    padding-right: 2.75rem;
}
.fee-list-filter select[name="section_id"] { min-width: 16rem; }
@media (max-width: 639px) {
    .fee-list-filter select { min-width: 0; width: auto; }
}
</style>
@endpush

@section('content')

<div class="finance-page -mx-2 -mt-2 -mb-2 lg:-mx-4 lg:-mt-4 lg:-mb-4">
    {{-- Sélecteurs d'année et de section --}}
    <div class="mb-6">
        <form method="GET" action="{{ route('finances.fees-list') }}"
            class="fee-list-filter flex flex-wrap items-center gap-3">
            <div class="flex items-center gap-2">
                <label class="text-sm text-gray-500">Année :</label>
                <select name="year_id" onchange="this.form.submit()"
                        class="px-3 py-2 border border-gray-200 rounded-lg text-sm
                            focus:outline-none bg-white"
                        style="color:#1A3A6B;">
                    @foreach($years as $year)
                        <option value="{{ $year->id }}"
                                {{ $selectedYear?->id == $year->id ? 'selected' : '' }}>
                            {{ $year->label }} {{ $year->is_active ? '(Active)' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center gap-2">
                <label class="text-sm text-gray-500">Section :</label>
                <select name="section_id" onchange="this.form.submit()"
                        class="px-3 py-2 border border-gray-200 rounded-lg text-sm
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
    </div>

    @if($classes->isEmpty())
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-12
                    text-center text-gray-400">
            <p class="text-sm">Aucune classe pour cette année.</p>
        </div>
    @else

        @foreach($classes->groupBy('level.section.name') as $sectionName => $sectionClasses)
            <div class="mb-6">
                <h3 class="text-sm font-semibold uppercase tracking-wider mb-3"
                    style="color:#1A3A6B;">
                    {{ $sectionName }}
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($sectionClasses as $class)
                        @php
                            $fee       = $class->feeStructures->first();
                            $installed = $class->studentEnrollments->count();
                            $total     = $fee?->installments->sum('amount') ?? 0;
                        @endphp
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5
                                    flex flex-col gap-3">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <p class="font-bold text-sm" style="color:#1A3A6B;">
                                        {{ $class->full_name }}
                                    </p>
                                    <p class="text-xs text-gray-400">
                                        {{ $installed }} élève(s) inscrits
                                    </p>
                                </div>
                                @if($fee)
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium
                                            bg-green-100 text-green-700 flex-shrink-0">
                                    Configuré
                                </span>
                                @else
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium
                                            bg-amber-100 text-amber-700 flex-shrink-0">
                                    À configurer
                                </span>
                                @endif
                            </div>

                            {{-- Tranches existantes --}}
                            @if($fee && $fee->installments->isNotEmpty())
                                <div class="space-y-1">
                                    @foreach($fee->installments->sortBy('installment_number') as $inst)
                                        <div class="flex justify-between text-xs">
                                            <span class="text-gray-500">{{ $inst->label }}</span>
                                            <span class="font-semibold" style="color:#1A3A6B;">
                                                {{ number_format($inst->amount) }} FCFA
                                            </span>
                                        </div>
                                    @endforeach
                                    <div class="pt-1 border-t border-gray-100 flex justify-between
                                                text-xs font-bold">
                                        <span class="text-gray-600">Total / élève</span>
                                        <span style="color:#1A5C2A;">
                                            {{ number_format($total) }} FCFA
                                        </span>
                                    </div>
                                </div>
                            @else
                                <p class="text-xs text-gray-400 italic">
                                    Aucun frais défini pour cette classe.
                                </p>
                            @endif

                            {{-- Bouton configurer --}}
                            @can('configure-fees')
                                @if(!$selectedYear?->isClosed())
                                    <a href="{{ route('finances.fees', $class) }}"
                                    class="w-full py-2 rounded-lg text-center text-sm font-medium
                                            transition-colors border inline-flex items-center justify-center gap-2
                                            {{ $fee
                                                ? 'border-blue-200 text-blue-700 hover:bg-blue-50'
                                                : 'text-white hover:shadow-md' }}"
                                    style="{{ !$fee ? 'background-color:#1A5C2A;' : '' }}">
                                        @if($fee)
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                            </svg>
                                            <span>Modifier les frais</span>
                                        @else
                                            <span>+ Configurer les frais</span>
                                        @endif
                                    </a>
                                @else
                                    <span class="w-full py-2 rounded-lg text-center text-xs
                                                text-gray-400 border border-gray-200">
                                        Année clôturée
                                    </span>
                                @endif
                            @endcan
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach

    @endif

</div>
@endsection
