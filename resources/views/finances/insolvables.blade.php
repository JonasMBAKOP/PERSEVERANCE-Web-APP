@extends('layouts.app')

@section('title', 'Eleves insolvables')
@section('page-title', 'Eleves insolvables')
@section('page-subtitle', 'Suivi des frais scolaires restant a regler')

@push('styles')
@include('finances.partials.finance-ui-styles')
@endpush

@section('content')
<div class="mb-6 rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex items-start gap-4">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-red-50 text-red-600">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4c-.77-1.33-2.69-1.33-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z"/></svg>
            </div>
            <div>
                <h2 class="text-lg font-black text-gray-900">Registre des &eacute;l&egrave;ves insolvables</h2>
                <p class="mt-1 max-w-2xl text-sm text-gray-500">El&egrave;ves n'ayant pas commenc&eacute; ou n'ayant pas termin&eacute; le r&egrave;glement de leurs frais de scolarit&eacute;.</p>
                <p class="mt-2 text-xs font-bold text-[#1A3A6B]">Tranche concern&eacute;e : {{ $installmentLabel }}</p>
            </div>
        </div>
        <a href="{{ route('finances.global', ['year_id' => $selectedYear?->id]) }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-bold text-gray-700 transition hover:bg-gray-50">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Gestion globale
        </a>
    </div>
</div>

<div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
    @include('finances.partials.kpi-card', ['label' => 'Eleves concernes', 'value' => number_format($summary['count']), 'hint' => number_format($summary['unpaid_count']) . ' sans aucun paiement', 'color' => '#DC2626', 'bg' => '#FEF2F2', 'delay' => '0s', 'icon' => '<svg class="h-5 w-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2h5m3-8a4 4 0 100-8 4 4 0 000 8z"/></svg>'])
    @include('finances.partials.kpi-card', ['label' => 'Total des frais', 'value' => number_format($summary['due']), 'suffix' => 'FCFA', 'hint' => 'Pour les eleves affiches', 'color' => '#1A3A6B', 'bg' => '#EBF3FB', 'delay' => '.05s', 'icon' => '<svg class="h-5 w-5" style="color:#1A3A6B" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V7m0 9v1"/></svg>'])
    @include('finances.partials.kpi-card', ['label' => 'Reste a recouvrer', 'value' => number_format($summary['remaining']), 'suffix' => 'FCFA', 'hint' => 'Solde cumule des impayes', 'color' => '#C2410C', 'bg' => '#FFF7ED', 'delay' => '.1s', 'icon' => '<svg class="h-5 w-5 text-orange-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01m8-4a8 8 0 11-16 0 8 8 0 0116 0z"/></svg>'])
</div>

