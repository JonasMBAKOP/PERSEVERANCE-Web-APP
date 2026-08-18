@extends('layouts.app')

@section('title', 'Modifier consultation')
@section('page-title', 'Modifier consultation')
@section('page-subtitle', 'Mettre à jour une consultation infirmière')

@section('content')
<div class="px-0 pb-12">
    <div class="mb-5 flex items-center gap-3">
        <a href="{{ route('infirmary.index') }}" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50" aria-label="Retour">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <p class="text-sm text-gray-500">{{ $visit->student_name }} · {{ $visit->visit_date?->format('d/m/Y') }}</p>
    </div>

    <form method="POST" action="{{ route('infirmary.update', $visit) }}" class="w-full rounded-2xl border border-gray-100 bg-white p-5 shadow-sm sm:p-7">
        @csrf
        @method('PUT')

        <div class="mb-7 border-b border-gray-100 pb-4">
            <h2 class="text-lg font-bold text-gray-900">Informations de la consultation</h2>
            <p class="mt-1 text-sm text-gray-500">Modifiez les données de la visite puis enregistrez.</p>
        </div>

        <div class="grid grid-cols-1 gap-5 lg:grid-cols-12">
            <div class="lg:col-span-3">
                <label class="mb-1.5 block text-sm font-semibold text-gray-700">Date <span class="text-red-500">*</span></label>
                <input type="date" name="visit_date" value="{{ old('visit_date', $visit->visit_date?->format('Y-m-d')) }}" required class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm outline-none focus:border-sky-500 focus:ring-2 focus:ring-sky-100">
                @error('visit_date')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="lg:col-span-3">
                <label class="mb-1.5 block text-sm font-semibold text-gray-700">Heure <span class="text-red-500">*</span></label>
                <input type="time" name="visit_time" value="{{ old('visit_time', substr((string) $visit->visit_time, 0, 5)) }}" required class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm outline-none focus:border-sky-500 focus:ring-2 focus:ring-sky-100">
                @error('visit_time')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="lg:col-span-3">
                <label class="mb-1.5 block text-sm font-semibold text-gray-700">Température (C)</label>
                <input type="number" name="temperature" value="{{ old('temperature', $visit->temperature) }}" min="30" max="45" step="0.1" placeholder="Ex: 37.2" class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm outline-none focus:border-sky-500 focus:ring-2 focus:ring-sky-100">
                @error('temperature')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="lg:col-span-6">
                <label class="mb-1.5 block text-sm font-semibold text-gray-700">Motif <span class="text-red-500">*</span></label>
                <textarea name="visit_reason" rows="5" required class="w-full resize-y rounded-xl border border-gray-200 px-3 py-2.5 text-sm outline-none focus:border-sky-500 focus:ring-2 focus:ring-sky-100">{{ old('visit_reason', $visit->visit_reason) }}</textarea>
                @error('visit_reason')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="lg:col-span-6">
                <label class="mb-1.5 block text-sm font-semibold text-gray-700">Traitement effectue</label>
                <textarea name="treatment" rows="5" class="w-full resize-y rounded-xl border border-gray-200 px-3 py-2.5 text-sm outline-none focus:border-sky-500 focus:ring-2 focus:ring-sky-100">{{ old('treatment', $visit->treatment) }}</textarea>
                @error('treatment')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="lg:col-span-12 flex justify-end gap-3">
                <a href="{{ route('infirmary.index') }}" class="rounded-xl border border-gray-200 px-5 py-2.5 text-sm font-semibold text-gray-600 hover:bg-gray-50">Annuler</a>
                <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-sky-700 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-sky-800">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Enregistrer les modifications
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
