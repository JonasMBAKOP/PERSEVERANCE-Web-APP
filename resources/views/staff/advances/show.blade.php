@extends('layouts.app')
@section('title', 'Bon sur salaire #' . $advance->id)
@section('page-title', 'Bon sur salaire #' . $advance->id)
@section('page-subtitle', $advance->staff?->full_name ?? 'Personnel archivé')
@section('content')
<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('staff.advances.index') }}" class="text-sm font-semibold text-[#1A3A6B] hover:underline">← Retour aux bons</a>
        <div class="flex items-center gap-2">
            <a href="{{ route('staff.advances.edit', $advance) }}" class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Modifier</a>
            @if($advance->repayments->isEmpty())
                <form method="POST" action="{{ route('staff.advances.destroy', $advance) }}" onsubmit="return confirm('Supprimer ce bon sur salaire ?')">
                    @csrf @method('DELETE')
                    <button class="rounded-lg border border-red-200 bg-white px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-50">Supprimer</button>
                </form>
            @endif
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-5"><p class="text-xs font-bold uppercase tracking-wide text-slate-500">Montant accordé</p><p class="mt-2 text-2xl font-black text-[#1A3A6B]">{{ number_format($advance->amount, 0, ',', ' ') }} FCFA</p></div>
        <div class="rounded-xl border border-slate-200 bg-white p-5"><p class="text-xs font-bold uppercase tracking-wide text-slate-500">Total remboursé</p><p class="mt-2 text-2xl font-black text-emerald-700">{{ number_format($advance->repaid_amount, 0, ',', ' ') }} FCFA</p></div>
        <div class="rounded-xl border border-slate-200 bg-white p-5"><p class="text-xs font-bold uppercase tracking-wide text-slate-500">Solde restant</p><p class="mt-2 text-2xl font-black text-amber-700">{{ number_format($advance->balance, 0, ',', ' ') }} FCFA</p></div>
    </div>

    @if(session('success'))<div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800">{{ session('error') }}</div>@endif

    <div class="grid gap-6 lg:grid-cols-[1fr_1.2fr]">
        <section class="rounded-xl border border-slate-200 bg-white p-5">
            <h2 class="font-bold text-slate-900">Détails du bon</h2>
            <dl class="mt-4 divide-y divide-slate-100 text-sm">
                <div class="flex justify-between gap-4 py-3"><dt class="text-slate-500">Personnel</dt><dd class="text-right font-semibold text-slate-800">{{ $advance->staff?->full_name ?? 'Personnel archivé' }}</dd></div>
                <div class="flex justify-between gap-4 py-3"><dt class="text-slate-500">Date</dt><dd class="font-semibold text-slate-800">{{ $advance->issued_on->format('d/m/Y') }}</dd></div>
                <div class="flex justify-between gap-4 py-3"><dt class="text-slate-500">Motif</dt><dd class="text-right font-semibold text-slate-800">{{ $advance->purpose ?: '—' }}</dd></div>
                <div class="py-3"><dt class="text-slate-500">Notes</dt><dd class="mt-1 whitespace-pre-line text-slate-800">{{ $advance->notes ?: '—' }}</dd></div>
            </dl>
        </section>

        @if($advance->balance > 0)
        <section class="rounded-xl border border-slate-200 bg-white p-5">
            <h2 class="font-bold text-slate-900">Enregistrer un remboursement</h2>
            <p class="mt-1 text-sm text-slate-500">Le montant ne peut pas dépasser le solde restant.</p>
            <form method="POST" action="{{ route('staff.advances.repayments.store', $advance) }}" class="mt-4 grid gap-4 sm:grid-cols-2">
                @csrf
                <div>
                    <label for="amount" class="mb-1 block text-sm font-semibold text-slate-700">Montant (FCFA)</label>
                    <input id="amount" name="amount" type="number" min="1" max="{{ $advance->balance }}" step="1" required value="{{ old('amount') }}" class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm">
                    @error('amount')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="repaid_on" class="mb-1 block text-sm font-semibold text-slate-700">Date du paiement</label>
                    <input id="repaid_on" name="repaid_on" type="date" max="{{ now()->toDateString() }}" required value="{{ old('repaid_on', now()->toDateString()) }}" class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm">
                    @error('repaid_on')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="payment_method" class="mb-1 block text-sm font-semibold text-slate-700">Mode de paiement</label>
                    <select id="payment_method" name="payment_method" required class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm">
                        <option value="cash" @selected(old('payment_method', 'cash') === 'cash')>Espèces</option>
                        <option value="bank_transfer" @selected(old('payment_method') === 'bank_transfer')>Virement</option>
                        <option value="mobile_money" @selected(old('payment_method') === 'mobile_money')>Mobile Money</option>
                        <option value="other" @selected(old('payment_method') === 'other')>Autre</option>
                    </select>
                    @error('payment_method')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="reference" class="mb-1 block text-sm font-semibold text-slate-700">Référence</label>
                    <input id="reference" name="reference" maxlength="100" value="{{ old('reference') }}" class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm">
                    @error('reference')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="sm:col-span-2">
                    <label for="notes" class="mb-1 block text-sm font-semibold text-slate-700">Notes</label>
                    <textarea id="notes" name="notes" rows="2" maxlength="5000" class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm">{{ old('notes') }}</textarea>
                    @error('notes')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="sm:col-span-2"><button type="submit" class="w-full rounded-lg bg-emerald-700 px-4 py-2.5 text-sm font-bold text-white hover:bg-emerald-800">Enregistrer le remboursement</button></div>
            </form>
        </section>
        @endif
    </div>

    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white">
        <div class="border-b border-slate-100 px-5 py-4"><h2 class="font-bold text-slate-900">Historique des remboursements</h2></div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wide text-slate-500"><tr><th class="px-4 py-3">Date</th><th class="px-4 py-3">Mode</th><th class="px-4 py-3">Référence</th><th class="px-4 py-3">Enregistré par</th><th class="px-4 py-3 text-right">Montant</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($advance->repayments as $repayment)
                        <tr><td class="px-4 py-3">{{ $repayment->repaid_on->format('d/m/Y') }}</td><td class="px-4 py-3">{{ ['cash' => 'Espèces', 'bank_transfer' => 'Virement', 'mobile_money' => 'Mobile Money', 'other' => 'Autre', 'salary_deduction' => 'Retenue sur salaire'][$repayment->payment_method] ?? $repayment->payment_method }}</td><td class="px-4 py-3">{{ $repayment->reference ?: '—' }}</td><td class="px-4 py-3">{{ $repayment->recordedBy?->name ?? '—' }}</td><td class="whitespace-nowrap px-4 py-3 text-right font-semibold">{{ number_format($repayment->amount, 0, ',', ' ') }} FCFA</td></tr>
                        @if($repayment->notes)<tr><td colspan="5" class="px-4 pb-3 pt-0 text-xs text-slate-500">Note : {{ $repayment->notes }}</td></tr>@endif
                    @empty
                        <tr><td colspan="5" class="px-4 py-10 text-center text-slate-500">Aucun remboursement enregistré.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
