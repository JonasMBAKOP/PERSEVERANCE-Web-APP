@extends('layouts.app')
@section('title', 'Saisir des absences')
@section('page-title', 'Saisie des Absences')
@section('page-subtitle', 'Enregistrer les absences d\'une classe')

@section('content')

<div x-data="absenceForm()" class="-mx-2 -mt-2 pb-2">

    <form method="POST" action="{{ route('absences.store') }}"
          id="absenceForm">
        @csrf

        {{-- ── FILTRES ─────────────────────────────────────────────────── --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 mb-5">
            <h3 class="text-sm font-black mb-4 pb-2 border-b border-gray-100"
                style="color:#1A3A6B;">
                Paramètres de la saisie
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase
                                   tracking-wider mb-1.5">
                        Section
                    </label>
                    <select x-model="sectionId" @change="onSectionChange()"
                            class="w-full px-3 py-2.5 border border-gray-200
                                   rounded-xl text-sm focus:outline-none bg-white">
                        <option value="">— Toutes les sections —</option>
                        @foreach($sections as $section)
                        <option value="{{ $section->id }}">{{ $section->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase
                                   tracking-wider mb-1.5">
                        Classe <span class="text-red-500">*</span>
                    </label>
                    <select name="class_group_id"
                            x-ref="classSelect"
                            x-model="classId"
                            @change="loadStudents()"
                            class="w-full px-3 py-2.5 border border-gray-200
                                   rounded-xl text-sm focus:outline-none bg-white">
                        <option value="">— Sélectionner —</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase
                                   tracking-wider mb-1.5">
                        Date d'absence <span class="text-red-500">*</span>
                    </label>
                    <input type="date" name="absence_date"
                           value="{{ date('Y-m-d') }}"
                           max="{{ date('Y-m-d') }}"
                           class="w-full px-3 py-2.5 border border-gray-200
                                  rounded-xl text-sm focus:outline-none">
                </div>

                {{-- <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase
                                   tracking-wider mb-1.5">
                        Matière (optionnel)
                    </label>
                    <select name="class_subject_id"
                            :disabled="!classId"
                            class="w-full px-3 py-2.5 border border-gray-200
                                   rounded-xl text-sm focus:outline-none bg-white
                                   disabled:opacity-50">
                        <option value="">— Absence générale —</option>
                        @if($preClass)
                        @foreach($preClass->classSubjects as $cs)
                        <option value="{{ $cs->id }}">
                            {{ $cs->subject->name_fr }} (Coef. {{ $cs->coefficient }})
                        </option>
                        @endforeach
                        @endif
                    </select>
                </div> --}}

                {{-- <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase
                                   tracking-wider mb-1.5">
                        Période
                    </label>
                    <select name="period"
                            class="w-full px-3 py-2.5 border border-gray-200
                                   rounded-xl text-sm focus:outline-none bg-white">
                        <option value="matin">Matin</option>
                        <option value="apres-midi">Après-midi</option>
                        <option value="journée" selected>Journée entière</option>
                    </select>
                </div> --}}

            </div>
        </div>

        {{-- ── LISTE ÉLÈVES ────────────────────────────────────────────── --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100
                    overflow-hidden mb-20">
            <div class="px-5 py-3.5 border-b border-gray-100 flex items-center
                        justify-between">
                <h3 class="text-sm font-black" style="color:#1A3A6B;">
                    Élèves
                    <span class="text-gray-400 font-normal text-xs ml-1"
                          x-text="enrollments.length > 0
                              ? '(' + enrollments.length + ' élèves)'
                              : ''">
                    </span>
                </h3>
                <button type="button"
                        @click="markAll()"
                        x-show="enrollments.length > 0"
                        class="text-xs font-bold px-3 py-1.5 rounded-lg
                               border border-gray-200 text-gray-600
                               hover:bg-gray-50 transition-colors">
                    Tout marquer absent (2h)
                </button>
            </div>

            {{-- État vide --}}
            <div x-show="!classId || enrollments.length === 0"
                 class="px-5 py-10 text-center text-sm text-gray-400 italic">
                <span x-text="!classId
                    ? 'Sélectionnez une classe pour voir les élèves.'
                    : 'Aucun élève dans cette classe.'">
                </span>
            </div>

            {{-- Table --}}
            <template x-if="enrollments.length > 0">
                <table class="w-full">
                    <thead>
                        <tr style="background:#F8FAFC;border-bottom:1px solid #E5E7EB;">
                            <th class="text-left px-5 py-3 text-xs font-bold
                                       text-gray-400 uppercase tracking-wider">
                                Élève
                            </th>
                            <th class="text-center px-4 py-3 text-xs font-bold
                                       text-gray-400 uppercase tracking-wider">
                                Absent
                            </th>
                            <th class="text-center px-4 py-3 text-xs font-bold
                                       text-gray-400 uppercase tracking-wider">
                                Heures
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(e, i) in enrollments" :key="e.id">
                            <tr :class="absences[e.id] > 0
                                    ? 'bg-red-50/30' : 'hover:bg-gray-50/40'"
                                class="border-b border-gray-50 transition-colors">
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-8 h-8 rounded-full flex
                                                    items-center justify-center
                                                    text-white text-xs font-bold"
                                             :style="'background:' + (e.gender==='M' ? '#1D4ED8' : '#BE185D')">
                                            <span x-text="e.full_name.charAt(0)"></span>
                                        </div>
                                        <div>
                                            <p class="text-sm font-bold text-gray-800"
                                               x-text="e.full_name"></p>
                                            <p class="text-xs text-gray-400"
                                               x-text="e.matricule"></p>
                                        </div>
                                    </div>
                                    <input type="hidden"
                                           :name="'absences[' + e.id + '][enrollment_id]'"
                                           :value="e.id">
                                </td>
                                <td class="px-4 py-3.5 text-center">
                                    <input type="checkbox"
                                           :id="'abs-' + e.id"
                                           @change="toggleAbsent(e.id, $event.target.checked)"
                                           class="w-5 h-5 rounded cursor-pointer"
                                           style="accent-color:#EF4444;">
                                </td>
                                <td class="px-4 py-3.5 text-center">
                                    <input type="number"
                                           :name="'absences[' + e.id + '][hours]'"
                                           x-model="absences[e.id]"
                                           :disabled="!absences[e.id]"
                                           min="0.5" max="8" step="0.5"
                                           placeholder="—"
                                           class="w-16 px-2 py-1.5 border border-gray-200
                                                  rounded-lg text-sm text-center
                                                  font-bold focus:outline-none
                                                  disabled:opacity-30"
                                           style="color:#EF4444;">
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </template>
        </div>

        {{-- Barre fixe --}}
        <div class="fixed bottom-0 left-0 md:left-64 right-0 z-30 bg-white
                    border-t border-gray-200 shadow-xl px-5 py-3.5
                    flex items-center justify-between gap-4">
            <div class="text-sm text-gray-600">
                <span class="font-bold text-red-600" x-text="absentCount"></span>
                absent(s) sélectionné(s)
            </div>
            <div class="flex gap-2">
                <a href="{{ route('absences.index') }}"
                   class="px-4 py-2 border border-gray-200 rounded-lg text-sm
                          font-medium text-gray-600 hover:bg-gray-50">
                    Annuler
                </a>
                <button type="submit"
                        :disabled="absentCount === 0"
                        :class="absentCount > 0 ? 'opacity-100' : 'opacity-40 cursor-not-allowed'"
                        class="px-5 py-2 rounded-lg text-white text-sm font-bold
                               transition-all"
                        style="background-color:#E87722;">
                    Enregistrer les absences
                </button>
            </div>
        </div>

    </form>
</div>

@endsection

@push('scripts')
<script>
const _absenceClasses = {!! json_encode($classesJson) !!};

function absenceForm() {
    return {
        sectionId:   '{{ $preSectionId ?? '' }}',
        classId:     '{{ $preClass?->id ?? '' }}',
        allClasses:  _absenceClasses,
        enrollments: [],
        absences:    {},

        get filteredClasses() {
            if (!this.sectionId) return this.allClasses;
            return this.allClasses.filter(c =>
                String(c.section_id) === String(this.sectionId)
            );
        },

        get absentCount() {
            return Object.values(this.absences).filter(v => v > 0).length;
        },

        init() {
            this.rebuildClassOptions();
            if (this.classId) this.loadStudents();
        },

        rebuildClassOptions() {
            const select = this.$refs.classSelect;
            if (!select) return;

            while (select.options.length > 1) {
                select.remove(1);
            }

            this.filteredClasses.forEach(c => {
                const opt = document.createElement('option');
                opt.value = c.id;
                opt.textContent = c.name;
                select.appendChild(opt);
            });
        },

        onSectionChange() {
            const stillVisible = this.filteredClasses.some(
                c => String(c.id) === String(this.classId)
            );
            if (!stillVisible) {
                this.classId = '';
                this.enrollments = [];
                this.absences = {};
            }
            this.rebuildClassOptions();
        },

        async loadStudents() {
            if (!this.classId) {
                this.enrollments = [];
                this.absences = {};
                return;
            }
            try {
                const r = await fetch(`/absences/api/students?class_id=${this.classId}`,
                    { headers: {'X-Requested-With':'XMLHttpRequest'} });
                const d = await r.json();
                this.enrollments = d.enrollments || [];
                this.absences    = {};
                this.enrollments.forEach(e => this.absences[e.id] = 0);
            } catch (e) {
                console.error(e);
            }
        },

        toggleAbsent(eid, checked) {
            this.absences[eid] = checked ? 2 : 0;
        },

        markAll() {
            this.enrollments.forEach(e => {
                this.absences[e.id] = 2;
                const cb = document.getElementById(`abs-${e.id}`);
                if (cb) cb.checked = true;
            });
        }
    }
}
</script>
@endpush
