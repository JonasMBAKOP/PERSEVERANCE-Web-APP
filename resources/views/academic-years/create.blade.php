@extends('layouts.app')

@section('title', 'Nouvelle année scolaire')
@section('page-title', 'Nouvelle Année Scolaire')
@section('page-subtitle', 'Configurer le calendrier scolaire')

{{-- @section('breadcrumb')
    <a href="{{ route('academic-years.index') }}" class="hover:text-gray-700">
        Années scolaires
    </a>
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round"
              stroke-width="2" d="M9 5l7 7-7 7"/>
    </svg>
    <span class="text-gray-700">Nouvelle année</span>
@endsection --}}

@section('content')

<form method="POST" action="{{ route('academic-years.store') }}"
      x-data="academicYearForm()">
    @csrf

    <div class="grid grid-cols-1 lg:grid-cols-[minmax(0,1.15fr)_minmax(320px,0.85fr)] gap-6">

        {{-- Colonne principale --}}
        <div class="space-y-6">

            {{-- ── Informations générales ──────────────────────────────── --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-sm font-semibold uppercase tracking-wider
                           text-gray-400 mb-4 pb-2 border-b border-gray-100">
                    Informations générales
                </h3>

                <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
                    <div class="xl:col-span-1">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Libellé <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="label"
                               value="{{ old('label', $suggestedLabel) }}"
                               placeholder="Ex: 2025-2026"
                               @input="updateLabel($event.target.value)"
                               class="w-full px-3 py-2.5 border rounded-lg text-sm
                                      font-mono font-semibold focus:outline-none
                                      @error('label') border-red-400
                                      @else border-gray-200 @enderror"
                               style="color: #1A3A6B;">
                        @error('label')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-gray-400">
                            Format : AAAA-AAAA
                        </p>
                    </div>

                    <div class="xl:col-span-1">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Date de début <span class="text-red-500">*</span>
                        </label>
                        <input type="date" name="start_date"
                               value="{{ old('start_date') }}"
                               class="w-full px-3 py-2.5 border rounded-lg text-sm
                                      focus:outline-none
                                      @error('start_date') border-red-400
                                      @else border-gray-200 @enderror">
                        @error('start_date')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="xl:col-span-1">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Date de fin <span class="text-red-500">*</span>
                        </label>
                        <input type="date" name="end_date"
                               value="{{ old('end_date') }}"
                               class="w-full px-3 py-2.5 border rounded-lg text-sm
                                      focus:outline-none
                                      @error('end_date') border-red-400
                                      @else border-gray-200 @enderror">
                        @error('end_date')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- ── Calendrier des trimestres & séquences ───────────────── --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between mb-4 pb-2
                            border-b border-gray-100">
                    <h3 class="text-sm font-semibold uppercase tracking-wider
                               text-gray-400">
                        Calendrier des séquences
                    </h3>
                    <span class="text-xs text-gray-400">
                        Dates optionnelles — modifiables plus tard
                    </span>
                </div>

                <div class="space-y-4">
                    @php
                        $trimestres = [];
                        foreach (\App\Models\AcademicYear::sequenceCalendar() as $number => $sequences) {
                            $trimestres[$number] = [
                                'label' => 'Trimestre ' . $number,
                                'seqs' => $sequences,
                            ];
                        }                    @endphp

                    @foreach($trimestres as $tNum => $trimestre)
                    <div class="border border-gray-100 rounded-xl overflow-hidden">
                        {{-- En-tête trimestre --}}
                        <div class="px-4 py-3 flex items-center gap-4"
                             style="background-color: #F0F4F8;">
                            <div class="w-8 h-8 rounded-full flex items-center
                                        justify-center text-white text-sm font-bold
                                        flex-shrink-0"
                                 style="background-color: #1A3A6B;">
                                T{{ $tNum }}
                            </div>
                            <span class="font-semibold text-sm"
                                  style="color: #1A3A6B;">
                                {{ $trimestre['label'] }}
                            </span>
                            {{-- <div class="flex items-center gap-2 ml-auto">
                                <input type="date"
                                       name="trimesters[{{ $tNum }}][start_date]"
                                       value="{{ old("trimesters.{$tNum}.start_date") }}"
                                       class="px-2 py-1.5 border border-gray-200
                                              rounded-lg text-xs focus:outline-none"
                                       placeholder="Début">
                                <span class="text-gray-400 text-xs">→</span>
                                <input type="date"
                                       name="trimesters[{{ $tNum }}][end_date]"
                                       value="{{ old("trimesters.{$tNum}.end_date") }}"
                                       class="px-2 py-1.5 border border-gray-200
                                              rounded-lg text-xs focus:outline-none"
                                       placeholder="Fin">
                            </div> --}}
                        </div>

                        {{-- Séquences du trimestre --}}
                        <div class="divide-y divide-gray-50">
                            @foreach($trimestre['seqs'] as $sNum => $sLabel)
                            <div class="px-4 py-3 grid grid-cols-1 md:grid-cols-[auto_1fr_auto] gap-3 md:items-center bg-white">
                                <div class="w-6 h-6 rounded-full flex items-center
                                            justify-center text-xs font-medium
                                            flex-shrink-0 md:ml-4"
                                     style="background-color: #EBF3FB;
                                            color: #1A3A6B;">
                                    {{ $sNum }}
                                </div>
                                <span class="hidden">
                                    Séquence {{ $sNum }}
                                </span>
                                <div>
                                    <label class="block text-[11px] font-semibold text-gray-400 uppercase tracking-wider mb-1">
                                        Nom de l'evaluation
                                    </label>
                                    <input type="text"
                                           name="sequences[{{ $sNum }}][label]"
                                           value="{{ old("sequences.{$sNum}.label", $sLabel) }}"
                                           class="w-full md:max-w-[220px] px-3 py-2 border rounded-lg text-sm font-semibold focus:outline-none
                                                  @error("sequences.{$sNum}.label") border-red-400 @else border-gray-200 @enderror"
                                           style="color: #1A3A6B;">
                                    @error("sequences.{$sNum}.label")
                                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="flex items-center gap-2 md:ml-auto">
                                    <input type="date"
                                           name="sequences[{{ $sNum }}][start_date]"
                                           value="{{ old("sequences.{$sNum}.start_date") }}"
                                           class="px-2 py-1.5 border border-gray-200
                                                  rounded-lg text-xs focus:outline-none">
                                    <span class="text-gray-400 text-xs">→</span>
                                    <input type="date"
                                           name="sequences[{{ $sNum }}][end_date]"
                                           value="{{ old("sequences.{$sNum}.end_date") }}"
                                           class="px-2 py-1.5 border border-gray-200
                                                  rounded-lg text-xs focus:outline-none">
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

        </div>

        {{-- Colonne résumé --}}
        <div class="space-y-4">
            {{-- Copie depuis une année précédente --}}
            @if($previousYears->isNotEmpty())
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6"
                 x-data="{ copyEnabled: false }">
                <div class="flex items-center justify-between mb-4 pb-2
                            border-b border-gray-100">
                    <h3 class="text-sm font-semibold uppercase tracking-wider
                               text-gray-400">
                        Copie depuis une année précédente
                    </h3>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" x-model="copyEnabled"
                               class="rounded" style="accent-color: #1A3A6B;">
                        <span class="text-sm text-gray-600">Activer</span>
                    </label>
                </div>

                <div x-show="copyEnabled" x-transition>
                    <div class="grid grid-cols-1 gap-4 mb-4">
                        <div class="w-full max-w-full">
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Copier depuis
                            </label>
                            <select name="copy_from"
                                    class="w-full min-w-0 px-3 py-2.5 border border-gray-200
                                           rounded-lg text-sm focus:outline-none
                                           bg-white">
                                <option value="">Sélectionner une année...</option>
                                @foreach($previousYears as $prev)
                                <option value="{{ $prev->id }}"
                                        {{ old('copy_from') == $prev->id ? 'selected' : '' }}>
                                    {{ $prev->label }}
                                    ({{ $prev->class_groups_count }} classes)
                                </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <label class="flex items-start gap-3 p-3 rounded-lg border
                                      border-gray-200 cursor-pointer hover:bg-gray-50">
                            <input type="checkbox" name="copy_classes" value="1"
                                   {{ old('copy_classes') ? 'checked' : 'checked' }}
                                   class="mt-0.5 rounded"
                                   style="accent-color: #1A3A6B;">
                            <div>
                                <p class="text-sm font-medium text-gray-800">
                                    Classes
                                </p>
                                <p class="text-xs text-gray-500">
                                    Copier toutes les classes
                                </p>
                            </div>
                        </label>

                        <label class="flex items-start gap-3 p-3 rounded-lg border
                                      border-gray-200 cursor-pointer hover:bg-gray-50">
                            <input type="checkbox" name="copy_subjects" value="1"
                                   {{ old('copy_subjects') ? 'checked' : 'checked' }}
                                   class="mt-0.5 rounded"
                                   style="accent-color: #1A3A6B;">
                            <div>
                                <p class="text-sm font-medium text-gray-800">
                                    Matières
                                </p>
                                <p class="text-xs text-gray-500">
                                    Matières & coefficients
                                </p>
                            </div>
                        </label>

                        <label class="flex items-start gap-3 p-3 rounded-lg border
                                      border-gray-200 cursor-pointer hover:bg-gray-50">
                            <input type="checkbox" name="copy_fees" value="1"
                                   class="mt-0.5 rounded"
                                   style="accent-color: #1A3A6B;">
                            <div>
                                <p class="text-sm font-medium text-gray-800">
                                    Frais
                                </p>
                                <p class="text-xs text-gray-500">
                                    Structures de frais
                                </p>
                            </div>
                        </label>
                    </div>
                </div>

                <div x-show="!copyEnabled" class="text-center py-4">
                    <p class="text-sm text-gray-400">
                        Activez cette option pour copier les classes,
                        matières et frais d'une année précédente.
                    </p>
                </div>
            </div>
            @endif

            {{-- Résumé --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <h3 class="text-sm font-semibold uppercase tracking-wider
                           text-gray-400 mb-4 pb-2 border-b border-gray-100">
                    Résumé
                </h3>
                <div class="space-y-3 text-sm">
                    <div class="flex items-center gap-2 text-green-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                             viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        3 trimestres créés automatiquement
                    </div>
                    <div class="flex items-center gap-2 text-green-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                             viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        6 évaluations créées automatiquement
                    </div>
                    <div class="flex items-center gap-2 text-gray-500">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                             viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  stroke-width="2"
                                  d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0
                                     11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Non activée par défaut
                    </div>
                    <div class="flex items-center gap-2 text-gray-500">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                             viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  stroke-width="2"
                                  d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0
                                     11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Dates modifiables après création
                    </div>
                </div>
            </div>

            {{-- Note info --}}
            <div class="p-4 rounded-xl text-sm"
                 style="background-color: #EBF3FB;">
                <div class="flex gap-2">
                    <svg class="w-5 h-5 flex-shrink-0 mt-0.5"
                         style="color: #1A3A6B;"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              stroke-width="2"
                              d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0
                                 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p style="color: #1A3A6B;">
                        L'année créée ne sera pas active immédiatement.
                        Vous pourrez l'activer depuis la liste des années
                        scolaires quand vous le souhaitez.
                    </p>
                </div>
            </div>

            {{-- Bouton --}}
            <button type="submit"
                    class="w-full py-3 rounded-xl text-white font-semibold
                           text-sm flex items-center justify-center gap-2
                           transition-colors"
                    style="background-color: #1A5C2A;">
                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                     viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Créer l'année scolaire
            </button>

            <a href="{{ route('academic-years.index') }}"
               class="block w-full py-2.5 rounded-xl text-center text-sm
                      font-medium text-gray-600 border border-gray-200
                      hover:bg-gray-50 transition-colors">
                Annuler
            </a>
        </div>

    </div>
</form>

<script>
function academicYearForm() {
    return {
        updateLabel(val) {
            // Auto-format: si on tape 4 chiffres, ajoute le tiret
            if (val.length === 4 && !val.includes('-')) {
                const input = document.querySelector('input[name="label"]');
                const nextYear = parseInt(val) + 1;
                input.value = val + '-' + nextYear;
            }
        }
    }
}
</script>

@endsection
