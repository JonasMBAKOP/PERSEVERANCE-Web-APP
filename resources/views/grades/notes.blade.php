@extends('layouts.app')

@section('title', 'Notes')
@section('page-title', 'Notes')
@section('page-subtitle')
    @if(auth()->user()->hasAnyRole(['super-admin','directeur','censeur']))
        Consultation de toutes les notes
    @else
        Vos notes par classe et par matière
    @endif
@endsection

@section('content')

@if(!$activeYear)
<div class="bg-amber-50 border border-amber-200 rounded-xl p-6 text-center">
    <p class="text-amber-700 font-semibold"><svg class="inline h-4 w-4 mr-1 align-[-2px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>Aucune année scolaire active.</p>
</div>
@else

{{-- ── FILTRES ──────────────────────────────────────────────────────────── --}}
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 mb-5"
     x-data="notesFilters(
         {{ json_encode($sections->map(fn($s) => ['id'=>$s->id,'name'=>$s->name])->values()) }},
         {{ json_encode($sequences->map(fn($s) => ['id'=>$s->id,'label'=>$s->label])->values()) }},
         '{{ $selectedSectionId }}',
         '{{ $selectedSubjectId }}',
         '{{ $selectedClassId }}',
         '{{ $selectedSequenceId }}'
     )"
     x-init="init()"

    <h3 class="text-sm font-black mb-4 pb-2 border-b border-gray-100"
        style="color:#1A3A6B;">
        Filtres de consultation
    </h3>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

        {{-- Section --}}
        <div>
            <label class="block text-xs font-bold text-gray-500 uppercase
                           tracking-wider mb-1.5">Section</label>
            <select x-model="sectionId"
                    @change="onSectionChange()"
                    class="w-full px-3 py-2.5 border border-gray-200 rounded-xl
                           text-sm focus:outline-none bg-white font-medium"
                    style="color:#1A3A6B;">
                <option value="">— Toutes les sections —</option>
                <template x-for="s in sections" :key="s.id">
                    <option :value="s.id" x-text="s.name"
                            :selected="s.id == sectionId"></option>
                </template>
            </select>
        </div>

        {{-- Matière --}}
        <div>
            <label class="block text-xs font-bold text-gray-500 uppercase
                           tracking-wider mb-1.5">Matière</label>
            <select x-model="subjectId"
                    @change="onSubjectChange()"
                    :disabled="!sectionId || loadingSubjects"
                    class="w-full px-3 py-2.5 border border-gray-200 rounded-xl
                           text-sm focus:outline-none bg-white font-medium
                           disabled:opacity-50">
                <option value="">— Toutes —</option>
                <template x-for="s in subjects" :key="s.id">
                    <option :value="s.id" x-text="s.name"
                            :selected="s.id == subjectId"></option>
                </template>
            </select>
        </div>

        {{-- Classe --}}
        <div>
            <label class="block text-xs font-bold text-gray-500 uppercase
                           tracking-wider mb-1.5">Classe</label>
            <select x-model="classId"
                    :disabled="!subjectId || loadingClasses"
                    class="w-full px-3 py-2.5 border border-gray-200 rounded-xl
                           text-sm focus:outline-none bg-white font-medium
                           disabled:opacity-50">
                <option value="">— Toutes —</option>
                <template x-for="c in classes" :key="c.id">
                    <option :value="c.id" x-text="c.full_name"
                            :selected="c.id == classId"></option>
                </template>
            </select>
        </div>

        {{-- Séquence --}}
        <div>
            <label class="block text-xs font-bold text-gray-500 uppercase
                           tracking-wider mb-1.5">Séquence</label>
            <select x-model="sequenceId"
                    class="w-full px-3 py-2.5 border border-gray-200 rounded-xl
                           text-sm focus:outline-none bg-white font-medium">
                <option value="">— Choisir —</option>
                <template x-for="s in sequences" :key="s.id">
                    <option :value="s.id" x-text="s.label"
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

    <div class="mt-4 flex gap-3">
        <button type="button"
                @click="search()"
                :disabled="!canLoad"
                :class="canLoad ? 'opacity-100' : 'opacity-40 cursor-not-allowed'"
                class="flex items-center gap-2 px-5 py-2.5 rounded-xl
                       text-white text-sm font-bold transition-all"
                style="background-color:#1A3A6B;">
            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                 viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      stroke-width="2"
                      d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            Afficher les notes
        </button>
        @if(request()->hasAny(['section_id','subject_id','class_id','sequence_id']))
        <a href="{{ route('grades.notes') }}"
           class="px-4 py-2.5 rounded-xl border border-gray-200 text-sm
                  font-medium text-gray-600 hover:bg-gray-50">
            Réinitialiser
        </a>
        @endif
    </div>
