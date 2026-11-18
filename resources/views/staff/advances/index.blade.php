@extends('layouts.app')

@section('title', 'Bons sur salaire')
@section('page-title', 'Bons sur salaire')
@section('page-subtitle', 'Suivi des avances accordées et des remboursements du personnel')

@section('breadcrumb')
    <a href="{{ route('staff.index') }}" class="hover:text-gray-700">Personnel</a>
    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="font-semibold text-[#1A3A6B]">Bons sur salaire</span>
@endsection

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <form method="GET" action="{{ route('staff.advances.index') }}" class="flex w-full flex-col gap-3 sm:flex-row sm:items-end sm:max-w-2xl">
            <div class="flex-1">
                <label for="advance-search" class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500">Rechercher un membre</label>
                <input id="advance-search" name="search" value="{{ request('search') }}" placeholder="Nom ou téléphone" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:border-[#1A3A6B] focus:outline-none focus:ring-2 focus:ring-blue-100">
            </div>
            <button type="submit" class="rounded-xl bg-[#1A3A6B] px-4 py-2.5 text-sm font-bold text-white">Rechercher</button>
        </form>
        <a href="{{ route('staff.advances.create') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#A35200] px-4 py-2.5 text-sm font-bold text-white hover:bg-[#874400]">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
            Nouveau bon
        </a>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-5"><p class="text-xs font-bold uppercase tracking-wide text-slate-500">Total accordé</p><p class="mt-2 text-2xl font-black text-[#1A3A6B]">{{ number_format($totalAmount, 0, ',', ' ') }} FCFA</p></div>
        <div class="rounded-xl border border-slate-200 bg-white p-5"><p class="text-xs font-bold uppercase tracking-wide text-slate-500">Total remboursé</p><p class="mt-2 text-2xl font-black text-emerald-700">{{ number_format($totalRepaid, 0, ',', ' ') }} FCFA</p></div>
        <div class="rounded-xl border border-slate-200 bg-white p-5"><p class="text-xs font-bold uppercase tracking-wide text-slate-500">Solde restant</p><p class="mt-2 text-2xl font-black text-amber-700">{{ number_format($totalBalance, 0, ',', ' ') }} FCFA</p></div>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wide text-slate-500">
                    <tr><th class="px-4 py-3">Date</th><th class="px-4 py-3">Personnel</th><th class="px-4 py-3 text-right">Montant du bon</th><th class="px-4 py-3 text-right">Remboursé</th><th class="px-4 py-3 text-right">Solde</th><th class="px-4 py-3">État</th><th class="px-4 py-3 text-right">Action</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($advances as $advance)
                        <tr class="hover:bg-slate-50">
                            <td class="whitespace-nowrap px-4 py-3">{{ $advance->issued_on->format('d/m/Y') }}</td>
                            <td class="px-4 py-3"><a class="font-semibold text-[#1A3A6B] hover:underline" href="{{ route('staff.advances.show', $advance) }}">{{ $advance->staff?->full_name ?? 'Personnel archivé' }}</a><p class="mt-0.5 text-xs text-slate-500">{{ $advance->purpose ?: '—' }}</p></td>
                            <td class="whitespace-nowrap px-4 py-3 text-right font-semibold">{{ number_format($advance->amount, 0, ',', ' ') }} FCFA</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-emerald-700">{{ number_format($advance->repaid_amount, 0, ',', ' ') }} FCFA</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right font-bold text-amber-700">{{ number_format($advance->balance, 0, ',', ' ') }} FCFA</td>
                            <td class="px-4 py-3">
                                @if($advance->balance === 0)
                                    <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700">Remboursé</span>
                                @elseif($advance->repaid_amount > 0)
                                    <span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-bold text-blue-700">Partiel</span>
                                @else
                                    <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-bold text-amber-700">À rembourser</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right"><a href="{{ route('staff.advances.show', $advance) }}" class="font-semibold text-[#1A3A6B] hover:underline">Détails</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-12 text-center text-slate-500">Aucun bon sur salaire enregistré.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($advances->hasPages())<div class="border-t border-slate-100 px-4 py-3">{{ $advances->links() }}</div>@endif
    </div>
</div>
@endsection
