@extends('layouts.app')

@section('title', 'Saisie des Notes')
@section('page-title', 'Saisie des Notes')
@section('page-subtitle')
    @if($readyToShow && $classSubject)
        {{ $classSubject->subject->name_fr }} — {{ $selectedClass?->full_name }}
        — {{ $sequence?->label }}
    @else
        Sélectionnez les filtres pour commencer la saisie
    @endif
@endsection

@section('content')

<style>
    .grades-entry-page .grades-notes-table-scroll {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    .grades-entry-page .grades-notes-table-scroll table { min-width: 700px; }
</style>
<div class="grades-entry-page -mx-2 -mt-2 -mb-2 lg:-mx-4 lg:-mt-4 lg:-mb-4">

@if(!$activeYear)
<div class="bg-amber-50 border border-amber-200 rounded-xl p-6 text-center">
    <p class="text-amber-700 font-semibold"><svg class="inline h-4 w-4 mr-1 align-[-2px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>Aucune année scolaire active.</p>
</div>
@else

{{-- ════════════════════════════════════════════════════════════════════ --}}
{{-- FILTRES EN CASCADE                                                    --}}
{{-- ════════════════════════════════════════════════════════════════════ --}}
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 mb-5"
     x-data="entryFilters(
         {{ json_encode($sections->map(fn($s) => ['id'=>$s->id,'name'=>$s->name])->values()) }},
         {{ json_encode($sequences->map(fn($s) => ['id'=>$s->id,'label'=>$s->label,'trimester'=>$s->trimester?->number])->values()) }},
         '{{ $selectedSectionId }}',
         '{{ $selectedSubjectId }}',
         '{{ $selectedClassId }}',
         '{{ $selectedSequenceId }}'
     )"
     x-init="init()">

    <h3 class="text-sm font-black mb-4 pb-2 border-b border-gray-100"
        style="color:#1A3A6B;">
        Filtres de saisie
    </h3>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

        {{-- 1. Section --}}
        <div>
            <label class="block text-xs font-bold text-gray-500 uppercase
                           tracking-wider mb-1.5">
                Section <span class="text-red-500">*</span>
            </label>
            <select x-model="sectionId"
                    @change="onSectionChange()"
                    class="w-full px-3 py-2.5 border border-gray-200 rounded-xl
                           text-sm focus:outline-none bg-white font-medium"
                    style="color:#1A3A6B;">
                <option value="">— Choisir une section —</option>
                <template x-for="s in sections" :key="s.id">
                    <option :value="s.id" x-text="s.name"
                            :selected="s.id == sectionId"></option>
                </template>
            </select>
        </div>

        {{-- 2. Matière (dépend de Section) --}}
        <div>
            <label class="block text-xs font-bold text-gray-500 uppercase
                           tracking-wider mb-1.5">
                Matière <span class="text-red-500">*</span>
            </label>
            <select x-model="subjectId"
                    @change="onSubjectChange()"
                    :disabled="!sectionId || loadingSubjects"
                    class="w-full px-3 py-2.5 border border-gray-200 rounded-xl
                           text-sm focus:outline-none bg-white font-medium
                           disabled:opacity-50 disabled:cursor-not-allowed"
                    style="color:#1A3A6B;">
                <template x-if="loadingSubjects">
                    <option>Chargement...</option>
                </template>
                <template x-if="!loadingSubjects">
                    <option value="">— Choisir une matière —</option>
                </template>
                <template x-for="s in subjects" :key="s.id">
                    <option :value="s.id"
                            x-text="s.code + ' — ' + s.name"
                            :selected="s.id == subjectId"></option>
                </template>
            </select>
            <p x-show="subjects.length === 1 && !loadingSubjects"
               class="text-xs text-green-600 font-medium mt-0.5">
                <svg class="inline h-4 w-4 mr-1 align-[-2px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>Matière auto-sélectionnée
            </p>
        </div>

        {{-- 3. Classe (dépend de Section + Matière) --}}
        <div>
            <label class="block text-xs font-bold text-gray-500 uppercase
                           tracking-wider mb-1.5">
                Classe <span class="text-red-500">*</span>
            </label>
            <select x-model="classId"
                    :disabled="!subjectId || loadingClasses"
                    class="w-full px-3 py-2.5 border border-gray-200 rounded-xl
                           text-sm focus:outline-none bg-white font-medium
                           disabled:opacity-50 disabled:cursor-not-allowed"
                    style="color:#1A3A6B;">
                <template x-if="loadingClasses">
                    <option>Chargement...</option>
                </template>
                <template x-if="!loadingClasses">
                    <option value="">— Choisir une classe —</option>
                </template>
                <template x-for="c in classes" :key="c.id">
                    <option :value="c.id"
                            x-text="c.full_name + ' (' + c.enrolled + ' él.)'"
                            :selected="c.id == classId"></option>
                </template>
            </select>
            <p x-show="classes.length === 0 && subjectId && !loadingClasses"
               class="text-xs text-amber-600 font-medium mt-0.5">
                Aucune classe disponible pour cette matière
            </p>
        </div>

        {{-- 4. Séquence --}}
        <div>
            <label class="block text-xs font-bold text-gray-500 uppercase
                           tracking-wider mb-1.5">
                Séquence <span class="text-red-500">*</span>
            </label>
            <select x-model="sequenceId"
                    class="w-full px-3 py-2.5 border border-gray-200 rounded-xl
                           text-sm focus:outline-none bg-white font-medium"
                    style="color:#1A3A6B;">
                <option value="">— Choisir une séquence —</option>
                <template x-for="s in sequences" :key="s.id">
                    <option :value="s.id"
                            x-text="s.label + ' (T' + s.trimester + ')'"
                            :selected="s.id == sequenceId"></option>
                </template>
            </select>
        </div>

    </div>

    {{-- Alerte erreur API --}}
    <p x-show="apiError" x-text="apiError"
    class="mt-3 text-xs text-red-600 font-semibold bg-red-50
            px-3 py-2 rounded-lg border border-red-200">
    </p>

    {{-- Bouton Charger --}}
    <div class="mt-4 flex items-center gap-3">
        <button type="button"
                @click="loadGrades()"
                :disabled="!canLoad"
                :class="canLoad
                    ? 'opacity-100 cursor-pointer hover:shadow-md'
                    : 'opacity-40 cursor-not-allowed'"
                class="flex items-center gap-2 px-5 py-2.5 rounded-xl
                       text-white text-sm font-bold transition-all"
                style="background-color:#1A3A6B;">
            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                 viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      stroke-width="2"
                      d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0
                         0l-4-4m4 4V4"/>
            </svg>
            Charger les notes
        </button>

        @if($readyToShow)
        <a href="{{ route('grades.entry.form') }}"
           class="px-4 py-2.5 rounded-xl border border-gray-200 text-sm
                  font-medium text-gray-600 hover:bg-gray-50">
            Réinitialiser les filtres
        </a>
        @endif
    </div>

