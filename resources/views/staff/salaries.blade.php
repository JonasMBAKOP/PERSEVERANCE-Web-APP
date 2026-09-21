@extends('layouts.app')

@section('title', 'Salaires')
@section('page-title', 'Salaires du personnel')
@section('page-subtitle', 'Liste des membres du personnel par type de contrat')

@section('content')
<div>
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Salaires</h2>
            <p class="text-sm text-gray-500 mt-1">Consultez les fiches par type de contrat.</p>
        </div>

        <form method="GET" action="{{ route('staff.salaries') }}"
              class="flex w-full flex-col items-stretch gap-3 sm:flex-row sm:flex-wrap sm:items-center lg:w-auto">
            <div class="relative w-full sm:w-[220px]">
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Rechercher un membre"
                       class="w-full rounded-lg border border-gray-200 bg-white py-2 pl-3 pr-20 text-sm focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500">
                <div class="absolute inset-y-0 right-2 flex items-center gap-1">
                    @if(request('search'))
                        <a href="{{ route('staff.salaries', request()->except('search', 'page')) }}"
                           aria-label="Réinitialiser la recherche"
                           class="text-gray-400 hover:text-gray-600">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </a>
                    @endif
                    <button type="submit" aria-label="Lancer la recherche" class="text-gray-500 transition hover:text-[#1A3A6B]">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35m1.35-5.15a6.5 6.5 0 1 1-13 0 6.5 6.5 0 0 1 13 0Z"/>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="relative w-full sm:w-auto">
                <select name="contract" onchange="this.form.submit()"
                        class="w-full min-w-[220px] cursor-pointer appearance-none rounded-lg border border-gray-200 bg-white py-2 pl-3 pr-9 text-sm focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Tous les contrats</option>
                    @foreach(\App\Models\Staff::contractLabels() as $value => $label)
                        <option value="{{ $value }}" {{ request('contract') === $value ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                <span class="pointer-events-none absolute inset-y-0 right-2.5 flex items-center text-gray-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </span>
            </div>

            <a href="{{ route('staff.salaries.print', array_filter(['contract' => request('contract'), 'search' => request('search')], fn ($value) => filled($value))) }}"
               target="_blank"
               class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-[#1A3A6B] px-4 py-2 text-sm font-semibold text-white hover:bg-[#14304f] sm:w-auto">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 9V4h12v5M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2M6 14h12v6H6v-6Z"/>
                </svg>
                Imprimer la fiche
            </a>
        </form>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
        @foreach(\App\Models\Staff::contractLabels() as $type => $label)
            <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                <p class="text-sm text-gray-500 uppercase tracking-[0.18em] mb-3">{{ $label }}</p>
                <p class="text-3xl font-bold text-gray-900">{{ $contractCounts[$type] ?? 0 }}</p>
            </div>
        @endforeach
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100">
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500">Nom</th>
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500">E-mail</th>
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500">Téléphone</th>
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500">Contrat</th>
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500">Salaire</th>
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500">Poste</th>
                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($staff as $member)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-4">
                                <a href="{{ route('staff.show', $member) }}"
                                   class="flex items-center gap-3 group">
                                    @if($member->photo)
                                        <img src="{{ asset('storage/' . $member->photo) }}"
                                             alt="{{ $member->full_name }}"
                                             class="w-9 h-9 rounded-full object-cover ring-2 ring-gray-100
                                                    group-hover:ring-blue-300 flex-shrink-0 transition-all">
                                    @else
                                        <div class="w-9 h-9 rounded-full flex items-center justify-center
                                                    font-bold text-xs flex-shrink-0 text-white transition-all
                                                    group-hover:ring-2 group-hover:ring-blue-300"
                                             style="background-color: #1A3A6B;">
                                            {{ strtoupper(substr($member->first_name ?? '', 0, 1) . substr($member->last_name ?? '', 0, 1)) }}
                                        </div>
                                    @endif
                                    <span class="font-semibold text-gray-900 group-hover:text-blue-600 transition-colors">
                                        {{ $member->full_name }}
                                    </span>
                                </a>
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-600">{{ $member->email ?? '—' }}</td>
                            <td class="px-4 py-4 text-sm text-gray-600">{{ $member->phone ?? '—' }}</td>
                            <td class="px-4 py-4 text-sm text-gray-700">{{ $member->contract_label }}</td>
                            <td class="px-4 py-4 text-sm text-gray-700">{{ $member->salary_display }}</td>
                            <td class="px-4 py-4 text-sm text-gray-700">{{ $member->primaryPosition?->position_label ?? 'Personnel' }}</td>
                            <td class="px-4 py-4 text-sm text-right">
                                <div class="flex flex-wrap justify-end gap-2">
                                    <a href="{{ route('staff.pay-slip', $member) }}"
                                       target="_blank"
                                       class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-semibold text-emerald-700 bg-emerald-50 hover:bg-emerald-100">
                                        Bulletin
                                    </a>
                                    @can('manage-staff')
                                        <a href="{{ route('staff.salary.edit', $member) }}"
                                           class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-semibold text-blue-700 bg-blue-50 hover:bg-blue-100">
                                            Modifier
                                        </a>
                                    @else
                                        —
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-sm text-gray-500">
                                Aucun membre trouvé.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6">
        {{ $staff->links() }}
    </div>
</div>
@endsection
