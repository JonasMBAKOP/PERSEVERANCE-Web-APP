@extends('layouts.app')

@section('title', 'Modifier le salaire — ' . $staff->full_name)
@section('page-title', 'Modifier le salaire')
@section('page-subtitle'){{ $staff->full_name }}@endsection

@section('breadcrumb')
    <a href="{{ route('staff.salaries') }}" class="hover:text-gray-700">
        Salaires
    </a>
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
    </svg>
    <span style="color:#1A3A6B;" class="font-medium">{{ $staff->full_name }}</span>
@endsection

@section('content')
<div class="max-w-5xl mx-auto space-y-6">
    <div class="grid gap-6 xl:grid-cols-[1.05fr_0.95fr]">
        <div class="rounded-3xl border border-gray-100 bg-white shadow-sm overflow-hidden">
            <div class="px-6 py-6 border-b border-gray-100">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-xs uppercase tracking-[0.24em] text-slate-500">Fiche salariale</p>
                        <h1 class="mt-2 text-2xl font-semibold text-slate-900">{{ $staff->full_name }}</h1>
                    </div>
                    <span class="inline-flex items-center rounded-full bg-blue-50 px-4 py-2 text-sm font-semibold text-blue-700">
                        {{ $staff->contract_label }}
                    </span>
                </div>
            </div>

            <div class="grid gap-6 p-6 sm:grid-cols-2">
                <div class="rounded-3xl border border-gray-100 bg-slate-50 p-5">
                    <p class="text-sm font-semibold text-slate-500 uppercase tracking-[0.16em] mb-3">Statut</p>
                    <p class="text-lg font-semibold text-slate-900">{{ $staff->contract_label }}</p>
                    <p class="mt-2 text-sm text-slate-500">Type de contrat enregistré pour ce membre du personnel.</p>
                </div>
                <div class="rounded-3xl border border-gray-100 bg-slate-50 p-5">
                    <p class="text-sm font-semibold text-slate-500 uppercase tracking-[0.16em] mb-3">Salaire actuel</p>
                    <p class="text-lg font-semibold text-slate-900">{{ $staff->salary_display }}</p>
                    <p class="mt-2 text-sm text-slate-500">Affichage dynamique selon le type de contrat.</p>
                </div>
            </div>

            <div class="px-6 py-6 border-t border-gray-100 bg-slate-50">
                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div>
                        <p class="text-sm font-semibold text-slate-700">Poste principal</p>
                        <p class="mt-1 text-sm text-slate-500">{{ $staff->primaryPosition?->position_label ?? 'Personnel' }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-slate-700">Email</p>
                        <p class="mt-1 text-sm text-slate-500">{{ $staff->email ?? '—' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="rounded-3xl border border-gray-100 bg-white shadow-sm p-6">
            <div class="mb-5 rounded-3xl border border-blue-100 bg-blue-50 px-5 py-4">
                <p class="text-sm font-semibold text-blue-700">Instructions</p>
                <p class="mt-2 text-sm text-slate-600">
                    Seul le champ pertinent à votre type de contrat apparaît. Pour un personnel permanent, modifiez le salaire mensuel. Pour un vacataire, modifiez uniquement le tarif horaire.
                </p>
            </div>

            <div class="space-y-4 text-sm text-slate-700">
                <div class="rounded-3xl border border-slate-100 bg-slate-50 p-4">
                    <p class="font-semibold text-slate-800">Type de contrat</p>
                    <p class="mt-2 text-slate-600">{{ $staff->contract_label }}</p>
                </div>
                <div class="rounded-3xl border border-slate-100 bg-slate-50 p-4">
                    <p class="font-semibold text-slate-800">Conseil</p>
                    <p class="mt-2 text-slate-600">
                        {{ in_array($staff->contract_type, ['permanent', 'semi_permanent'], true) ? 'Utilisez uniquement le champ Salaire mensuel.' : 'Utilisez uniquement le champ Tarif horaire.' }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('staff.salary.update', $staff) }}">
        @csrf
        @method('PUT')

        <div class="rounded-3xl border border-gray-100 bg-white shadow-sm overflow-hidden">
            <div class="px-6 py-6 border-b border-gray-100">
                <h2 class="text-lg font-semibold text-slate-900">Modifier le salaire</h2>
                <p class="mt-2 text-sm text-slate-500">Mettez à jour le montant applicable en fonction du contrat.</p>
            </div>

            <div class="space-y-6 p-6">
                @if(in_array($staff->contract_type, ['permanent', 'semi_permanent'], true))
                    <div class="space-y-3">
                        <label class="block text-sm font-medium text-slate-700">Salaire mensuel</label>
                        <input type="number" name="monthly_salary"
                               value="{{ old('monthly_salary', $staff->monthly_salary) }}"
                               min="0" step="100"
                               class="w-full rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-100">
                        @error('monthly_salary')
                            <p class="text-sm text-red-600">{{ $message }}</p>
                        @else
                            <p class="text-sm text-slate-500">Entrez le montant mensuel en FCFA.</p>
                        @enderror
                    </div>
                @else
                    <div class="space-y-3">
                        <label class="block text-sm font-medium text-slate-700">Tarif horaire</label>
                        <input type="number" name="hourly_rate"
                               value="{{ old('hourly_rate', $staff->hourly_rate) }}"
                               min="0" step="50"
                               class="w-full rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-100">
                        @error('hourly_rate')
                            <p class="text-sm text-red-600">{{ $message }}</p>
                        @else
                            <p class="text-sm text-slate-500">Entrez le tarif horaire en FCFA.</p>
                        @enderror
                    </div>
                @endif

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="rounded-3xl border border-slate-100 bg-slate-50 p-4">
                        <p class="text-sm uppercase tracking-[0.18em] text-slate-500">Date d’entrée</p>
                        <p class="mt-2 text-sm text-slate-700">{{ $staff->start_date?->format('d/m/Y') ?? '—' }}</p>
                    </div>
                    <div class="rounded-3xl border border-slate-100 bg-slate-50 p-4">
                        <p class="text-sm uppercase tracking-[0.18em] text-slate-500">Position principale</p>
                        <p class="mt-2 text-sm text-slate-700">{{ $staff->primaryPosition?->position_label ?? 'Personnel' }}</p>
                    </div>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <a href="{{ route('staff.salaries') }}"
                       class="inline-flex w-full items-center justify-center rounded-3xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 sm:w-auto">
                        Annuler
                    </a>
                    <button type="submit"
                            class="inline-flex w-full items-center justify-center rounded-3xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800 sm:w-auto">
                        Enregistrer le salaire
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
