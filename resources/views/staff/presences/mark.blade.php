@extends('layouts.app')

@section('title', 'Marquer les présences')
@section('page-title', 'Marquer les présences')
@section('page-subtitle', 'Enregistrer arrivée, départ et observations')

@section('content')
<div class="max-w-6xl mx-auto space-y-6">
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
            <form method="GET" action="{{ route('staff.presences.mark') }}" class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-end">
                <div class="w-full sm:w-[200px]">
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-500">Date</label>
                    <input type="date" name="date" value="{{ $date->toDateString() }}" class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                </div>
                <div class="w-full sm:w-[220px]">
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-500">Contrat</label>
                    <select name="contract_type" class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                        <option value="all" {{ ($contractType ?? 'all') === 'all' ? 'selected' : '' }}>Tous les contrats</option>
                        <option value="permanent" {{ ($contractType ?? '') === 'permanent' ? 'selected' : '' }}>Permanents</option>
                        <option value="semi" {{ ($contractType ?? '') === 'semi' ? 'selected' : '' }}>Semi-permanents</option>
                        <option value="vacataire" {{ ($contractType ?? '') === 'vacataire' ? 'selected' : '' }}>Vacataires</option>
                    </select>
                </div>
                <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-blue-700">Filtrer</button>
            </form>

            <div class="flex flex-wrap items-center justify-end gap-3">
                <div class="flex items-center justify-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm">
                    <span class="text-slate-500">Programmés</span>
                    <span class="text-lg font-black text-slate-800">{{ $staff->count() }}</span>
                </div>

                <a href="{{ route('staff.presences.index', ['date' => $date->toDateString(), 'contract_type' => $contractType ?? 'all']) }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                    <span>Retour à la visualisation</span>
                </a>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('staff.presences.store') }}">
        @csrf
        <input type="hidden" name="date" value="{{ $date->toDateString() }}">

        <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white shadow-sm">
            <table class="min-w-[920px] w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left">Nom</th>
                        <th class="px-4 py-3">Contrat</th>
                        <th class="px-4 py-3">Arrivée</th>
                        <th class="px-4 py-3">Départ</th>
                        <th class="px-4 py-3">Observations</th>
                        <th class="px-4 py-3">Statut</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    @foreach($staff as $member)
                        @php
                            $p = $presences->get($member->id);
                        @endphp
                        <tr>
                            <td class="px-4 py-3">{{ $member->full_name }}</td>
                            <td class="px-4 py-3 text-center">{{ ucfirst(str_replace('_', ' ', $member->contract_type)) }}</td>
                            <td class="px-4 py-3 text-center">
                                <input type="time" name="presences[{{ $member->id }}][arrival_time]" value="{{ $p?->arrival_time ?? '' }}" class="border rounded px-2 py-1">
                            </td>
                            <td class="px-4 py-3 text-center">
                                <input type="time" name="presences[{{ $member->id }}][departure_time]" value="{{ $p?->departure_time ?? '' }}" class="border rounded px-2 py-1">
                            </td>
                            <td class="px-4 py-3">
                                <input type="text" name="presences[{{ $member->id }}][note]" value="{{ $p?->note ?? '' }}" placeholder="Ex: Permissionné" class="w-full border rounded px-2 py-1">
                            </td>
                            <td class="px-4 py-3 text-center">
                                <select name="presences[{{ $member->id }}][status]" class="border rounded px-2 py-1">
                                    <option value="present" {{ $p && $p->status === 'present' ? 'selected' : '' }}>Présent</option>
                                    <option value="absent" {{ !$p || $p->status === 'absent' ? 'selected' : '' }}>Absent</option>
                                </select>
                                <span class="ml-2 inline-block autosave-indicator" data-staff-id="{{ $member->id }}" aria-hidden="true"></span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4 text-right">
            <button type="submit" class="bg-green-600 text-white px-5 py-2 rounded font-semibold">Enregistrer</button>
        </div>
    </form>
</div>

@push('scripts')
<script>
(() => {
    const route = "{{ route('staff.presences.store') }}";
    const date = "{{ $date->toDateString() }}";

    // CSRF token: prefer meta tag then fallback to form input
    const meta = document.querySelector('meta[name="csrf-token"]');
    const token = meta ? meta.getAttribute('content') : (document.querySelector('input[name="_token"]')?.value || '');

    const timers = {};
    const debounceMs = 700;

    function setIndicator(staffId, state, message = ''){
        const el = document.querySelector('.autosave-indicator[data-staff-id="' + staffId + '"]');
        if(!el) return;
        el.innerHTML = '';
        el.classList.remove('text-green-600','text-yellow-500','text-red-600');
        if(state === 'saving'){
            el.classList.add('text-yellow-500');
            el.innerHTML = '<svg class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle class="opacity-25" cx="12" cy="12" r="10" stroke-width="4"></circle><path class="opacity-75" d="M4 12a8 8 0 018-8v8z" fill="currentColor"></path></svg>';
        }else if(state === 'ok'){
            el.classList.add('text-green-600');
            el.innerHTML = '<svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>';
        }else if(state === 'error'){
            el.classList.add('text-red-600');
            el.innerHTML = '<svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>';
            if(message) el.title = message;
        }
    }

    function gatherRow(staffId){
        const rowPrefix = 'presences[' + staffId + ']';
        const status = document.querySelector('[name="' + rowPrefix + '[status]"]').value;
        const arrival = document.querySelector('[name="' + rowPrefix + '[arrival_time]"]').value;
        const departure = document.querySelector('[name="' + rowPrefix + '[departure_time]"]').value;
        const note = document.querySelector('[name="' + rowPrefix + '[note]"]').value;
        return { status, arrival_time: arrival || null, departure_time: departure || null, note: note || null };
    }

    function saveRow(staffId){
        setIndicator(staffId, 'saving');
        const payload = { date: date, presences: {} };
        payload.presences[staffId] = gatherRow(staffId);

        fetch(route, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token,
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload),
            credentials: 'same-origin'
        }).then(async (res) => {
            if(!res.ok) {
                const txt = await res.text();
                throw new Error(txt || 'Server error');
            }
            return res.json();
        }).then((json) => {
            if(json.ok) setIndicator(staffId, 'ok');
            else setIndicator(staffId, 'error', JSON.stringify(json));
        }).catch((err) => {
            setIndicator(staffId, 'error', err.message);
            console.error('Autosave error for', staffId, err);
        });
    }

    // Attach listeners
    document.querySelectorAll('tr').forEach(tr => {
        const staffId = tr.querySelector('.autosave-indicator')?.getAttribute('data-staff-id');
        if(!staffId) return;

        const inputs = tr.querySelectorAll('input[name^="presences[' + staffId + ']"] , select[name^="presences[' + staffId + ']"]');
        inputs.forEach(inp => {
            const eventName = inp.tagName.toLowerCase() === 'select' ? 'change' : 'input';
            inp.addEventListener(eventName, () => {
                if(timers[staffId]) clearTimeout(timers[staffId]);
                timers[staffId] = setTimeout(() => saveRow(staffId), debounceMs);
            });
        });
    });
})();
</script>
@endpush
@endsection
