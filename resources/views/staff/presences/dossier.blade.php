@extends('layouts.app')

@section('title', 'Dossier de présences')
@section('page-title', 'Dossier de présences')
@section('page-subtitle', 'Historique des présences et absences par personnel')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <form method="GET" action="{{ route('staff.presences.dossier') }}" class="flex flex-col gap-4 xl:flex-row xl:items-end">
            <div id="contract-field" class="w-full xl:w-[220px]">
                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-500">Type de contrat</label>
                <select name="contract_type" id="contract_type" class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                    <option value="all" {{ $contractType === 'all' ? 'selected' : '' }}>Tous</option>
                    <option value="permanent" {{ $contractType === 'permanent' ? 'selected' : '' }}>Permanent</option>
                    <option value="semi_permanent" {{ $contractType === 'semi_permanent' ? 'selected' : '' }}>Semi-permanent</option>
                    <option value="vacataire" {{ $contractType === 'vacataire' ? 'selected' : '' }}>Vacataire</option>
                </select>
            </div>

            <div id="staff-field" class="w-full xl:w-[260px]">
                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-500">Personnel</label>
                <select name="staff_id" id="staff_id" class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                    @foreach($availableStaff as $member)
                        <option value="{{ $member->id }}" {{ ($selectedStaff && $selectedStaff->id == $member->id) ? 'selected' : '' }}>
                            {{ $member->full_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div id="dossier-type-field" class="w-full xl:w-[180px]">
                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-500">Type de dossier</label>
                <select name="dossier_type" id="dossier_type" class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                    <option value="month" {{ $dossierType === 'month' ? 'selected' : '' }}>Mensuel</option>
                    <option value="range" {{ $dossierType === 'range' ? 'selected' : '' }}>Entre 2 dates</option>
                </select>
            </div>

            <div id="month-field" class="w-full sm:w-[190px] {{ $dossierType === 'month' ? '' : 'hidden' }}">
                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-500">Mois</label>
                <input type="month" name="month" value="{{ $month }}" class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
            </div>

            <div id="range-fields" class="w-full min-w-0 {{ $dossierType === 'range' ? '' : 'hidden' }} flex flex-col gap-3 sm:flex-row sm:items-end">
                <div class="w-full sm:w-[145px]">
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-500">Date de début</label>
                    <input type="date" name="start_date" value="{{ $startDate }}" class="w-full rounded-xl border border-gray-200 bg-white px-2 py-2.5 pr-1 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                </div>
                <div class="w-full sm:w-[145px]">
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-500">Date de fin</label>
                    <input type="date" name="end_date" value="{{ $endDate }}" class="w-full rounded-xl border border-gray-200 bg-white px-2 py-2.5 pr-1 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                </div>
            </div>

            <div class="xl:ml-auto w-full xl:w-auto">
                <button type="submit" class="w-full xl:w-auto rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-blue-700 whitespace-nowrap">
                    Filtrer
                </button>
            </div>
        </form>
    </div>

    @if($selectedStaff)
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 bg-slate-50">
                <div>
                    <h2 class="text-lg font-semibold text-slate-800">{{ $selectedStaff->full_name }}</h2>
                    <p class="text-sm text-slate-500">{{ ucfirst(str_replace('_', ' ', $selectedStaff->contract_type)) }} • {{ $start->translatedFormat('d M Y') }} au {{ $end->translatedFormat('d M Y') }}</p>
                </div>
                <a href="{{ route('staff.presences.index', ['date' => Carbon\Carbon::now()->toDateString(), 'contract_type' => $contractType]) }}" class="inline-flex items-center px-3 py-2 rounded-lg border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 transition text-sm font-medium">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                    Retour
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-slate-700">Date</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-slate-700">Jour</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-slate-700">Arrivée</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-slate-700">Départ</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-slate-700">Statut</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-slate-700">Observations</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($attendanceRows as $row)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3 text-sm text-slate-700">{{ $row['date'] }}</td>
                                <td class="px-4 py-3 text-sm text-slate-700">{{ $row['day'] }}</td>
                                <td class="px-4 py-3 text-sm text-slate-700">{{ $row['arrival_time'] }}</td>
                                <td class="px-4 py-3 text-sm text-slate-700">{{ $row['departure_time'] }}</td>
                                <td class="px-4 py-3 text-sm">
                                    @if($row['is_present'])
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">Présent</span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800">Absent</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-700">{{ $row['note'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-slate-500">Aucune donnée enregistrée pour cette période.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <div class="bg-white rounded-2xl border border-slate-200 p-8 text-center text-slate-500">
            Sélectionnez un type de contrat et un personnel pour afficher le dossier.
        </div>
    @endif
</div>

@push('scripts')
<script>
    const dossierType = document.getElementById('dossier_type');
    const monthField = document.getElementById('month-field');
    const rangeFields = document.getElementById('range-fields');
    const contractType = document.getElementById('contract_type');
    const staffSelect = document.getElementById('staff_id');
    const contractField = document.getElementById('contract-field');
    const staffField = document.getElementById('staff-field');
    const dossierTypeField = document.getElementById('dossier-type-field');

    const optionsByContract = @json($staffByContract);

    function refreshStaffOptions() {
        const selectedContract = contractType.value;
        const currentValue = staffSelect.value;
        const items = selectedContract === 'all'
            ? Object.values(optionsByContract).flat()
            : (optionsByContract[selectedContract] || []);

        staffSelect.innerHTML = '';

        if (!items.length) {
            staffSelect.innerHTML = '<option value="">Aucun personnel</option>';
            return;
        }

        items.forEach(item => {
            const option = document.createElement('option');
            option.value = item.id;
            option.textContent = item.name;
            if (String(currentValue) === String(item.id)) {
                option.selected = true;
            }
            staffSelect.appendChild(option);
        });
    }

    function toggleDossierMode() {
        const isMonth = dossierType.value === 'month';
        monthField.classList.toggle('hidden', !isMonth);
        rangeFields.classList.toggle('hidden', isMonth);
        contractField.classList.toggle('xl:w-[230px]', !isMonth);
        contractField.classList.toggle('xl:w-[220px]', isMonth);
        staffField.classList.toggle('xl:w-[290px]', !isMonth);
        staffField.classList.toggle('xl:w-[260px]', isMonth);
        dossierTypeField.classList.toggle('xl:w-[190px]', !isMonth);
        dossierTypeField.classList.toggle('xl:w-[180px]', isMonth);
    }

    dossierType.addEventListener('change', toggleDossierMode);
    contractType.addEventListener('change', refreshStaffOptions);

    toggleDossierMode();
    refreshStaffOptions();
</script>
@endpush
@endsection