</div>

{{-- ════════════════════════════════════════════════════════════════════ --}}
{{-- TABLE DE SAISIE                                                       --}}
{{-- ════════════════════════════════════════════════════════════════════ --}}
@if($readyToShow)

@if(!$classSubject)
<div class="bg-red-50 border border-red-200 rounded-xl p-5 text-center">
    <p class="text-red-700 font-semibold text-sm">
        Cette matière n'est pas assignée à la classe sélectionnée.
    </p>
</div>

@elseif($enrollments->isEmpty())
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-10
            text-center text-gray-400 text-sm">
    Aucun élève inscrit dans cette classe.
</div>

@else

{{-- Alerte verrouillé --}}
@if($isLocked)
<div class="flex items-start gap-3 p-4 rounded-xl mb-4
            bg-red-50 border border-red-200">
    <svg class="w-5 h-5 text-red-500 flex-shrink-0" fill="currentColor"
         viewBox="0 0 20 20">
        <path fill-rule="evenodd"
              d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0
                 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z"/>
    </svg>
    <div>
        <p class="text-sm font-bold text-red-700">Notes verrouillées</p>
        <p class="text-xs text-red-600 mt-0.5">
            Verrouillé par {{ $lock?->lockedBy?->name ?? '—' }}
            le {{ $lock?->locked_at?->format('d/m/Y à H:i') }}.
        </p>
    </div>
