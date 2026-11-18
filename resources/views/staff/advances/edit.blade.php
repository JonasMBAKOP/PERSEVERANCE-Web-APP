@extends('layouts.app')
@section('title', 'Modifier le bon sur salaire')
@section('page-title', 'Modifier le bon sur salaire')
@section('page-subtitle', $advance->staff?->full_name ?? 'Personnel archivé')
@section('content')
<div class="mx-auto max-w-3xl rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
    @if($advance->repaid_amount > 0)
        <div class="mb-5 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">Le montant du bon ne peut pas être inférieur au total déjà remboursé ({{ number_format($advance->repaid_amount, 0, ',', ' ') }} FCFA).</div>
    @endif
    <form method="POST" action="{{ route('staff.advances.update', $advance) }}" class="space-y-6">
        @csrf
        @method('PUT')
        @include('staff.advances._form')
        <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:justify-between">
            <a href="{{ route('staff.advances.show', $advance) }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-center text-sm font-semibold text-slate-700 hover:bg-slate-50">Annuler</a>
            <button type="submit" class="rounded-xl bg-[#1A3A6B] px-5 py-2.5 text-sm font-bold text-white hover:bg-[#142f54]">Enregistrer les modifications</button>
        </div>
    </form>
</div>
@endsection
