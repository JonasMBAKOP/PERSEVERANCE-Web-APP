@extends('layouts.app')
@section('title', 'Absences - ' . $enrollment->student->full_name)
@section('page-title', 'Absences de l\'élève')
@section('page-subtitle'){{ $enrollment->student->full_name }}@endsection

@section('breadcrumb')
    <a href="{{ route('absences.index') }}" class="hover:text-gray-700">Absences</a>
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
    </svg>
    <span style="color:#1A3A6B;" class="font-medium">{{ $enrollment->student->full_name }}</span>
@endsection

@section('content')
<div class="-mx-2 -mt-2 pb-2">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 mb-5">
        <div class="flex flex-col items-stretch gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-full flex items-center justify-center text-white font-black text-xl"
                     style="background:{{ $enrollment->student->gender === 'M' ? '#1D4ED8' : '#BE185D' }};">
                    {{ strtoupper(substr($enrollment->student->last_name, 0, 1) . substr($enrollment->student->first_name, 0, 1)) }}
                </div>
                <div>
                    <h2 class="font-black text-lg" style="color:#1A3A6B;">{{ $enrollment->student->full_name }}</h2>
                    <p class="text-sm text-gray-500">{{ $enrollment->classGroup->full_name }} · {{ $enrollment->academicYear->label }}</p>
                </div>
            </div>
            <div class="grid w-full grid-cols-3 gap-2 text-center sm:w-auto sm:gap-4">
                <div class="px-4 py-3 rounded-xl bg-gray-50">
                    <p class="text-xl font-black text-gray-800">{{ $totalH }}h</p>
                    <p class="text-xs text-gray-500">Total</p>
                </div>
                <div class="px-4 py-3 rounded-xl" style="background:#EAF5EA;">
                    <p class="text-xl font-black text-green-700">{{ $justifiedH }}h</p>
                    <p class="text-xs text-gray-500">Justifiées</p>
                </div>
                <div class="px-4 py-3 rounded-xl bg-red-50">
                    <p class="text-xl font-black text-red-600">{{ $unjustifiedH }}h</p>
                    <p class="text-xs text-gray-500">Injustifiées</p>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <h3 class="font-black text-sm" style="color:#1A3A6B;">
                Historique des absences et retards ({{ $absences->total() }})
            </h3>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end lg:ml-auto">
                <form method="GET" action="{{ route('absences.student', $enrollment) }}" data-history-filters class="flex flex-wrap items-end gap-2">
                    <div>
                        <label for="start_date" class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-gray-400">Du</label>
                        <input id="start_date" type="date" name="start_date" value="{{ $startDate }}" class="rounded-lg border border-gray-200 px-2.5 py-2 text-xs text-gray-700 focus:border-[#1A3A6B] focus:outline-none">
                    </div>
                    <div>
                        <label for="end_date" class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-gray-400">Au</label>
                        <input id="end_date" type="date" name="end_date" value="{{ $endDate }}" class="rounded-lg border border-gray-200 px-2.5 py-2 text-xs text-gray-700 focus:border-[#1A3A6B] focus:outline-none">
                    </div>
                    <div>
                        <label for="absence_type" class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-gray-400">Type</label>
                        <select id="absence_type" name="type" class="min-w-[9rem] rounded-lg border border-gray-200 bg-white px-3 py-2 pr-10 text-xs text-gray-700 focus:border-[#1A3A6B] focus:outline-none">
                            <option value="all" @selected($absenceType === 'all')>Tous</option>
                            <option value="retards" @selected($absenceType === 'retards')>Retards</option>
                            <option value="absences" @selected($absenceType === 'absences')>Absences</option>
                        </select>
                    </div>
                    <button type="submit" class="rounded-lg bg-[#1A3A6B] px-3 py-2 text-xs font-bold text-white hover:bg-[#143052]">Filtrer</button>
                    @if($startDate || $endDate || $absenceType !== 'all')
                        <button type="button" data-reset-history class="rounded-lg border border-gray-200 px-3 py-2 text-xs font-bold text-gray-600 hover:bg-gray-50">Réinitialiser</button>
                    @endif
                </form>
                @can('manage-absences')
                <a href="{{ route('absences.create', ['class_id' => $enrollment->class_group_id]) }}" class="text-xs font-bold hover:underline sm:pb-2" style="color:#E87722;">+ Ajouter</a>
                @endcan
            </div>
        </div>

        @include('absences.partials.student-history', ['absences' => $absences])
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const filters = document.querySelector('[data-history-filters]');
    if (!filters) return;

    const loadHistory = (url) => {
        const target = new URL(url, window.location.origin);
        target.searchParams.set('history_only', '1');
        const history = document.getElementById('student-absence-history');
        if (!history) return;
        history.classList.add('opacity-60', 'pointer-events-none');

        fetch(target.toString(), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html',
            },
        })
            .then(response => {
                if (!response.ok) throw new Error('Unable to load absence history');
                return response.text();
            })
            .then(html => {
                const parsed = new DOMParser().parseFromString(html, 'text/html');
                const replacement = parsed.querySelector('#student-absence-history');
                if (replacement) history.replaceWith(replacement);
            })
            .catch(() => history.classList.remove('opacity-60', 'pointer-events-none'));
    };

    filters.addEventListener('submit', (event) => {
        event.preventDefault();
        const url = new URL(filters.action, window.location.origin);
        new FormData(filters).forEach((value, key) => {
            if (value !== '') url.searchParams.set(key, value);
            else url.searchParams.delete(key);
        });
        url.searchParams.delete('page');
        loadHistory(url.toString());
    });

    document.addEventListener('click', (event) => {
        const reset = event.target.closest('[data-reset-history]');
        if (reset) {
            event.preventDefault();
            const url = new URL(filters.action, window.location.origin);
            loadHistory(url.toString());
            filters.reset();
            return;
        }

        const link = event.target.closest('a[href]');
        if (!link || !link.closest('#student-absence-history')) return;
        event.preventDefault();
        loadHistory(link.href);
    });
})();
</script>
@endpush