</div>
@endif

{{-- Résumé avant le formulaire --}}
<div class="flex items-center gap-4 mb-4 flex-wrap">
    <div class="flex items-center gap-2 px-4 py-2 bg-white rounded-xl
                shadow-sm border border-gray-100">
        <div class="w-2 h-2 rounded-full" style="background:#1A3A6B;"></div>
        <span class="text-sm font-semibold" style="color:#1A3A6B;">
            {{ $classSubject->subject->name_fr }}
        </span>
        <span class="text-xs text-gray-400 px-2 py-0.5 bg-gray-100 rounded">
            Coef. {{ $classSubject->coefficient }}
        </span>
    </div>
    <div class="text-xs text-gray-500">
        {{ $selectedClass?->full_name }}
        · {{ $sequence?->label }}
        · {{ $enrollments->count() }} élève(s)
    </div>

    {{-- Verrouiller --}}
    @can('lock-grades')
    <form method="POST"
          action="{{ route('grades.lock', [$selectedClass, $sequence]) }}"
          class="ml-auto">
        @csrf @method('PATCH')
        <button type="submit"
                class="flex items-center gap-1.5 px-3 py-2 rounded-lg
                       text-xs font-bold border-2 transition-all
                       {{ $isLocked
                           ? 'bg-red-50 border-red-300 text-red-600'
                           : 'bg-gray-50 border-gray-200 text-gray-600' }}"
                onclick="return confirm('{{ $isLocked
                    ? 'Déverrouiller ?' : 'Verrouiller ? Plus modifiable.' }}')">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                 viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      stroke-width="2"
                      d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2
                         2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
            {{ $isLocked ? 'Déverrouiller' : 'Verrouiller' }}
        </button>
    </form>
    @endcan
</div>

@php $gradingScale = (float) ($classSubject->grading_scale ?: 20); @endphp

