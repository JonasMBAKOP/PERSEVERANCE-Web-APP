@extends('layouts.app')

@section('title', 'Appel du jour')
@section('page-title', 'Appel du jour')
@section('page-subtitle', 'Marquage des absences par période de l’emploi du temps')

@section('content')
<div class="-mx-2 -mt-2 space-y-5 pb-2" x-data>
    <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
        <form method="GET" action="{{ route('attendance.index') }}" class="grid gap-4 md:grid-cols-[1fr_220px_auto] md:items-end">
            <div>
                <label class="mb-1.5 block text-xs font-black uppercase tracking-wider text-gray-500">Classe</label>
                <select name="class_id" onchange="this.form.submit()" class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm font-semibold text-[#1A3A6B] focus:border-[#1A3A6B] focus:outline-none">
                    <option value="">Sélectionner une classe</option>
                    @foreach($classes->groupBy('level.section.name') as $sectionName => $sectionClasses)
                        <optgroup label="{{ $sectionName }}">
                            @foreach($sectionClasses as $class)
                                <option value="{{ $class->id }}" @selected($selectedClass?->id === $class->id)>{{ $class->full_name }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-black uppercase tracking-wider text-gray-500">Date de l’appel</label>
                <input type="date" name="date" value="{{ $date->toDateString() }}" @if(!$isUnrestricted) max="{{ now()->toDateString() }}" @endif onchange="this.form.submit()" @disabled(auth()->user()->hasRole('enseignant') && ! $isUnrestricted) class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm font-semibold text-[#1A3A6B] focus:border-[#1A3A6B] focus:outline-none disabled:cursor-not-allowed disabled:bg-gray-100 disabled:opacity-70">
            </div>
            <a href="{{ route('absences.index', ['class_id' => $selectedClass?->id]) }}" class="inline-flex items-center justify-center rounded-xl border border-gray-200 px-4 py-2.5 text-sm font-bold text-gray-600 hover:bg-gray-50">Voir les absences</a>
        </form>
    </div>

    @if(!$selectedClass)
        <div class="rounded-2xl border border-dashed border-gray-300 bg-white px-6 py-16 text-center shadow-sm">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-50 text-amber-600">
                <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7V3m8 4V3M4 11h16M5 5h14a1 1 0 011 1v13a1 1 0 01-1 1H5a1 1 0 01-1-1V6a1 1 0 011-1z"/></svg>
            </div>
            <h2 class="mt-4 text-lg font-black text-gray-800">Choisissez une classe</h2>
            <p class="mt-1 text-sm text-gray-500">Les périodes de l’emploi du temps seront proposées pour la date choisie.</p>
        </div>
    @elseif($periods->isEmpty())
        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-6 py-12 text-center">
            <h2 class="text-lg font-black text-amber-900">Aucune période programmée</h2>
            <p class="mt-1 text-sm text-amber-800">Aucun cours n’est enregistré pour {{ $date->translatedFormat('l d/m/Y') }} dans cette classe.</p>
            <a href="{{ route('timetable.index', ['class_id' => $selectedClass->id]) }}" class="mt-4 inline-flex rounded-xl bg-amber-700 px-4 py-2.5 text-sm font-bold text-white hover:bg-amber-800">Vérifier l’emploi du temps</a>
        </div>
    @else
        <form method="POST" action="{{ route('attendance.store') }}" class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
            @csrf
            <input type="hidden" name="class_group_id" value="{{ $selectedClass->id }}">
            <input type="hidden" name="absence_date" value="{{ $date->toDateString() }}">
            <div class="border-b border-gray-100 bg-[#1A3A6B] px-5 py-4 text-white">
                <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                    <div><h2 class="font-black">{{ $selectedClass->full_name }}</h2><p class="text-xs text-blue-100">{{ $date->translatedFormat('l d F Y') }} · {{ $periods->count() }} période(s)</p></div>
                    <span class="text-xs font-bold text-blue-100">Cochez un élève pour le déclarer absent</span>
                </div>
            </div>
            <div class="border-b border-gray-100 bg-gray-50 px-5 py-4">
                <p class="mb-3 text-xs font-black uppercase tracking-wider text-gray-500">Périodes à traiter</p>
                <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    @foreach($periods as $period)
                        <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-gray-200 bg-white px-3 py-3 hover:border-[#1A3A6B]">
                            <input type="checkbox" name="periods[]" value="{{ $period['key'] }}" checked class="h-4 w-4 rounded border-gray-300 text-[#1A3A6B] focus:ring-[#1A3A6B]">
                            <span class="min-w-0"><strong class="block text-sm text-gray-800">{{ $period['label'] }} · {{ $period['start'] }}-{{ $period['end'] }}</strong><span class="block truncate text-xs text-gray-500">{{ $period['subject'] }}</span></span>
                        </label>
                    @endforeach
                </div>
            </div>
            @if($enrollments->isEmpty())
                <div class="px-5 py-12 text-center text-sm text-gray-500">Aucun élève actif dans cette classe.</div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[760px] text-sm">
                        <thead class="bg-white text-left text-xs uppercase tracking-wider text-gray-400">
                            <tr class="border-b border-gray-100"><th class="px-5 py-3">Élève</th>@foreach($periods as $period)<th class="px-3 py-3 text-center"><span class="block font-black text-[#1A3A6B]">P{{ $period['index'] }}</span><span class="normal-case tracking-normal">{{ $period['start'] }}-{{ $period['end'] }}</span></th>@endforeach</tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                        @foreach($enrollments as $enrollment)
                            <tr class="hover:bg-gray-50/60">
                                <td class="px-5 py-3"><div class="font-bold text-gray-800">{{ $enrollment->student->full_name }}</div><div class="text-xs text-gray-400">{{ $enrollment->student->matricule }}</div></td>
                                @foreach($periods as $period)
                                    @php $absenceKey = $enrollment->id . ':' . $period['slot_id'] . ':' . $period['index']; @endphp
                                    <td class="px-3 py-3 text-center"><label class="inline-flex cursor-pointer items-center justify-center rounded-lg px-3 py-2 hover:bg-red-50"><input type="checkbox" name="attendance[{{ $enrollment->id }}][{{ $period['key'] }}]" value="1" @checked(isset($existing[$absenceKey])) class="h-5 w-5 rounded border-gray-300 text-red-600 focus:ring-red-500"><span class="sr-only">Absent</span></label></td>
                                @endforeach
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="flex flex-col gap-3 border-t border-gray-100 bg-gray-50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between"><p class="text-xs text-gray-500">Les périodes décochées seront considérées comme présence. Les absences justifiées existantes sont conservées.</p><button type="submit" class="inline-flex items-center justify-center rounded-xl bg-[#E87722] px-5 py-2.5 text-sm font-black text-white shadow-sm hover:bg-[#d96818]">Enregistrer l’appel</button></div>
            @endif
        </form>
    @endif
</div>
@endsection
