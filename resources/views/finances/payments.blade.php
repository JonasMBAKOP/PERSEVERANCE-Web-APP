@extends('layouts.app')

@section('title', 'Paiements')
@section('page-title', 'Tous les Paiements')
@section('page-subtitle', 'Du plus récent au plus ancien')

@push('styles')
<style>
.payments-table { table-layout: fixed; }
.payments-table .payment-receipt-column {
    width: 8rem !important;
    max-width: 8rem !important;
    overflow: hidden;
    padding-left: .5rem !important;
    padding-right: .5rem !important;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.payments-table .payment-actions-column {
    width: 3rem !important;
    max-width: 3rem !important;
    padding-left: .25rem !important;
    padding-right: .25rem !important;
    white-space: nowrap;
}
.payments-table .payment-installment-column {
    width: 8.5rem !important;
    max-width: 8.5rem !important;
}
.payments-table .payment-date-column {
    width: 6.5rem !important;
    max-width: 6.5rem !important;
}
.payments-table .payment-amount-column {
    width: 8rem !important;
    max-width: 8rem !important;
}
.payments-table .payment-method-column {
    width: 7rem !important;
    max-width: 7rem !important;
}
.payments-table .payment-student-column {
    width: 12rem !important;
    max-width: 12rem !important;
}
@media (min-width: 1024px) {
    .payments-table { width: 100% !important; min-width: 0 !important; }
}
</style>
@endpush

@section('content')

<div x-data="paymentsManager()" class="-mx-2 -mt-2 pb-2">

{{-- ── BARRE ACTION FLOTTANTE ──────────────────────────────────────────── --}}
<div x-show="selected.size > 0" x-transition
     class="fixed top-20 left-1/2 -translate-x-1/2 z-50
            flex items-center gap-3 px-5 py-3 rounded-2xl shadow-xl
            border border-blue-200"
     style="background-color:#1A3A6B;">
    <span class="text-white text-sm font-bold">
        <span x-text="selected.size"></span>
        reçu(s) sélectionné(s)
    </span>
    <button @click="printSelected()"
            class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm
                   font-bold transition-all hover:shadow-md"
            style="background-color:#F3D99B; color:#1A3A6B;">
        <svg class="w-4 h-4" fill="none" stroke="currentColor"
             viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round"
                  stroke-width="2"
                  d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2
                     2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0
                     00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2
                     2v4h10z"/>
        </svg>
        Imprimer les reçus sélectionnés
        <span class="text-xs opacity-70">(2/page A4)</span>
    </button>
    <button @click="clearSelection()"
            class="p-1.5 rounded-lg text-white hover:bg-white/20">
        <svg class="w-4 h-4" fill="none" stroke="currentColor"
             viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round"
                  stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
    </button>
</div>

{{-- ── FILTRES ───────────────────────────────────────────────────────────── --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-5">
    <form method="GET" action="{{ route('finances.payments') }}"
          class="flex flex-col gap-3 overflow-x-auto pb-1">

        <div class="flex min-w-max flex-nowrap gap-3">

        <select name="year_id" onchange="this.form.submit()"
                class="w-[15rem] min-w-[15rem] shrink-0 px-3 py-2 pr-10 border border-gray-200 rounded-lg text-sm
                       focus:outline-none bg-white">
            <option value="">Toutes les années</option>
            @foreach(\App\Models\AcademicYear::orderByDesc('start_date')
                ->get() as $yr)
            <option value="{{ $yr->id }}"
                    {{ $selectedYearId == $yr->id ? 'selected' : '' }}>
                {{ $yr->label }}
            </option>
            @endforeach
        </select>

        <select name="class_id" onchange="this.form.submit()"
                class="w-[17rem] min-w-[17rem] shrink-0 px-3 py-2 pr-10 border border-gray-200 rounded-lg text-sm
                       focus:outline-none bg-white">
            <option value="">Toutes les classes</option>
            @foreach($classes as $cls)
            <option value="{{ $cls->id }}"
                    {{ request('class_id') == $cls->id ? 'selected' : '' }}>
                {{ $cls->full_name }}
            </option>
            @endforeach
        </select>

        <select name="method" onchange="this.form.submit()"
                class="w-[13rem] min-w-[13rem] shrink-0 px-3 py-2 pr-10 border border-gray-200 rounded-lg text-sm
                       focus:outline-none bg-white">
            <option value="">Tous les modes</option>
            @foreach([
                'cash'          => 'Espèces',
                'orange_money'  => 'Orange Money',
                'mtn_momo'      => 'MTN MoMo',
                'bank_transfer' => 'Virement',
            ] as $val => $lbl)
            <option value="{{ $val }}"
                    {{ request('method') === $val ? 'selected' : '' }}>
                {{ $lbl }}
            </option>
            @endforeach
        </select>

        </div>

        <div class="flex min-w-max flex-nowrap gap-3">

        <select name="responsible" onchange="this.form.submit()"
                class="w-[19rem] min-w-[19rem] shrink-0 px-3 py-2 pr-10 border border-gray-200 rounded-lg text-sm
                       focus:outline-none bg-white">
            @if($isAdmin)
                <option value="global" {{ $selectedResponsible === 'global' ? 'selected' : '' }}>
                    Responsable : Tous
                </option>
                <option value="me" {{ $selectedResponsible === 'me' ? 'selected' : '' }}>
                    Responsable : Moi ({{ auth()->user()->name }})
                </option>
                @foreach($recorders as $recorder)
                    <option value="{{ $recorder->id }}"
                            {{ (string) $selectedResponsible === (string) $recorder->id ? 'selected' : '' }}>
                        Responsable : {{ $recorder->name }}
                    </option>
                @endforeach
            @else
                <option value="me" selected>
                    Responsable : Moi ({{ auth()->user()->name }})
                </option>
            @endif
        </select>

        <div class="relative w-[18rem] min-w-[18rem] shrink-0">
            <input type="text" name="search"
                   value="{{ request('search') }}"
                   placeholder="Nom, matricule, n° reçu..."
                   class="w-full pl-9 pr-4 py-2 border border-gray-200
                          rounded-lg text-sm focus:outline-none">
            <span class="absolute inset-y-0 left-3 flex items-center
                         text-gray-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                     viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          stroke-width="2"
                          d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </span>
        </div>

        @if(request()->hasAny(['year_id','class_id','method','search','responsible']))
        <a href="{{ route('finances.payments') }}"
           class="w-10 min-w-10 shrink-0 px-3 py-2 border border-gray-200 rounded-lg text-sm
                  text-gray-500 hover:bg-gray-50"><svg class="inline h-4 w-4 align-[-2px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></a>
        @endif
        </div>
    </form>
</div>

{{-- ── BARRE RÉSUMÉ ─────────────────────────────────────────────────────── --}}
<div class="flex items-center justify-between mb-4 gap-3 flex-wrap">
    <div class="flex items-center gap-3">
        <p class="text-sm text-gray-500">
            <strong class="text-gray-800">{{ $payments->total() }}</strong>
            paiement(s)
        </p>
        <span class="text-gray-300">|</span>
        <p class="text-sm font-semibold text-green-600">
            Total : {{ number_format($payments->sum('amount_paid') + $payments->sum('scholarship_amount')) }} FCFA
        </p>
    </div>

    {{-- Tout sélectionner / désélectionner --}}
    @if($payments->isNotEmpty())
    <div class="flex items-center gap-2">
        <button @click="selectAll()"
                class="text-xs font-semibold px-3 py-1.5 rounded-lg
                       border border-gray-200 hover:bg-gray-50 transition-colors"
                style="color:#1A3A6B;">
            Tout sélectionner
        </button>
        <button @click="clearSelection()"
                class="text-xs font-medium px-3 py-1.5 rounded-lg
                       border border-gray-200 hover:bg-gray-50 text-gray-500">
            Tout désélectionner
        </button>
    </div>
    @endif
</div>

{{-- ── TABLE ────────────────────────────────────────────────────────────── --}}
<div class="-mx-2 bg-white rounded-2xl shadow-sm border border-gray-100
            overflow-hidden">
    @if($payments->isEmpty())
    <div class="p-12 text-center text-gray-400">
        <p class="text-sm">Aucun paiement trouvé.</p>
    </div>
    @else
    <div class="overflow-x-auto">
        <table class="payments-table w-full min-w-[1100px]">
            <colgroup>
                <col>
                <col class="payment-student-column">
                <col class="payment-installment-column">
                <col class="payment-amount-column">
                <col class="payment-method-column">
                <col class="payment-date-column">
                <col class="payment-receipt-column">
                <col class="payment-actions-column">
            </colgroup>
            <thead>
                <tr style="background-color:#F8FAFC;"
                    class="border-b border-gray-100">
                    {{-- Colonne checkbox --}}
                    <th class="px-4 py-3.5 w-10">
                        <input type="checkbox"
                               @change="toggleAllVisible($event.target.checked)"
                               class="w-4 h-4 rounded cursor-pointer"
                               style="accent-color:#1A3A6B;"
                               title="Sélectionner/désélectionner la page">
                    </th>
                    <th class="text-left px-4 py-3.5 text-xs font-semibold
                               text-gray-400 uppercase tracking-wider">
                        Élève
                    </th>
                    <th class="text-left px-4 py-3.5 text-xs font-semibold
                               text-gray-400 uppercase tracking-wider
                               ">
                        Tranche
                    </th>
                    <th class="text-right px-4 py-3.5 text-xs font-semibold
                               text-gray-400 uppercase tracking-wider">
                        Montant
                    </th>
                    <th class="text-left px-4 py-3.5 text-xs font-semibold
                               text-gray-400 uppercase tracking-wider
                               ">
                        Mode
                    </th>
                    <th class="text-left px-4 py-3.5 text-xs font-semibold
                               text-gray-400 uppercase tracking-wider
                               ">
                        Date
                    </th>
                    <th class="payment-receipt-column text-left px-4 py-3.5 text-xs font-semibold
                               text-gray-400 uppercase tracking-wider
                               ">
                        N° Reçu
                    </th>
                    <th class="payment-actions-column text-right px-4 py-3.5 text-xs font-semibold
                               text-gray-400 uppercase tracking-wider">
                        Actions
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($payments as $p)
                <tr class="hover:bg-gray-50/50 transition-colors"
                    :class="selected.has('{{ $p->id }}')
                        ? 'bg-blue-50/40' : ''">
                    {{-- Checkbox --}}
                    <td class="px-4 py-3.5 text-center">
                        <input type="checkbox"
                               value="{{ $p->id }}"
                               :checked="selected.has('{{ $p->id }}')"
                               @change="toggle('{{ $p->id }}', $event.target.checked)"
                               class="payment-checkbox w-4 h-4 rounded cursor-pointer"
                               style="accent-color:#1A3A6B;">
                    </td>
                    <td class="px-4 py-3.5">
                        <a href="{{ route('finances.student',
                                         $p->studentEnrollment) }}"
                           class="hover:underline">
                            <p class="text-sm font-semibold text-gray-800">
                                {{ $p->studentEnrollment?->student?->full_name }}
                            </p>
                            <p class="text-xs text-gray-400">
                                {{ $p->studentEnrollment?->classGroup?->full_name }}
                            </p>
                        </a>
                    </td>
                    <td class="px-4 py-3.5 text-sm text-gray-700 font-medium
                               ">
                        {{ $p->is_bulk ? 'Paiement en bloc' : ($p->feeInstallment?->label ?? '—') }}
                    </td>
                    <td class="px-4 py-3.5 text-right">
                        <span class="font-bold text-green-700 text-sm">
                            {{ number_format($p->amount_paid + ($p->scholarship_amount ?? 0)) }}
                        </span>
                        <span class="text-xs text-gray-400 ml-0.5">FCFA</span>
                    </td>
                    <td class="px-4 py-3.5 text-sm text-gray-600">
                        {{ $p->payment_method_label }}
                    </td>
                    <td class="px-4 py-3.5 text-sm text-gray-600">
                        {{ $p->payment_date->format('d/m/Y') }}
                    </td>
                    <td class="payment-receipt-column px-4 py-3.5">
                        <span class="font-mono text-xs font-bold"
                              style="color:#1A3A6B;">
                            {{ $p->receipt_number }}
                        </span>
                    </td>
                    <td class="payment-actions-column px-4 py-3.5 text-right">
                        <a href="{{ route('finances.receipt', $p) }}"
                           target="_blank"
                           class="p-1.5 rounded-lg text-gray-400
                                  hover:text-blue-700 hover:bg-blue-50
                                  transition-colors inline-flex"
                           title="Voir le reçu individuel">
                            <svg class="w-4 h-4" fill="none"
                                 stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round"
                                      stroke-linejoin="round" stroke-width="2"
                                      d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2
                                         2 0 012-2h5.586a1 1 0 01.707.293l5.414
                                         5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($payments->hasPages())
    <div class="px-5 py-3 border-t border-gray-100">
        {{ $payments->links() }}
    </div>
    @endif
    @endif
</div>

</div>{{-- end x-data --}}

<script>
// IDs de la page courante
const pageIds = @json($payments->pluck('id')->map(fn($id) => (string)$id));

function paymentsManager() {
    return {
        selected: new Set(),

        toggle(id, checked) {
            if (checked) this.selected.add(id);
            else         this.selected.delete(id);
        },

        toggleAllVisible(checked) {
            pageIds.forEach(id => {
                if (checked) this.selected.add(id);
                else         this.selected.delete(id);
            });
            // Forcer la mise à jour des checkboxes
            document.querySelectorAll('.payment-checkbox').forEach(cb => {
                cb.checked = checked;
            });
        },

        selectAll() {
            pageIds.forEach(id => this.selected.add(id));
            document.querySelectorAll('.payment-checkbox').forEach(cb => {
                cb.checked = true;
            });
        },

        clearSelection() {
            this.selected.clear();
            document.querySelectorAll('.payment-checkbox').forEach(cb => {
                cb.checked = false;
            });
        },

        printSelected() {
            if (this.selected.size === 0) return;
            const ids = Array.from(this.selected).join(',');
            window.open(
                `{{ route('finances.receipts.batch') }}?ids=${ids}`,
                '_blank'
            );
        }
    }
}
</script>

@endsection