{{-- FORMULAIRE DE SAISIE --}}
@if(!$isLocked)
<form method="POST" action="{{ route('grades.save') }}"
      id="gradeEntryForm">
    @csrf
    <input type="hidden" name="sequence_id"      value="{{ $sequence->id }}">
    <input type="hidden" name="class_subject_id" value="{{ $classSubject->id }}">
    <input type="hidden" name="class_group_id"   value="{{ $selectedClass->id }}">
    {{-- Params pour redirection back --}}
    <input type="hidden" name="_redirect"
           value="{{ request()->fullUrl() }}">

    <div class="grades-notes-table-scroll bg-white rounded-2xl shadow-sm border border-gray-100
                mb-20">
        <table class="w-full">
            <thead>
                <tr style="background:#F8FAFC; border-bottom:2px solid #E5E7EB;">
                    <th class="text-left px-5 py-3.5 text-xs font-bold
                               text-gray-500 uppercase tracking-wider"
                        style="width:40%; min-width:200px;">
                        Élève
                    </th>
                    <th class="text-center px-5 py-3.5 text-xs font-bold
                               text-gray-500 uppercase tracking-wider"
                        style="width:45%;">
                        Note /{{ rtrim(rtrim(number_format($gradingScale, 2, '.', ''), '0'), '.') }}
                    </th>
                    <th class="text-center px-5 py-3.5 text-xs font-bold
                               text-gray-500 uppercase tracking-wider"
                        style="width:20%;">
                        Note /20
                    </th>
                    <th class="text-center px-5 py-3.5 text-xs font-bold
                               text-gray-500 uppercase tracking-wider"
                        style="width:25%;">
                        Appréciation
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50" id="gradeRows">
                @foreach($enrollments as $i => $enr)
                @php
                    $g      = $grades->get($enr->id);
                    $grade  = $g?->grade;
                    $rawGrade = $g?->raw_grade ?? ($gradingScale == 20 ? $grade : null);
                @endphp
                <tr class="hover:bg-blue-50/20 transition-colors"
                    data-row="{{ $enr->id }}">
                    <td class="px-5 py-3.5">
                        <div class="flex items-center gap-3">
                            <span class="w-6 h-6 rounded-full flex items-center
                                         justify-center text-white text-xs
                                         font-bold flex-shrink-0"
                                  style="background:{{ $enr->student->gender==='M'
                                      ? '#1D4ED8' : '#BE185D' }};">
                                {{ $i + 1 }}
                            </span>
                            <div>
                                <p class="text-sm font-bold text-gray-800">
                                    {{ strtoupper($enr->student->last_name) }}
                                </p>
                                <p class="text-xs text-gray-400">
                                    {{ $enr->student->first_name }}
                                </p>
                            </div>
                        </div>
                    </td>
                    <td class="px-5 py-3.5 text-center">
                        <input type="number"
                               name="grades[{{ $enr->id }}]"
                               id="grade-{{ $enr->id }}"
                               value="{{ $rawGrade !== null
                                   ? number_format((float)$rawGrade, 2, '.', '')
                                   : '' }}"
                               min="0" max="{{ $gradingScale }}" step="0.01"
                               placeholder="—"
                               oninput="updateGradeRow({{ $enr->id }}, this.value)"
                               onchange="roundGrade(this)"
                               class="grade-input text-center font-black
                                      text-base rounded-xl border-2 px-3 py-2
                                      focus:outline-none transition-all"
                               style="width:80px; color:#1A3A6B;
                                      border-color:{{ $rawGrade !== null ? '#1A3A6B' : '#E5E7EB' }};
                                      background:white;">
                    </td>
                    <td class="px-5 py-3.5 text-center">
                        <span id="normalized-{{ $enr->id }}"
                              class="text-base font-black" style="color:#1A3A6B;">
                            {{ $grade !== null ? number_format((float)$grade, 2) : '—' }}
                        </span>
                    </td>
                    <td class="px-5 py-3.5 text-center">
                        <span id="apprec-{{ $enr->id }}"
                              class="text-xs font-bold px-2 py-1 rounded-lg"
                              style="{{ $grade !== null
                                  ? gradeStyle((float)$grade)
                                  : 'background:#F3F4F6;color:#9CA3AF;' }}">
                            @if($grade !== null) {{ gradeLabel((float)$grade) }}
                            @else — @endif
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="background:#F8FAFC;border-top:2px solid #E5E7EB;">
                    <td class="px-5 py-3 text-xs font-bold text-gray-500 uppercase">
                        Moyenne de la classe
                    </td>
                    <td class="px-5 py-3"></td>
                    <td class="px-5 py-3 text-center">
                        <span id="class-avg" class="text-sm font-black"
                              style="color:#1A3A6B;">—</span>
                    </td>
                    <td class="px-5 py-3 text-xs text-gray-400 text-center">
                        <span id="filled-count">0</span>
                        / {{ $enrollments->count() }} notes saisies
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>

    {{-- Barre fixe de sauvegarde --}}
    <div class="fixed bottom-0 left-0 md:left-64 right-0 z-30 bg-white
                border-t border-gray-200 shadow-xl px-5 py-3.5
                flex items-center justify-between gap-4">
        <div class="flex items-center gap-3 text-sm">
            <div class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></div>
            <span class="text-gray-600">
                Notes modifiées — pensez à enregistrer
            </span>
        </div>
        <div class="flex items-center gap-2">
            {{-- <a href="{{ route('grades.entry.form') }}"
               class="px-4 py-2 border border-gray-200 rounded-lg text-sm
                      font-medium text-gray-600 hover:bg-gray-50">
                ← Changer les filtres
            </a> --}}
            <button type="submit"
                    class="flex items-center gap-2 px-6 py-2.5 rounded-xl
                           text-white text-sm font-bold transition-all
                           hover:shadow-lg"
                    style="background-color:#1A5C2A;">
                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                     viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Enregistrer les notes
            </button>
        </div>
    </div>

