@extends('layouts.app')
@section('title', 'Nouveau bon sur salaire')
@section('page-title', 'Nouveau bon sur salaire')
@section('page-subtitle', 'Enregistrer une avance accordée à un membre du personnel')
@section('content')
<div class="mx-auto max-w-3xl rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
    <form method="POST" action="{{ route('staff.advances.store') }}" class="space-y-6">
        @csrf
        @include('staff.advances._form')
        <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:justify-between">
            <a href="{{ route('staff.advances.index') }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-center text-sm font-semibold text-slate-700 hover:bg-slate-50">Annuler</a>
            <button type="submit" class="rounded-xl bg-[#1A3A6B] px-5 py-2.5 text-sm font-bold text-white hover:bg-[#142f54]">Enregistrer le bon</button>
        </div>
    </form>
</div>
@endsection
