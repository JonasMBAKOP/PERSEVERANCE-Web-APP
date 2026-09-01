@extends('layouts.app')

@section('title', 'Compte — ' . $enrollment->student->full_name)
@section('page-title', 'Compte Financier')
@section('page-subtitle'){{ $enrollment->student->full_name }}@endsection

@section('breadcrumb')
    <a href="{{ route('finances.index') }}" class="hover:text-gray-700">
        Finances
    </a>
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round"
              stroke-width="2" d="M9 5l7 7-7 7"/>
    </svg>
    <span style="color:#1A3A6B;" class="font-medium">
        {{ $enrollment->student->full_name }}
    </span>
@endsection

@section('content')

<div class="student-finance-page -mx-2 -mt-2 -mb-2 lg:-mx-4 lg:-mt-4 lg:-mb-4">


{{-- ── EN-TÊTE ÉLÈVE ────────────────────────────────────────────────────── --}}
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 mb-5
            flex w-full max-w-full flex-col overflow-hidden md:grid
            md:grid-cols-[minmax(0,auto)_minmax(0,1fr)_auto] md:items-center md:gap-4">
    <div class="flex min-w-0 items-center gap-4 md:col-start-1">
        @if($enrollment->student->photo)
        <img src="{{ $enrollment->student->photo_url }}"
             class="w-14 h-14 rounded-full object-cover ring-2 ring-gray-100
                    flex-shrink-0">
        @else
        <div class="w-14 h-14 rounded-full flex items-center justify-center
                    text-white font-black text-xl flex-shrink-0"
             style="background-color:#1A3A6B;">
            {{ strtoupper(substr($enrollment->student->last_name, 0, 1))
               . strtoupper(substr($enrollment->student->first_name, 0, 1)) }}
        </div>
        @endif
        <div>
            <p class="font-black text-lg" style="color:#1A3A6B;">
                <span class="block">{{ $enrollment->student->last_name }}</span>
                <span class="block">{{ $enrollment->student->first_name }}</span>
            </p>
            <p class="text-sm text-gray-500">
                {{ $enrollment->student->matricule }}
                · {{ $enrollment->classGroup->full_name }}
                · {{ $enrollment->academicYear->label }}
            </p>
        </div>
    </div>

    <div class="flex w-full min-w-0 flex-col gap-3 md:contents">
        <div class="flex w-full min-w-0 items-center justify-center gap-0 md:col-start-2 md:w-auto md:justify-self-center">
        <div class="min-w-0 flex-1 text-center px-2" style="white-space:nowrap;padding-left:2rem;padding-right:2rem;">
            <p class="text-xs text-gray-400">Total dû</p>
            <p class="font-bold" style="color:#1A3A6B;">
                {{ number_format($totalDue) }}
                <span class="block text-xs font-normal text-gray-400">FCFA</span>
            </p>
        </div>
        <div class="min-w-0 flex-1 text-center px-2 border-l border-gray-200" style="padding-left:2rem;padding-right:2rem;">
            <p class="text-xs text-gray-400">Payé</p>
            <p class="font-bold text-green-600">
                {{ number_format($totalPaid) }}
                <span class="block text-xs font-normal text-gray-400">FCFA</span>
            </p>
        </div>
        <div class="min-w-0 flex-1 text-center px-2 border-l border-gray-200" style="padding-left:2rem;padding-right:2rem;">
            <p class="text-xs text-gray-400">Bourse</p>
            <p class="font-bold text-indigo-600">
                {{ number_format($totalScholarship ?? 0) }}
                <span class="block text-xs font-normal text-gray-400">FCFA</span>
            </p>
        </div>
        <div class="min-w-0 flex-1 text-center px-2 border-l border-gray-200" style="padding-left:2rem;padding-right:2rem;">
            <p class="text-xs text-gray-400">Restant</p>
            <p class="font-bold {{ $totalRemaining > 0
                ? 'text-red-500' : 'text-green-600' }}">
                {{ number_format($totalRemaining) }}
                <span class="block text-xs font-normal text-gray-400">FCFA</span>
            </p>
        </div>
        </div>

        <div class="flex w-full min-w-0 flex-wrap items-center gap-2 md:col-start-3 md:w-auto md:flex-nowrap md:justify-self-end">
        @can('manage-finances')
        @if($feeStructure)
        <button type="button"
                onclick="openBulkPaymentModal()"
                @if($totalRemaining <= 0) disabled @endif
                class="flex items-center gap-2 whitespace-nowrap px-4 py-2 rounded-lg text-white text-sm font-bold transition-all hover:shadow-md {{ $totalRemaining <= 0 ? 'opacity-40 cursor-not-allowed' : '' }}"
                style="background-color:#E87722;">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 1.343-3 3s1.343 3 3 3 3-1.343 3-3-1.343-3-3-3zm0 10c-4.418 0-8-2.239-8-5s3.582-5 8-5 8 2.239 8 5-3.582 5-8 5zm0-12V3m0 18v-3"/>
            </svg>
            Payer<br class="hidden sm:block"> en bloc
        </button>
        @endif
        @endcan
        <a href="{{ route('finances.student.receipt', $enrollment) }}"
            target="_blank"
            class="flex items-center gap-2 whitespace-nowrap px-4 py-2 rounded-lg text-white
                    text-sm font-bold transition-all hover:shadow-md"
            style="background-color:#1A3A6B;">
            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1
                        0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            Reçu<br class="hidden sm:block"> global
        </a>
        </div>
    </div>
