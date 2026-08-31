@extends('layouts.app')

@section('title', 'Frais — ' . $classGroup->full_name)
@section('page-title', 'Configuration des Frais')
@section('page-subtitle'){{ $classGroup->full_name }}
    — {{ $classGroup->academicYear->label }}@endsection

@section('breadcrumb')
    <a href="{{ route('finances.fees-list') }}" class="hover:text-gray-700">
        Config. des frais
    </a>
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round"
              stroke-width="2" d="M9 5l7 7-7 7"/>
    </svg>
    <span style="color:#1A3A6B;" class="font-medium">
        {{ $classGroup->full_name }}
    </span>
@endsection

@push('styles')
<style>
@media (max-width: 1023px) {
    .fees-total-actions { flex-direction: column; align-items: stretch; }
    .fees-total-actions > button { width: 100%; justify-content: center; }
}
</style>
@endpush

@section('content')

<div class="finance-page -mx-2 -mt-2 -mb-2 lg:-mx-4 lg:-mt-4 lg:-mb-4">
<div class="w-full max-w-7xl"
     x-data="feesForm({{ json_encode($feeStructure?->installments ?? []) }})">

    {{-- Info classe --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-5
                flex flex-col lg:flex-row lg:items-center gap-4 w-full">
        <div class="w-12 h-12 rounded-xl flex items-center justify-center
                    text-white font-bold text-lg flex-shrink-0"
             style="background-color:#1A3A6B;">
            {{ strtoupper(substr($classGroup->name, 0, 2)) }}
        </div>
        <div class="min-w-0">
            <p class="font-bold" style="color:#1A3A6B;">
                {{ $classGroup->full_name }}
            </p>
            <p class="text-sm text-gray-500">
                {{ $classGroup->level->section->name }}
                {{-- — {{ $classGroup->academicYear->label }} --}}
                — {{ $classGroup->studentEnrollments()->where('status','active')
                    ->count() }} élève(s)
            </p>
        </div>
        <div class="lg:ml-auto lg:text-right">
            <p class="text-xs text-gray-400">Total actuel</p>
            <p class="font-bold text-lg" style="color:#1A5C2A;"
               x-text="total.toLocaleString('fr-FR') + ' FCFA'">
                {{ number_format($feeStructure?->total_amount ?? 0) }} FCFA
            </p>
        </div>
    </div>

    <form method="POST"
          action="{{ route('finances.fees.save', $classGroup) }}">
        @csrf

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 w-full">
            <div class="flex items-center justify-between mb-5 pb-2
                        border-b border-gray-100">
                <h3 class="text-sm font-semibold uppercase tracking-wider
                           text-gray-400">
                    Types de paiement
                </h3>
                <button type="button" @click="addInstallment()"
                        class="text-xs font-medium hover:underline flex
                               items-center gap-1"
                        style="color:#1A5C2A;">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                         viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Ajouter un paiement
                </button>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
                <template x-for="(inst, i) in installments" :key="i">
                    <div class="border border-gray-200 rounded-xl p-4 bg-gray-50">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-semibold text-gray-500
                                         uppercase tracking-wider"
                                  x-text="'Paiement ' + (i + 1)">
                            </span>
                            <button type="button"
                                    @click="removeInstallment(i)"
                                    x-show="installments.length > 1"
                                    class="text-xs text-red-500 hover:underline">
                                Supprimer
                            </button>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-3">
                            <div class="sm:col-span-12">
                                <label class="block text-xs font-medium
                                               text-gray-600 mb-1">
                                    Libellé <span class="text-red-500">*</span>
                                </label>
                                <input type="text"
                                       :name="`installments[${i}][label]`"
                                       x-model="inst.label"
                                       placeholder="Ex: Frais d'inscription, Tranche 1..."
                                       class="w-full px-3 py-2 border border-gray-200
                                              rounded-lg text-sm focus:outline-none
                                              bg-white">
                            </div>
                            <div class="sm:col-span-4">
                                <label class="block text-xs font-medium
                                               text-gray-600 mb-1">
                                    Montant (FCFA)
                                    <span class="text-red-500">*</span>
                                </label>
                                <input type="number"
                                       :name="`installments[${i}][amount]`"
                                       x-model="inst.amount"
                                       @input="calcTotal()"
                                       min="0" step="500"
                                       placeholder="Ex: 25000"
                                       class="w-full px-3 py-2 border border-gray-200
                                              rounded-lg text-sm focus:outline-none
                                              bg-white font-mono">
                            </div>
                            <div class="sm:col-span-8 min-w-0">
                                <label class="block text-xs font-medium
                                               text-gray-600 mb-1">
                                    Période de paiement
                                </label>
                                <div class="grid grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] items-center gap-1">
                                    <input type="date"
                                           :name="`installments[${i}][due_date_start]`"
                                           x-model="inst.due_date_start"
                                           class="w-full min-w-0 px-2 py-2 border
                                                  border-gray-200 rounded-lg
                                                  text-xs focus:outline-none bg-white">
                                    <span class="text-gray-300 text-xs">→</span>
                                    <input type="date"
                                           :name="`installments[${i}][due_date_end]`"
                                           x-model="inst.due_date_end"
                                           class="w-full min-w-0 px-2 py-2 border
                                                  border-gray-200 rounded-lg
                                                  text-xs focus:outline-none bg-white">
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Total --}}
            <div class="fees-total-actions mt-5 pt-4 border-t border-gray-100 flex items-center
                        justify-between gap-4">
                <div>
                    <p class="text-sm text-gray-500">
                        Total des frais par élève
                    </p>
                    <p class="text-2xl font-black mt-0.5" style="color:#1A3A6B;"
                       x-text="total.toLocaleString('fr-FR') + ' FCFA'">
                    </p>
                </div>
                <button type="submit"
                        class="px-6 py-3 rounded-xl text-white font-bold text-sm
                               flex items-center gap-2 transition-all hover:shadow-md"
                        style="background-color:#1A5C2A;">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor"
                         viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Enregistrer les frais
                </button>
            </div>
        </div>
    </form>
</div>
</div>

<script>
function feesForm(existingInstallments) {
    const defaults = [
        { label: 'Carnet Médical', amount: 0,
          due_date_start: '', due_date_end: '' },
        { label: 'Frais d\'inscription', amount: 0,
          due_date_start: '', due_date_end: '' },
        { label: 'Tranche 1',            amount: 0,
          due_date_start: '', due_date_end: '' },
        { label: 'Tranche 2',            amount: 0,
          due_date_start: '', due_date_end: '' },
    ];

    const initial = existingInstallments.length > 0
        ? existingInstallments.map(i => ({
            label:          i.label,
            amount:         i.amount,
            due_date_start: i.due_date_start ?? '',
            due_date_end:   i.due_date_end   ?? '',
          }))
        : defaults;

    return {
        installments: initial,
        total: initial.reduce((s, i) => s + (parseFloat(i.amount) || 0), 0),

        addInstallment() {
            this.installments.push({
                label: '', amount: 0,
                due_date_start: '', due_date_end: ''
            });
        },
        removeInstallment(i) {
            if (this.installments.length <= 1) return;
            this.installments.splice(i, 1);
            this.calcTotal();
        },
        calcTotal() {
            this.total = this.installments.reduce(
                (s, i) => s + (parseFloat(i.amount) || 0), 0
            );
        }
    }
}
</script>

@endsection