</form>

@else {{-- isLocked --}}
{{-- Vue en lecture seule --}}
<div class="grades-notes-table-scroll bg-white rounded-2xl shadow-sm border border-gray-100">
    <table class="w-full">
        <thead>
            <tr style="background:#F8FAFC; border-bottom:2px solid #E5E7EB;">
                <th class="text-left px-5 py-3.5 text-xs font-bold
                           text-gray-500 uppercase tracking-wider">Élève</th>
                <th class="text-center px-5 py-3.5 text-xs font-bold
                           text-gray-500 uppercase tracking-wider">Note /{{ rtrim(rtrim(number_format($gradingScale, 2, '.', ''), '0'), '.') }}</th>
                <th class="text-center px-5 py-3.5 text-xs font-bold
                           text-gray-500 uppercase tracking-wider">Note /20</th>
                <th class="text-center px-5 py-3.5 text-xs font-bold
                           text-gray-500 uppercase tracking-wider">Appréciation</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @foreach($enrollments as $enr)
            @php
                $g      = $grades->get($enr->id);
                $grade  = $g?->grade;
                $rawGrade = $g?->raw_grade ?? ($gradingScale == 20 ? $grade : null);
            @endphp
            <tr class="hover:bg-gray-50/50">
                <td class="px-5 py-3.5">
                    <p class="text-sm font-bold text-gray-800">
                        {{ $enr->student->full_name }}
                    </p>
                </td>
                <td class="px-5 py-3.5 text-center">
                    @if($rawGrade !== null)
                    <span class="text-lg font-black" style="color:#1A3A6B;">
                        {{ number_format((float)$rawGrade, 2) }}
                    </span>
                    @else
                    <span class="text-gray-300">—</span>
                    @endif
                </td>
                <td class="px-5 py-3.5 text-center">
                    @if($grade !== null)
                    <span class="text-lg font-black" style="color:#1A3A6B;">
                        {{ number_format((float)$grade, 2) }}
                    </span>
                    @else
                    <span class="text-gray-300">—</span>
                    @endif
                </td>
                <td class="px-5 py-3.5 text-center">
                    @if($grade !== null)
                    <span class="text-xs font-bold px-2 py-1 rounded-lg"
                          style="{{ gradeStyle((float)$grade) }}">
                        {{ gradeLabel((float)$grade) }}
                    </span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif {{-- end isLocked --}}
@endif {{-- end classSubject check --}}
@endif {{-- end readyToShow --}}

@endif {{-- end activeYear --}}

@php
use App\Models\AppreciationScale;

function gradeLabel(float $g): string {
    return AppreciationScale::forGrade($g)?->code ?? '—';
}
function gradeStyle(float $g): string {
    $ui = AppreciationScale::uiForGrade($g);
    return $ui ? "background:{$ui['bg']};color:{$ui['color']};" : '';
}
@endphp

</div>
@endsection

@push('scripts')
<script>
/* ── Filtres en cascade ──────────────────────────────────────────────── */
// function entryFilters(sections, sequences, initSection, initSubject, initClass, initSeq) {
//     return {
//         sections, sequences,
//         sectionId:       initSection  || '',
//         subjectId:       initSubject  || '',
//         classId:         initClass    || '',
//         sequenceId:      initSeq      || '',
//         subjects:        [],
//         classes:         [],
//         loadingSubjects: false,
//         loadingClasses:  false,

//         get canLoad() {
//             return this.sectionId && this.subjectId
//                 && this.classId && this.sequenceId;
//         },

//         async init() {
//             if (this.sectionId) {
//                 await this.loadSubjects(false);
//                 if (this.subjectId) await this.loadClasses(false);
//             }
//         },