</div>

{{-- ── RÉSULTATS ────────────────────────────────────────────────────────── --}}
@if($enrollments->isNotEmpty() && $classSubject && $sequence)

<div class="flex items-center justify-between mb-3">
    <div>
        <h3 class="font-black text-sm" style="color:#1A3A6B;">
            {{ $classSubject->subject->name_fr }}
            <span class="text-gray-400 font-normal">·</span>
            {{ $selectedClass?->full_name }}
            <span class="text-gray-400 font-normal">·</span>
            {{ $sequence->label }}
        </h3>
        <p class="text-xs text-gray-500 mt-0.5">
            {{ $enrollments->count() }} élève(s) ·
            Coef. {{ $classSubject->coefficient }}
        </p>
    </div>
    @can('enter-grades')
    <a href="{{ route('grades.entry.form', [
        'section_id'  => $selectedSectionId,
        'subject_id'  => $selectedSubjectId,
        'class_id'    => $selectedClassId,
        'sequence_id' => $selectedSequenceId,
    ]) }}"
       class="flex items-center gap-2 px-4 py-2 rounded-xl text-white
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
    @endcan
</div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    @php $gradingScale = (float) ($classSubject->grading_scale ?: 20); @endphp
    <table class="w-full">
        <thead>
            <tr style="background:#F8FAFC; border-bottom:2px solid #E5E7EB;">
                <th class="text-left px-5 py-3.5 text-xs font-bold
                           text-gray-500 uppercase tracking-wider">
                    Élève
                </th>
                <th class="text-center px-5 py-3.5 text-xs font-bold
                           text-gray-500 uppercase tracking-wider">
                    Note /{{ rtrim(rtrim(number_format($gradingScale, 2, '.', ''), '0'), '.') }}
                </th>
                <th class="text-center px-5 py-3.5 text-xs font-bold
                           text-gray-500 uppercase tracking-wider">
                    Note /20
                </th>
                <th class="text-center px-5 py-3.5 text-xs font-bold
                           text-gray-500 uppercase tracking-wider">
                    Appréciation
                </th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @php $sum = 0; $cnt = 0; $absCount = 0; @endphp
            @foreach($enrollments as $enr)
            @php
                $g      = $grades->get($enr->id);
                $grade  = $g?->grade;
                $rawGrade = $g?->raw_grade ?? ($gradingScale == 20 ? $grade : null);
                $absent = $g?->is_absent ?? false;
                if ($absent) $absCount++;
                elseif ($grade !== null) { $sum += $grade; $cnt++; }
            @endphp
            <tr class="hover:bg-gray-50/50 transition-colors">
                <td class="px-5 py-3.5">
                    <div class="flex items-center gap-2.5">
                        <div class="w-7 h-7 rounded-full flex items-center
                                    justify-center text-white text-xs
                                    font-bold flex-shrink-0"
                             style="background:{{ $enr->student->gender==='M'
                                 ? '#1D4ED8' : '#BE185D' }};">
                            {{ strtoupper(substr($enr->student->last_name,0,1))
                               . strtoupper(substr($enr->student->first_name,0,1)) }}
                        </div>
                        <div>
                            <p class="text-sm font-bold text-gray-800">
                                {{ strtoupper($enr->student->last_name) }}
                                {{ $enr->student->first_name }}
                            </p>
                        </div>
                    </div>
                </td>
                <td class="px-5 py-3.5 text-center">
                    @if($absent)
                    <span class="text-red-500 font-black text-sm">ABS</span>
                    @elseif($rawGrade !== null)
                    <span class="text-lg font-black" style="color:#1A3A6B;">
                        {{ number_format((float)$rawGrade, 2) }}
                    </span>
                    @else
                    <span class="text-gray-300 text-sm">—</span>
                    @endif
                </td>
                <td class="px-5 py-3.5 text-center">
                    @if($absent)
                    <span class="text-red-500 font-black text-sm">ABS</span>
                    @elseif($grade !== null)
                    <span class="text-lg font-black" style="color:#1A3A6B;">
                        {{ number_format((float)$grade, 2) }}
                    </span>
                    @else
                    <span class="text-gray-300 text-sm">—</span>
                    @endif
                </td>
                <td class="px-5 py-3.5 text-center">
                    @if(!$absent && $grade !== null)
                    @php $a = \App\Models\AppreciationScale::uiForGrade((float) $grade); @endphp
                    @if($a)
                    <span class="text-xs font-bold px-2 py-1 rounded-lg"
                          style="background:{{ $a['bg'] }}; color:{{ $a['color'] }};"
                          title="{{ \App\Models\AppreciationScale::forGrade((float) $grade)?->label_fr }}">
                        {{ $a['code'] }}
                    </span>
                    @endif
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background:#F8FAFC; border-top:2px solid #E5E7EB;">
                <td class="px-5 py-3 text-xs font-bold text-gray-500 uppercase">
                    Moyenne de la classe
                </td>
                <td class="px-5 py-3"></td>
                <td class="px-5 py-3 text-center">
                    @if($cnt > 0)
                    @php $avg = $sum / $cnt; @endphp
                    <span class="text-base font-black" style="color:#1A3A6B;">
                        {{ number_format($avg, 2) }}/20
                    </span>
                    @else
                    <span class="text-gray-300">—</span>
                    @endif
                </td>
                <td class="px-5 py-3 text-center text-xs text-gray-500">
                    @if($absCount > 0)
                    <span class="text-red-500">{{ $absCount }} absent(s)</span>
                    @endif
                </td>
            </tr>
        </tfoot>
    </table>