</div>

@if(!$feeStructure)
<div class="bg-amber-50 border border-amber-200 rounded-xl p-5 mb-5">
    <p class="text-sm text-amber-700 font-medium">
        <svg class="inline h-4 w-4 mr-1 align-[-2px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>Aucune structure de frais n'est configurée pour cette classe.
    </p>
    @can('configure-fees')
    <a href="{{ route('finances.fees', $enrollment->classGroup) }}"
       class="inline-block mt-2 text-sm font-medium hover:underline"
       style="color:#E87722;">
        → Configurer les frais de {{ $enrollment->classGroup->full_name }}
    </a>
    @endcan
</div>
@else

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

    {{-- ── Tranches + Formulaire paiement ─────────────────────────────── --}}
    <div class="lg:col-span-2 space-y-4">

        @foreach($installments as $item)
        @php
            $inst     = $item['installment'];
            $paid     = $item['paid'];
            $remaining= $item['remaining'];
            $status   = $item['status'];
            $pct      = $inst->amount > 0
                ? min(round(($paid / $inst->amount) * 100), 100) : 0;

            $statusConf = [
                'paid'    => ['bg' => '#D1FAE5', 'text' => '#065F46',
                              'label' => 'Soldée'],
                'partial' => ['bg' => '#FEF3C7', 'text' => '#92400E',
                              'label' => '◑ Partielle'],
                'unpaid'  => ['bg' => '#FEE2E2', 'text' => '#991B1B',
                              'label' => 'Non payée'],
            ];
            $sc = $statusConf[$status];
        @endphp

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5
                    {{ $status === 'paid' ? 'border-l-4 border-green-400' : '' }}">
            <div class="flex items-start justify-between gap-3 mb-4">
                <div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <h3 class="font-semibold text-gray-800">
                            {{ $inst->label }}
                        </h3>
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium"
                              style="background-color:{{ $sc['bg'] }};
                                     color:{{ $sc['text'] }};">
                            {{ $sc['label'] }}
                        </span>
                    </div>
                    @if($inst->due_date_start && $inst->due_date_end)
                    <p class="text-xs text-gray-400 mt-0.5">
                        Période : {{ $inst->due_date_start->format('d/m/Y') }}
                        → {{ $inst->due_date_end->format('d/m/Y') }}
                    </p>
                    @endif
                </div>
                <div class="text-right flex-shrink-0">
                    <p class="text-sm text-gray-400">Montant</p>
                    <p class="font-bold text-lg" style="color:#1A3A6B;">
                        {{ number_format($inst->amount) }}
                        <span class="text-xs font-normal text-gray-400">FCFA</span>
                    </p>
                </div>
            </div>

            {{-- Barre de progression --}}
            <div class="mb-3">
                <div class="flex justify-between text-xs text-gray-500 mb-1">
                    <span>Payé : {{ number_format($paid) }} FCFA</span>
                    <span>Reste : {{ number_format($remaining) }} FCFA</span>
                </div>
                <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full rounded-full transition-all"
                         style="width:{{ $pct }}%;
                                background-color:{{ $status === 'paid'
                                    ? '#1A5C2A' : ($status === 'partial'
                                    ? '#C8A415' : '#EF4444') }}">
                    </div>
                </div>
            </div>

            {{-- Formulaire paiement rapide (si pas soldée) --}}
            @if($status !== 'paid')
            @can('manage-finances')
            <form method="POST"
                  action="{{ route('finances.pay', $enrollment) }}"
                  class="flex flex-wrap items-end gap-3 pt-3
                         border-t border-gray-100">
                @csrf
                <input type="hidden" name="fee_installment_id"
                       value="{{ $inst->id }}">

                <div class="w-32 min-w-32 flex-none">
                    <label class="block text-xs text-gray-500 mb-1">
                        Montant (FCFA)
                    </label>
                    <input type="number" name="amount_paid"
                           value="0"
                           min="0" step="500" max="{{ $remaining }}"
                           class="w-full px-3 py-2 border border-gray-200
                                  rounded-lg text-sm font-mono focus:outline-none
                                  focus:ring-2 focus:ring-blue-100">
                </div>

                <div class="w-44 min-w-44 flex-none">
                    <label class="block text-xs text-gray-500 mb-1">Mode</label>
                    <select name="payment_method"
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg
                                   text-sm focus:outline-none bg-white">
                        <option value="cash">Espèces</option>
                        <option value="orange_money">Orange Money</option>
                        <option value="mtn_momo">MTN MoMo</option>
                        <option value="bank_transfer">Virement</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs text-gray-500 mb-1">Date</label>
                    <input type="date" name="payment_date"
                           value="{{ date('Y-m-d') }}"
                           class="px-3 py-2 border border-gray-200 rounded-lg
                                  text-sm focus:outline-none">
                </div>

                <button type="submit"
                        class="px-4 py-2 rounded-lg text-white text-sm
                               font-semibold whitespace-nowrap"
                        style="background-color:#1A5C2A;">
                    Enregistrer
                </button>
            </form>
            @endcan
            @endif
        </div>
        @endforeach

    </div>

    {{-- ── Historique des paiements ─────────────────────────────────── --}}
    <div class="space-y-4 h-full lg:col-span-1 flex flex-col">

        {{-- Résumé --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <h3 class="text-sm font-semibold uppercase tracking-wider
                       text-gray-400 mb-4 pb-2 border-b border-gray-100">
                Résumé
            </h3>
            @php
                $effectivePaid = $totalPaid + ($totalScholarship ?? 0);
                $globalPct = $totalDue > 0
                    ? min(round(($effectivePaid / $totalDue) * 100), 100)
                    : 0;
            @endphp
            <div class="space-y-3">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Total dû</span>
                    <span class="font-semibold" style="color:#1A3A6B;">
                        {{ number_format($totalDue) }} FCFA
                    </span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Total payé + bourse</span>
                    <span class="font-semibold text-green-600">
                        {{ number_format($totalPaid + ($totalScholarship ?? 0)) }} FCFA
                    </span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Restant</span>
                    <span class="font-semibold
                                 {{ $totalRemaining > 0
                                     ? 'text-red-500' : 'text-green-600' }}">
                        {{ number_format($totalRemaining) }} FCFA
                    </span>
                </div>
                <div>
                    <div class="flex justify-between text-xs text-gray-400 mb-1">
                        <span>Progression</span>
                        <span>{{ $globalPct }}%</span>
                    </div>
                    <div class="h-2.5 bg-gray-100 rounded-full overflow-hidden">
                        <div class="h-full rounded-full"
                             style="width:{{ $globalPct }}%;
                                    background-color:{{ $globalPct >= 100
                                        ? '#1A5C2A' : ($globalPct >= 50
                                        ? '#C8A415' : '#EF4444') }}">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Historique --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 flex-1 flex flex-col">
            <div class="px-4 py-3 border-b border-gray-100">
                <h3 class="text-sm font-semibold uppercase tracking-wider
                           text-gray-400">
                    Historique ({{ $payments->count() }})
                </h3>
            </div>

            @if($payments->isEmpty())
            <p class="px-4 py-6 text-sm text-gray-400 italic text-center">
                Aucun paiement enregistré.
            </p>
            @else
            <div class="divide-y divide-gray-50 flex-1 overflow-y-auto min-h-0">
                @foreach($payments as $p)
                <div class="px-4 py-3">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-green-600">
                                +{{ number_format($p->effective_amount_paid) }} FCFA
                            </p>
                            <p class="text-xs text-gray-500">
                                {{ $p->is_bulk ? 'Paiement en bloc' : ($p->feeInstallment?->label ?? '—') }}
                            </p>
                            @if($p->is_bulk)
                            <p class="text-[11px] text-gray-400 mt-1">
                                {{ $p->allocation_summary ?: 'Répartition automatique des tranches' }}
                            </p>
                            @endif
                            <p class="text-xs text-gray-400">
                                {{ $p->payment_method_label }}
                                @if($p->reference)
                                · {{ $p->reference }}
                                @endif
                            </p>
                        </div>
                        <div class="text-right flex-shrink-0">
                            <p class="text-xs text-gray-500">
                                {{ $p->payment_date->format('d/m/Y') }}
                            </p>
                            <a href="{{ route('finances.receipt', $p) }}"
                               target="_blank"
                               class="text-xs hover:underline"
                               style="color:#1A3A6B;">
                                #{{ $p->receipt_number }}
                            </a>
                            @can('manage-finances')
                            <form method="POST" action="{{ route('finances.payment.delete', $p) }}"
                                  onsubmit="return confirm('Confirmer la suppression de ce paiement ? Cette action recalculera les soldes financiers et ne peut pas être annulée.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="mt-1 text-xs font-semibold text-red-600 hover:text-red-800 hover:underline">
                                    Supprimer
                                </button>
                            </form>
                            @endcan
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>

    </div>

</div>
@endif

@can('manage-finances')
@if($feeStructure)
<div id="bulk-payment-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 px-4">
    <div class="w-full max-w-md rounded-2xl bg-white p-5 shadow-xl">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-lg font-black" style="color:#1A3A6B;">Paiement en bloc</h3>
                <p class="text-sm text-gray-500">Le montant sera réparti automatiquement sur les tranches ouvertes.</p>
            </div>
            <button type="button" onclick="closeBulkPaymentModal()" class="p-2 rounded-lg text-gray-400 hover:bg-gray-100">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <form id="bulk-payment-form" method="POST" action="{{ route('finances.bulk-pay', $enrollment) }}" class="space-y-4">
            @csrf
            <div id="bulk-payment-error" class="hidden rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Montant à payer (FCFA)</label>
                <input type="number" name="amount_paid" min="0" value="0"
                       class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-100">
            </div>

            <div class="flex items-center gap-3">
                <label class="inline-flex items-center gap-2 text-sm text-gray-600">
                    <input type="checkbox" id="scholarship-toggle" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    <span>Bourse appliquée</span>
                </label>
            </div>

            <div id="scholarship-block" class="hidden">
                <label class="block text-xs text-gray-500 mb-1">Montant de la bourse (FCFA)</label>
                <input type="number" name="scholarship_amount" min="0" value="0"
                       class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-100">
                <p class="text-xs text-gray-400 mt-1">La bourse réduit le reste dû sans entrer d'argent comptant.</p>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="closeBulkPaymentModal()" class="px-4 py-2 rounded-lg border border-gray-200 text-sm font-semibold text-gray-600 hover:bg-gray-50">Annuler</button>
                <button type="submit" class="px-4 py-2 rounded-lg text-white text-sm font-semibold" style="background-color:#1A5C2A;">Payer</button>
            </div>
        </form>
    </div>
</div>
@endif
@endcan

<script>
function openBulkPaymentModal() {
    const modal = document.getElementById('bulk-payment-modal');
    if (modal) {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        const input = modal.querySelector('input[name="amount_paid"]');
        const scholarshipToggle = modal.querySelector('#scholarship-toggle');
        const scholarshipBlock = modal.querySelector('#scholarship-block');
        if (input) {
            input.value = '0';
            input.focus();
        }
        if (scholarshipToggle) {
            scholarshipToggle.checked = false;
        }
        if (scholarshipBlock) {
            scholarshipBlock.classList.add('hidden');
        }
    }
}

function closeBulkPaymentModal() {
    const modal = document.getElementById('bulk-payment-modal');
    if (modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
}

function toggleScholarshipBlock() {
    const checkbox = document.getElementById('scholarship-toggle');
    const block = document.getElementById('scholarship-block');
    if (!checkbox || !block) {
        return;
    }

    if (checkbox.checked) {
        block.classList.remove('hidden');
    } else {
        block.classList.add('hidden');
        const scholarshipInput = block.querySelector('input[name="scholarship_amount"]');
        if (scholarshipInput) {
            scholarshipInput.value = '0';
        }
    }
}

document.getElementById('scholarship-toggle')?.addEventListener('change', toggleScholarshipBlock);

document.getElementById('bulk-payment-form')?.addEventListener('submit', function (event) {
    event.preventDefault();

    const form = event.target;
    const action = form.action;
    const formData = new FormData(form);
    const errorBox = document.getElementById('bulk-payment-error');

    if (errorBox) {
        errorBox.classList.add('hidden');
        errorBox.textContent = '';
    }

    const newTab = window.open('', '_blank');
    if (!newTab) {
        alert('Impossible d’ouvrir un nouvel onglet; vérifiez votre bloqueur de fenêtres contextuelles.');
        return;
    }

    fetch(action, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
        body: formData,
    })
    .then(async response => {
        const contentType = response.headers.get('content-type') || '';
        if (!response.ok) {
            let message = 'Erreur lors du paiement.';
            if (contentType.includes('application/json')) {
                const data = await response.json();
                message = data.error || message;
            } else {
                message = await response.text();
            }
            throw new Error(message);
        }

        const data = contentType.includes('application/json') ? await response.json() : null;
        const receiptUrl = data?.receipt_url || response.url;
        if (receiptUrl) {
            newTab.location.href = receiptUrl;
        } else {
            newTab.location.href = response.url;
        }
        window.location.reload();
    })
    .catch(error => {
        if (newTab && !newTab.closed) {
            newTab.close();
        }

        if (errorBox) {
            errorBox.textContent = error.message;
            errorBox.classList.remove('hidden');
            return;
        }

        alert(error.message);
    });
});

document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
        closeBulkPaymentModal();
    }
});

</script>

</div>

@endsection