//         async onSectionChange() {
//             this.subjectId = '';
//             this.classId   = '';
//             this.subjects  = [];
//             this.classes   = [];
//             if (this.sectionId) await this.loadSubjects(true);
//         },

//         async onSubjectChange() {
//             this.classId = '';
//             this.classes = [];
//             if (this.subjectId && this.sectionId) await this.loadClasses(true);
//         },

//         async loadSubjects(autoSelect) {
//             this.loadingSubjects = true;
//             try {
//                 const r = await fetch(
//                     `{{ route('grades.api.subjects') }}?section_id=${this.sectionId}`,
//                     { headers: { 'X-Requested-With': 'XMLHttpRequest' } }
//                 );
//                 const d = await r.json();
//                 this.subjects = d.subjects || [];

//                 // Auto-select si une seule matière
//                 if (autoSelect && this.subjects.length === 1) {
//                     this.subjectId = this.subjects[0].id;
//                     await this.loadClasses(false);
//                 }
//             } finally {
//                 this.loadingSubjects = false;
//             }
//         },

//         async loadClasses(autoSelect) {
//             this.loadingClasses = true;
//             try {
//                 const r = await fetch(
//                     `{{ route('grades.api.classes') }}?section_id=${this.sectionId}&subject_id=${this.subjectId}`,
//                     { headers: { 'X-Requested-With': 'XMLHttpRequest' } }
//                 );
//                 const d = await r.json();
//                 this.classes = d.classes || [];
//             } finally {
//                 this.loadingClasses = false;
//             }
//         },

//         loadGrades() {
//             if (!this.canLoad) return;
//             const p = new URLSearchParams({
//                 section_id:  this.sectionId,
//                 subject_id:  this.subjectId,
//                 class_id:    this.classId,
//                 sequence_id: this.sequenceId,
//             });
//             window.location.href = `{{ route('grades.entry.form') }}?${p}`;
//         }
//     }
// }
function entryFilters(sections, sequences, initSection, initSubject, initClass, initSeq) {
    return {
        sections, sequences,
        sectionId:       String(initSection  || ''),
        subjectId:       String(initSubject  || ''),
        classId:         String(initClass    || ''),
        sequenceId:      String(initSeq      || ''),
        subjects:        [],
        classes:         [],
        loadingSubjects: false,
        loadingClasses:  false,
        apiError:        '',

        get canLoad() {
            return this.sectionId && this.subjectId
                && this.classId && this.sequenceId;
        },

        async init() {
            // Charger au montage si filtres déjà présents dans l'URL
            if (this.sectionId) {
                await this.loadSubjects(false);
                if (this.subjectId) await this.loadClasses(false);
            }
        },

        async onSectionChange() {
            this.subjectId = '';
            this.classId   = '';
            this.subjects  = [];
            this.classes   = [];
            this.apiError  = '';
            if (this.sectionId) await this.loadSubjects(true);
        },

        async onSubjectChange() {
            this.classId  = '';
            this.classes  = [];
            this.apiError = '';
            if (this.subjectId && this.sectionId) await this.loadClasses(true);
        },

        async loadSubjects(autoSelect = false) {
            this.loadingSubjects = true;
            this.apiError        = '';
            try {
                const url = `/grades/api/subjects?section_id=${encodeURIComponent(this.sectionId)}`;
                const res = await fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    credentials: 'same-origin',
                });

                if (!res.ok) throw new Error(`HTTP ${res.status}`);
                const data = await res.json();

                if (data.error) throw new Error(data.error);
                this.subjects = data.subjects || [];

                // Auto-select si une seule matière
                if (autoSelect && this.subjects.length === 1) {
                    this.subjectId = String(this.subjects[0].id);
                    await this.loadClasses(false);
                }
            } catch (e) {
                this.apiError  = 'Erreur chargement matières: ' + e.message;
                this.subjects  = [];
                console.error(e);
            } finally {
                this.loadingSubjects = false;
            }
        },

        async loadClasses(autoSelect = false) {
            this.loadingClasses = true;
            this.apiError       = '';
            try {
                const url = `/grades/api/classes?section_id=${encodeURIComponent(this.sectionId)}&subject_id=${encodeURIComponent(this.subjectId)}`;
                const res = await fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    credentials: 'same-origin',
                });

                if (!res.ok) throw new Error(`HTTP ${res.status}`);
                const data = await res.json();

                if (data.error) throw new Error(data.error);
                this.classes = data.classes || [];
            } catch (e) {
                this.apiError = 'Erreur chargement classes: ' + e.message;
                this.classes  = [];
                console.error(e);
            } finally {
                this.loadingClasses = false;
            }
        },

        loadGrades() {
            if (!this.canLoad) return;
            const p = new URLSearchParams({
                section_id:  this.sectionId,
                subject_id:  this.subjectId,
                class_id:    this.classId,
                sequence_id: this.sequenceId,
            });
            window.location.href = `/grades/entry?${p.toString()}`;
        }
    };
}

