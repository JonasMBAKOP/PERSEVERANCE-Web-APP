@extends('layouts.app')

@section('title', 'Détail Notes — ' . $classGroup->full_name)
@section('page-title', 'Détail des Notes')
@section('page-subtitle')
    {{ $classGroup->full_name }} — {{ $sequence->label }}
@endsection

@section('breadcrumb')
    <a href="{{ route('grades.index') }}" class="hover:text-gray-700">
        Vue Globale
    </a>
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round"
              stroke-width="2" d="M9 5l7 7-7 7"/>
    </svg>
    <span style="color:#1A3A6B;" class="font-medium">
        {{ $classGroup->full_name }} · {{ $sequence->label }}
    </span>
@endsection

@section('content')

<style>
    .grades-detail-page .grades-table-scroll { overflow-x: auto; -webkit-overflow-scrolling: touch; }
    .grades-detail-page .grades-table-scroll table { min-width: 620px; }
    @media (max-width: 1023px) {
        .grades-detail-page .grades-table-scroll th:first-child,
        .grades-detail-page .grades-table-scroll td:first-child { width: 105px !important; min-width: 105px !important; }
        .grades-detail-page .grades-table-scroll th:last-child,
        .grades-detail-page .grades-table-scroll td:last-child { width: 60px !important; min-width: 60px !important; }
    }
</style>
<div class="grades-detail-page -mx-2 -mt-2 -mb-2 lg:-mx-4 lg:-mt-4 lg:-mb-4">

{{-- ── EN-TÊTE + FILTRE MATIÈRE ────────────────────────────────────────── --}}
<div class="flex flex-col sm:flex-row sm:items-center gap-4 mb-5">
    <div>
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl flex items-center justify-center
                        text-white font-black text-base"
                 style="background:#1A3A6B;">
                {{ strtoupper(substr($classGroup->name, 0, 2)) }}
            </div>
            <div>
                <h3 class="font-black text-lg" style="color:#1A3A6B;">
                    {{ $classGroup->full_name }}
                </h3>
                <p class="text-xs text-gray-500">
                    {{ $classGroup->level->section->name }}
                    · {{ $sequence->label }}
                    (T{{ $sequence->trimester?->number }})
                    · {{ $enrollments->count() }} élèves
                </p>
            </div>
        </div>
    </div>

    <div class="sm:ml-auto flex items-center gap-3 flex-wrap">
        {{-- Filtre matière --}}
        <form method="GET"
              action="{{ route('grades.detail', [$classGroup, $sequence]) }}"
              class="flex items-center gap-2">
            <label class="text-xs font-bold text-gray-500 uppercase
                           tracking-wider">
                Matière :
            </label>
            <select name="subject_id"
                    onchange="this.form.submit()"
                    class="px-3 py-2 border border-gray-200 rounded-lg text-sm
                           focus:outline-none bg-white font-medium"
                    style="color:#1A3A6B; min-width:240px;">
                <option value="">Toutes les matières</option>
                @foreach($subjects as $cs)
                <option value="{{ $cs->subject_id }}"
                        {{ $filterSubjectId == $cs->subject_id ? 'selected' : '' }}>
                    {{ $cs->subject->code }} — {{ $cs->subject->name_fr }}
                </option>
                @endforeach
            </select>
        </form>

        {{-- Verrouiller --}}
        @can('lock-grades')
        <form method="POST"
              action="{{ route('grades.lock', [$classGroup, $sequence]) }}">
            @csrf @method('PATCH')
            <button type="submit"
                    class="flex items-center gap-1.5 px-3 py-2 rounded-lg
                           text-xs font-bold border-2 transition-all
                           {{ $lock?->is_locked
                               ? 'bg-red-50 border-red-300 text-red-600'
                               : 'bg-gray-50 border-gray-200 text-gray-600' }}"
                    onclick="return confirm('{{ $lock?->is_locked
                        ? 'Déverrouiller ?' : 'Verrouiller les notes ?' }}')">
                {{ $lock?->is_locked ? 'Déverrouiller' : 'Verrouiller' }}
            </button>
        </form>
        @endcan

        @can('manage-academic-years')
        <a href="{{ route('grades.bordereau', [$classGroup, $sequence]) }}"
           target="_blank" rel="noopener noreferrer"
           class="flex items-center gap-1.5 px-3 py-2 rounded-lg border border-blue-200
                  text-xs font-bold text-blue-700 hover:bg-blue-50">
            Bordereau de notes
        </a>
            {{-- <a href="{{ route('classes.bordereau', $classe) }}?exam_type=sequence&sequence_id={{ $sequence->id }}"
                target="_blank"
                class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Imprimer le bordereau
            </a> --}}
        @endcan

        <a href="{{ route('grades.index',
                          ['section_id' => $classGroup->level->section_id]) }}"
           class="px-3 py-2 rounded-lg border border-gray-200 text-xs
                  font-medium text-gray-600 hover:bg-gray-50">
            ← Retour
        </a>
    </div>
</div>

{{-- ── TABLEAU NOTES ────────────────────────────────────────────────────── --}}
@if($enrollments->isEmpty())
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-10
            text-center text-gray-400 text-sm">
    Aucun élève inscrit dans cette classe.
</div>
@else