<form method="GET" action="{{ route('finances.insolvables') }}" class="mb-6 rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
    <div class="grid gap-4 lg:grid-cols-[1fr_1fr_1fr_1fr_auto] lg:items-end">
        <div><label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-gray-500">Ann&eacute;e scolaire</label><select name="year_id" class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm font-semibold text-gray-700">@foreach($years as $year)<option value="{{ $year->id }}" {{ $selectedYear?->id === $year->id ? 'selected' : '' }}>{{ $year->label }}{{ $year->is_active ? ' (Active)' : '' }}</option>@endforeach</select></div>
        <div><label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-gray-500">Par section</label><select name="section_id" class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm font-semibold text-gray-700"><option value="">Toutes les sections</option>@foreach($sections as $section)<option value="{{ $section->id }}" {{ $selectedSectionId === $section->id ? 'selected' : '' }}>{{ $section->name }}</option>@endforeach</select></div>
        <div><label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-gray-500">Paiement concern&eacute;</label><select name="installment_label" class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm font-semibold text-gray-700"><option value="">Toutes les tranches</option>@foreach($installmentLabels as $label)<option value="{{ $label }}" {{ $selectedInstallmentLabel === $label ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select></div>
        <div><label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-gray-500">Par classe</label><select name="class_id" class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm font-semibold text-gray-700"><option value="">Toutes les classes</option>@foreach($classes as $class)<option value="{{ $class->id }}" {{ $selectedClassId === $class->id ? 'selected' : '' }}>{{ $class->full_name }}</option>@endforeach</select></div>
        <div class="flex gap-2"><button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#1A3A6B] px-5 py-2.5 text-sm font-bold text-white transition hover:bg-[#163450]"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h18M6 8h12M10 12h4M11 16h2"/></svg>Filtrer</button><a href="{{ route('finances.insolvables.print', request()->query()) }}" target="_blank" rel="noopener" class="inline-flex items-center justify-center gap-2 rounded-xl border border-[#E87722] px-4 py-2.5 text-sm font-bold text-[#C2410C] transition hover:bg-orange-50"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2m-2 0H8v4h8v-4z"/></svg>Imprimer</a></div>
    </div>
</form>

<section class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
    <div class="flex flex-col gap-2 border-b border-gray-100 bg-gray-50/80 px-5 py-4 sm:flex-row sm:items-center sm:justify-between"><div><h3 class="text-sm font-black text-[#1A3A6B]">Liste des &eacute;l&egrave;ves &agrave; r&eacute;gulariser</h3><p class="text-xs text-gray-400">Tranche : {{ $installmentLabel }}. Classement alphab&eacute;tique des &eacute;l&egrave;ves.</p></div><span class="fin-chip">{{ number_format($summary['count']) }} &eacute;l&egrave;ve(s)</span></div>
    @if($rows->isEmpty())
        <div class="p-10 text-center"><svg class="mx-auto mb-3 h-12 w-12 text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5-2a9 9 0 11-18 0 9 9 0 0116 0z"/></svg><p class="text-sm font-bold text-gray-600">Aucun impay&eacute; ne correspond aux filtres.</p></div>
    @else
        <div class="overflow-x-auto"><table class="fin-table w-full min-w-[1180px] text-left"><thead class="border-b border-gray-100 bg-white"><tr><th class="px-5 py-3">El&egrave;ve</th><th class="px-4 py-3">Classe / Section</th><th class="px-4 py-3 text-right">Total Frais</th><th class="px-4 py-3 text-right">Total Pay&eacute;</th><th class="px-4 py-3 text-right">Reste &agrave; payer</th>@if(!$selectedInstallmentLabel)<th class="px-4 py-3">Tranche(s) restante(s)</th>@endif<th class="px-5 py-3 text-center">Action</th></tr></thead><tbody class="divide-y divide-gray-50 text-sm">
        @foreach($rows as $row) @php($enrollment = $row['enrollment'])
            <tr class="transition hover:bg-gray-50/70"><td class="px-5 py-4"><p class="font-bold text-gray-800">{{ $enrollment->student->full_name }}</p><p class="mt-0.5 text-xs text-gray-400">{{ $enrollment->student->matricule }}</p><span class="mt-2 inline-flex rounded-full px-2 py-0.5 text-[10px] font-bold {{ $row['status'] === 'unpaid' ? 'bg-red-50 text-red-600' : 'bg-amber-50 text-amber-700' }}">{{ $row['status'] === 'unpaid' ? 'Aucun paiement' : 'Paiement partiel' }}</span></td><td class="px-4 py-4"><p class="font-semibold text-gray-700">{{ $enrollment->classGroup->full_name }}</p><p class="mt-0.5 text-xs text-gray-400">{{ $enrollment->classGroup->level?->section?->name }}</p></td><td class="px-4 py-4 text-right font-bold text-gray-700">{{ number_format($row['total_due']) }} <span class="text-xs font-medium text-gray-400">FCFA</span></td><td class="px-4 py-4 text-right font-bold text-green-700">{{ number_format($row['total_paid']) }} <span class="text-xs font-medium text-gray-400">FCFA</span></td><td class="px-4 py-4 text-right font-black text-red-600">{{ number_format($row['remaining']) }} <span class="text-xs font-medium text-gray-400">FCFA</span></td>@if(!$selectedInstallmentLabel)<td class="px-4 py-4"><div class="flex max-w-xs flex-wrap gap-1.5">@foreach($row['remaining_fees'] as $fee)<span class="rounded-md bg-gray-100 px-2 py-1 text-[10px] font-semibold text-gray-600">{{ $fee['label'] }} : {{ number_format($fee['remaining']) }}</span>@endforeach</div></td>@endif<td class="px-5 py-4 text-center"><a href="{{ route('finances.student', $enrollment) }}" class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-bold text-[#1A3A6B] transition hover:border-blue-200 hover:bg-blue-50">Voir dossier <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></a></td></tr>
        @endforeach
        </tbody></table></div>
        @if($paginate && $rows->hasPages())<div class="border-t border-gray-100 px-5 py-4">{{ $rows->onEachSide(1)->links() }}</div>@endif
    @endif
</section>
@endsection