/* ── Fonctions interactives du tableau de saisie ────────────────────── */
const APPREC = {!! json_encode($appreciationJs ?? []) !!};
const GRADING_SCALE = {{ $gradingScale }};

function getApprec(v) {
    return APPREC.find(a => v >= a.min && v <= a.max) || APPREC[APPREC.length - 1];
}

function updateGradeRow(eid, val) {
    const span  = document.getElementById(`apprec-${eid}`);
    const normalized = document.getElementById(`normalized-${eid}`);
    const input = document.getElementById(`grade-${eid}`);
    const v     = parseFloat(val);

    if (!span || !normalized) return;

    // Validation couleur
    if (val !== '' && (isNaN(v) || v < 0 || v > GRADING_SCALE)) {
        input.style.borderColor = '#EF4444';
        input.style.background  = '#FEF2F2';
        span.textContent = '!';
        span.style.cssText = 'background:#FEE2E2;color:#991B1B;';
        normalized.textContent = '—';
    } else if (val === '') {
        input.style.borderColor = '#E5E7EB';
        input.style.background  = 'white';
        span.textContent = '—';
        span.style.cssText = 'background:#F3F4F6;color:#9CA3AF;';
        normalized.textContent = '—';
    } else {
        const x = v * 20 / GRADING_SCALE;
        input.style.borderColor = '#1A3A6B';
        input.style.background  = '#EBF3FB';
        const a = getApprec(x);
        span.textContent = a.label;
        span.style.cssText = `background:${a.bg};color:${a.color};`;
        normalized.textContent = x.toFixed(2);
    }

    updateStats();
}


function roundGrade(input) {
    const v = parseFloat(input.value);
    if (!isNaN(v)) input.value = Math.round(v * 100) / 100;
}

function updateStats() {
    let sum = 0, cnt = 0, filled = 0;

    document.querySelectorAll('[data-row]').forEach(row => {
        const eid   = row.dataset.row;
        const inp   = document.getElementById(`grade-${eid}`);
        if (!inp) return;

        if (inp.value !== '') {
            const normalized = document.getElementById(`normalized-${eid}`);
            const v = parseFloat(normalized?.textContent);
            if (!isNaN(v) && v >= 0 && v <= 20) {
                sum += v; cnt++; filled++;
            }
        }
    });

    const avgEl = document.getElementById('class-avg');
    if (avgEl) {
        if (cnt > 0) {
            const avg = sum / cnt;
            const a   = getApprec(avg);
            avgEl.textContent = avg.toFixed(2) + '/20';
            avgEl.style.color = a.color;
        } else {
            avgEl.textContent = '—';
            avgEl.style.color = '#9CA3AF';
        }
    }

    const fc = document.getElementById('filled-count');
    if (fc) fc.textContent = filled;
}

// Init au chargement
document.addEventListener('DOMContentLoaded', updateStats);
</script>
@endpush