</div>

@elseif(request()->hasAny(['section_id','subject_id','class_id','sequence_id']))
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-10
            text-center text-gray-400 text-sm">
    Sélectionnez tous les filtres pour afficher les notes.
</div>
@endif
@endif

@endsection

@push('scripts')
<script>
// function notesFilters(sections, sequences, initSection, initSubject, initClass, initSeq) {
//     return {
//         sections, sequences,
//         sectionId: initSection || '',
//         subjectId: initSubject || '',
//         classId:   initClass   || '',
//         sequenceId:initSeq     || '',
//         subjects:  [],
//         classes:   [],
//         loadingSubs: false,
//         loadingCls:  false,

//         get canSearch() {
//             return this.classId && this.sequenceId && this.subjectId;
//         },

//         async init() {
//             if (this.sectionId) {
//                 await this.loadSubjects();
//                 if (this.subjectId) await this.loadClasses();
//             }
//         },

//         async onSectionChange() {
//             this.subjectId = '';
//             this.classId   = '';
//             this.subjects  = [];
//             this.classes   = [];
//             if (this.sectionId) await this.loadSubjects();
//         },

//         async onSubjectChange() {
//             this.classId = '';
//             this.classes = [];
//             if (this.subjectId) await this.loadClasses();
//         },

//         async loadSubjects() {
//             this.loadingSubs = true;
//             try {
//                 const r = await fetch(`{{ route('grades.api.subjects') }}?section_id=${this.sectionId}`,
//                     { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
//                 const d = await r.json();
//                 this.subjects = d.subjects || [];
//             } finally { this.loadingSubs = false; }
//         },

//         async loadClasses() {
//             this.loadingCls = true;
//             try {
//                 const r = await fetch(`{{ route('grades.api.classes') }}?section_id=${this.sectionId}&subject_id=${this.subjectId}`,
//                     { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
//                 const d = await r.json();
//                 this.classes = d.classes || [];
//             } finally { this.loadingCls = false; }
//         },

//         search() {
//             if (!this.canSearch) return;
//             const p = new URLSearchParams({
//                 section_id:  this.sectionId,
//                 subject_id:  this.subjectId,
//                 class_id:    this.classId,
//                 sequence_id: this.sequenceId,
//             });
//             window.location.href = `{{ route('grades.notes') }}?${p}`;
//         }
//     }
// }
function notesFilters(sections, sequences, initSection, initSubject, initClass, initSeq) {
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

        search() {
            if (!this.canLoad) return;
            const p = new URLSearchParams({
                section_id:  this.sectionId,
                subject_id:  this.subjectId,
                class_id:    this.classId,
                sequence_id: this.sequenceId,
            });
            window.location.href = `/grades/notes?${p.toString()}`;
        }
    };
}
</script>
@endpush