<div class="grades-table-scroll bg-white rounded-2xl shadow-sm border border-gray-100">
    <table class="w-full"
           style="border-collapse:separate;border-spacing:0;">
        <thead>
            <tr style="background:#F8FAFC;border-bottom:2px solid #E5E7EB;">
                <th class="text-left px-5 py-3.5 text-xs font-bold
                           text-gray-500 uppercase tracking-wider sticky
                           left-0 z-10"
                    style="background:#F8FAFC;min-width:140px;">
                    Élève
                </th>
                @foreach($displaySubjects as $cs)
                <th class="text-center px-4 py-3.5 text-xs font-bold
                           text-gray-500 uppercase tracking-wider"
                    style="min-width:100px;">
                    <div class="font-black" style="color:#1A3A6B;">
                        {{ $cs->subject->name_fr }}
                    </div>
                    <div class="text-gray-400 font-normal text-xs">
                        Coef. {{ $cs->coefficient }}
                    </div>
                </th>
                @endforeach
                @if(!$filterSubjectId)
                <th class="text-center px-4 py-3.5 text-xs font-bold
                           text-gray-500 uppercase tracking-wider
                           " style="background:#F8FAFC;min-width:70px;">
                    Moy.
                </th>
                @endif
            </tr>
        </thead>
        <tbody>
            @foreach($enrollments as $enr)
            @php
                $totalPts  = 0;
                $totalCoef = 0;
                $hasAny    = false;
            @endphp
            <tr class="border-b border-gray-50 hover:bg-blue-50/20 transition-colors">
                <td class="px-5 py-3.5 sticky left-0 bg-white z-5"
                    style="border-right:1px solid #F0F4F8;">
                    <div class="flex items-center gap-2.5">
                        <div class="w-7 h-7 rounded-full flex items-center
                                    justify-center text-white text-xs font-bold"
                             style="background:{{ $enr->student->gender==='M'
                                 ? '#1D4ED8' : '#BE185D' }};">
                            {{ strtoupper(substr($enr->student->last_name,0,1))
                               . strtoupper(substr($enr->student->first_name,0,1)) }}
                        </div>
                        <div>
                            <p class="text-xs font-bold text-gray-800">
                                {{ strtoupper($enr->student->last_name) }}
                            </p>
                            <p class="text-xs text-gray-400">
                                {{ $enr->student->first_name }}
                            </p>
                        </div>
                    </div>
                </td>

                @foreach($displaySubjects as $cs)
                @php
                    $g     = $allGrades->get($enr->id)?->get($cs->id);
                    $grade = $g?->grade;
                    $abs   = $g?->is_absent ?? false;
                    if (!$abs && $grade !== null) {
                        $totalPts  += $grade * $cs->coefficient;
                        $totalCoef += $cs->coefficient;
                        $hasAny = true;
                    } elseif ($abs) {
                        $totalCoef += $cs->coefficient;
                        $hasAny = true;
                    }
                @endphp
                <td class="px-4 py-3.5 text-center">
                    @if($abs)
                    <span class="text-xs font-black text-red-500">ABS</span>
                    @elseif($grade !== null)
                    @php
                        $g2 = (float)$grade;
                        $c2 = $g2>=16?'#1A5C2A':($g2>=12?'#2D6FD4':($g2>=10?'#92400E':'#991B1B'));
                    @endphp
                    <span class="text-sm font-black" style="color:{{ $c2 }};">
                        {{ number_format($g2, 2) }}
                    </span>
                    @else
                    <span class="text-gray-300 text-sm">—</span>
                    @endif
                </td>
                @endforeach

                @if(!$filterSubjectId)
                <td class="px-4 py-3.5 text-center"
                    style="box-shadow:-2px 0 6px rgba(0,0,0,.04);">
                    @if($hasAny && $totalCoef > 0)
                    @php $avg = $totalPts / $totalCoef; @endphp
                    <span class="text-sm font-black px-2 py-1 rounded-lg"
                          style="{{ $avg>=16?'background:#D1FAE5;color:#065F46;'
                              :($avg>=12?'background:#DBEAFE;color:#1D4ED8;'
                              :($avg>=10?'background:#FEF3C7;color:#92400E;'
                              :'background:#FEE2E2;color:#991B1B;')) }}">
                        {{ number_format($avg, 2) }}
                    </span>
                    @else
                    <span class="text-gray-300">—</span>
                    @endif
                </td>
                @endif
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

{{-- Bouton modifier --}}
@can('enter-grades')
@if(!$lock?->is_locked)
<div class="mt-4 flex justify-end">
    <a href="{{ route('grades.entry.form', [
        'section_id'  => $classGroup->level->section_id,
        'subject_id'  => $filterSubjectId ?: $displaySubjects->first()?->subject_id,
        'class_id'    => $classGroup->id,
        'sequence_id' => $sequence->id,
    ]) }}"
       class="flex items-center gap-2 px-5 py-2.5 rounded-xl text-white
              text-sm font-bold transition-all hover:shadow-md"
       style="background-color:#E87722;">
        <svg class="w-4 h-4" fill="none" stroke="currentColor"
             viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round"
                  stroke-width="2"
                  d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0
                     113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
        </svg>
        Modifier les notes
    </a>
</div>
@endif
@endcan
@endif

</div>
@endsection
