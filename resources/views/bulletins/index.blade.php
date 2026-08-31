@extends('layouts.app')
@section('title', 'Bulletins')
@section('page-title', 'Génération des Bulletins')
@section('page-subtitle', 'Bulletins séquentiels, trimestriels et annuels')

@section('content')

@if(!$activeYear)
<div class="bg-amber-50 border border-amber-200 rounded-xl p-6 text-center">
    <p class="text-amber-700 font-semibold"><svg class="inline h-4 w-4 mr-1 align-[-2px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>Aucune année scolaire active.</p>
</div>
@else

{{-- ── ERREURS DE VALIDATION ───────────────────────────────────────────── --}}
@if($errors->any())
<div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-5">
    <p class="text-sm font-bold text-red-700 mb-1">
        <svg class="inline h-4 w-4 mr-1 align-[-2px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>Le formulaire contient des erreurs :
    </p>
    <ul class="text-sm text-red-600 list-disc list-inside">
        @foreach($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

@if(session('error'))
<div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-5">
    <p class="text-sm font-bold text-red-700">{{ session('error') }}</p>
</div>
@endif

<form method="POST" action="{{ route('bulletins.bulk-pdf') }}"
      x-data="bulletinForm()" x-init="init()" target="_blank">
    @csrf

    {{-- ── FILTRES ─────────────────────────────────────────────────────── --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 mb-5">
        <h3 class="text-sm font-black mb-4 pb-2 border-b border-gray-100"
            style="color:#1A3A6B;">
            Paramètres du bulletin
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">

            {{-- Section (filtre les classes) --}}
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase
                               tracking-wider mb-1.5">
                    Section
                </label>
                <select x-model="sectionId"
                        @change="onSectionChange()"
                        class="w-full px-3 py-2.5 border border-gray-200
                               rounded-xl text-sm focus:outline-none bg-white">
                    <option value="">— Toutes les sections —</option>
                    @foreach($sections as $sec)
                    <option value="{{ $sec->id }}">{{ $sec->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Classe (dépend de Section) --}}
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase
                               tracking-wider mb-1.5">
                    Classe <span class="text-red-500">*</span>
                </label>
                <select name="class_group_id" x-model="classId"
                        @change="onClassChange()"
                        class="w-full px-3 py-2.5 border border-gray-200
                               rounded-xl text-sm focus:outline-none bg-white">
                    <option value="">— Choisir —</option>
                    @foreach($classes as $c)
                    <option value="{{ $c->id }}"
                            data-section="{{ $c->level->section_id }}">
                        {{ $c->full_name }} ({{ $c->enrolled }} él.)
                    </option>
                    @endforeach
                </select>
            </div>

            {{-- Type --}}
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase
                               tracking-wider mb-1.5">
                    Type de bulletin <span class="text-red-500">*</span>
                </label>
                <select name="type" x-model="type"
                        class="w-full px-3 py-2.5 border border-gray-200
                               rounded-xl text-sm focus:outline-none bg-white">
                    <option value="sequentiel">Séquentiel</option>
                    <option value="trimestriel">Trimestriel</option>
                    <option value="annuel">Annuel</option>
                </select>
            </div>

            {{-- Séquence (si sequentiel) --}}
            <div x-show="type === 'sequentiel'">
                <label class="block text-xs font-bold text-gray-500 uppercase
                               tracking-wider mb-1.5">
                    Séquence <span class="text-red-500">*</span>
                </label>
                <select name="sequence_id" x-model="sequenceId"
                        class="w-full px-3 py-2.5 border border-gray-200
                               rounded-xl text-sm focus:outline-none bg-white">
                    <option value="">— Choisir —</option>
                    @foreach($sequences as $seq)
                    <option value="{{ $seq->id }}">
                        {{ $seq->label }} (T{{ $seq->trimester?->number }})
                    </option>
                    @endforeach
                </select>
            </div>

            {{-- Trimestre (si trimestriel) --}}
            <div x-show="type === 'trimestriel'">
                <label class="block text-xs font-bold text-gray-500 uppercase
                               tracking-wider mb-1.5">
                    Trimestre <span class="text-red-500">*</span>
                </label>
                <select name="trimester_id" x-model="trimesterId"
                        class="w-full px-3 py-2.5 border border-gray-200
                               rounded-xl text-sm focus:outline-none bg-white">
                    <option value="">— Choisir —</option>
                    @foreach($trimesters as $tri)
                    <option value="{{ $tri->id }}">{{ $tri->label }}</option>
                    @endforeach
                </select>
            </div>

        </div>

        {{-- @can('manage-parent-communication')
        @php
            $phone = $enrollment->student->father_phone
                ?? $enrollment->student->mother_phone
                ?? $enrollment->student->guardian_phone;
        @endphp
        @if($phone)
        <form method="POST" action="{{ route('communication.parents.bulletin.send', $enrollment) }}"
            class="inline">
            @csrf
            <input type="hidden" name="phone" value="{{ $phone }}">
            <input type="hidden" name="pdf_url"
                value="{{ route('bulletins.pdf', [$enrollment, 'type'=>$type,
                            'sequence_id'=>request('sequence_id'), 'trimester_id'=>request('trimester_id')]) }}">
            <button type="submit"
                    class="flex items-center gap-2 px-5 py-2.5 rounded-xl text-white
                        text-sm font-bold" style="background-color:#1A5C2A;">
                📱 Envoyer tous par WhatsApp
            </button>
        </form>
        @endif
        @endcan --}}

        {{-- Message d'aide dynamique --}}
        <p class="mt-3 text-xs text-amber-600 font-medium"
           x-show="classId && !periodSelected">
            <svg class="inline h-4 w-4 mr-1 align-[-2px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>Veuillez sélectionner
            <span x-text="type === 'sequentiel' ? 'une séquence' : 'un trimestre'"></span>
            avant de générer.
        </p>
    </div>

    {{-- ── ÉLÈVES ──────────────────────────────────────────────────────── --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100
                overflow-hidden mb-5">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center
                    justify-between">
            <h3 class="text-sm font-black" style="color:#1A3A6B;">
                Élèves
                <span class="text-gray-400 font-normal text-xs ml-1"
                      x-text="students.length ? '(' + students.length + ')' : ''">
                </span>
            </h3>
            <button type="button" @click="toggleAll()"
                    x-show="students.length > 0"
                    class="text-xs font-bold px-3 py-1.5 rounded-lg
                           border border-gray-200 text-gray-600 hover:bg-gray-50">
                <span x-text="allSelected ? 'Tout désélectionner' : 'Tout sélectionner'"></span>
            </button>
        </div>

        <div x-show="!classId" class="px-5 py-10 text-center text-sm text-gray-400 italic">
            Sélectionnez une classe pour voir la liste des élèves.
        </div>

        <div x-show="classId && loadingStudents"
             class="px-5 py-10 text-center text-sm text-gray-400">
            Chargement des élèves...
        </div>

        <div x-show="classId && !loadingStudents && students.length === 0"
             class="px-5 py-10 text-center text-sm text-gray-400 italic">
            Aucun élève inscrit dans cette classe.
        </div>

        <template x-if="students.length > 0">
            <div class="divide-y divide-gray-50 max-h-80 overflow-x-auto overflow-y-auto">
                <template x-for="s in students" :key="s.id">
                    <div class="flex min-w-[680px] items-center justify-between px-5 py-3 hover:bg-gray-50 transition-colors">
                        <label class="flex items-center gap-3 cursor-pointer flex-1 min-w-0">
                            <input type="checkbox" name="student_ids[]" :value="s.id"
                                   x-model="selected"
                                   class="w-4 h-4 rounded" style="accent-color:#1A3A6B;">
                            <div class="w-7 h-7 rounded-full flex items-center
                                        justify-center text-white text-xs font-bold flex-shrink-0"
                                 :style="'background:' + (s.gender==='M' ? '#1D4ED8' : '#BE185D')">
                                <span x-text="s.full_name.charAt(0)"></span>
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-bold text-gray-800 truncate" x-text="s.full_name"></p>
                                <p class="text-xs text-gray-400" x-text="s.matricule"></p>
                            </div>
                        </label>
                        <div class="flex items-center gap-2">
                            <button type="button"
                                    @click="
                                        if (!periodSelected) { alert(type === 'sequentiel' ? 'Veuillez choisir une séquence' : 'Veuillez choisir un trimestre'); return; }
                                        let url = '/bulletins/' + s.id + '?type=' + type;
                                        if (type === 'sequentiel') url += '&sequence_id=' + sequenceId;
                                        if (type === 'trimestriel') url += '&trimester_id=' + trimesterId;
                                        window.open(url, '_blank');
                                    "
                                    class="no-print text-xs font-bold px-3 py-1.5 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-100 flex-shrink-0 transition-colors">
                                Visualiser Bulletin
                            </button>
                            <button type="button"
                                    @click="window.open('/livrets/' + s.id, '_blank')"
                                    class="no-print text-xs font-bold px-3 py-1.5 rounded-lg text-white hover:opacity-90 flex-shrink-0 transition-colors"
                                    style="background-color: #1A3A6B;">
                                Livret Scolaire
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </template>
    </div>

    {{-- ── ACTIONS ─────────────────────────────────────────────────────── --}}
    <div class="flex flex-wrap gap-3">
        <button type="submit"
                :disabled="!canSubmit"
                :class="canSubmit
                    ? 'opacity-100 cursor-pointer hover:shadow-md'
                    : 'opacity-40 cursor-not-allowed'"
                class="flex items-center gap-2 px-6 py-3 rounded-xl text-white
                       text-sm font-bold transition-all"
                style="background-color:#E87722;">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0
                         012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293
                         .707V19a2 2 0 01-2 2z"/>
            </svg>
            <span x-text="selected.length > 0
                ? 'Générer ' + selected.length + ' bulletin(s) PDF'
                : 'Générer tous les bulletins de la classe'">
            </span>
        </button>

        @can('manage-parent-communication')
        <button type="submit"
                formaction="{{ route('communication.parents.bulletin.bulk-send') }}"
                formtarget="_self"
                :disabled="!canSubmit"
                :class="canSubmit
                    ? 'opacity-100 cursor-pointer hover:shadow-md'
                    : 'opacity-40 cursor-not-allowed'"
                class="flex items-center gap-2 px-6 py-3 rounded-xl text-white
                       text-sm font-bold transition-all"
                style="background-color:#1A5C2A;">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M3 10h2a2 2 0 012 2v6a2 2 0 01-2 2H3v-10zm0 0V6a2 2 0 012-2h2.5a2 2 0 012 2v4m0 0h6a2 2 0 012 2v3a2 2 0 01-2 2h-6a2 2 0 01-2-2v-3a2 2 0 012-2zm0 0V6a2 2 0 012-2H17"/>
            </svg>
            <span x-text="selected.length > 0
                ? 'Envoyer ' + selected.length + ' bulletins par WhatsApp'
                : 'Envoyer les bulletins de la classe par WhatsApp'">
            </span>
        </button>
        @endcan

        <button type="button"
                @click="
                    if (!classId) { alert('Veuillez d\'abord choisir une classe'); return; }
                    let url = '/livrets/bulk?class_group_id=' + classId;
                    selected.forEach(id => {
                        url += '&student_ids[]=' + id;
                    });
                    window.open(url, '_blank');
                "
                :disabled="!classId"
                :class="classId
                    ? 'opacity-100 cursor-pointer hover:shadow-md'
                    : 'opacity-40 cursor-not-allowed'"
                class="flex items-center gap-2 px-6 py-3 rounded-xl text-white
                       text-sm font-bold transition-all"
                style="background-color:#1A3A6B;">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
            </svg>
            <span x-text="selected.length > 0
                ? 'Générer ' + selected.length + ' livret(s) scolaire(s)'
                : 'Générer tous les livrets de la classe'">
            </span>
        </button>
    </div>

</form>
@endif

@endsection

@push('scripts')
<script>
function bulletinForm() {
    return {
        sectionId:       '',
        classId:         '',
        type:            'sequentiel',
        sequenceId:      '',
        trimesterId:     '',
        students:        [],
        selected:        [],
        allSelected:     false,
        loadingStudents: false,

        init() {},

        // ── Validation côté client : empêche la soumission incomplète ────
        get periodSelected() {
            if (this.type === 'sequentiel')  return !!this.sequenceId;
            if (this.type === 'trimestriel') return !!this.trimesterId;
            return true; // annuel n'a pas besoin de séquence/trimestre
        },

        get canSubmit() {
            return !!this.classId && this.periodSelected;
        },

        // ── Filtre des classes selon la section choisie ───────────────────
        onSectionChange() {
            const select = document.querySelector('select[name="class_group_id"]');
            if (!select) return;

            Array.from(select.options).forEach(opt => {
                if (!opt.value) { opt.hidden = false; return; }
                const optSection = opt.getAttribute('data-section');
                opt.hidden = this.sectionId ? (optSection !== this.sectionId) : false;
            });

            // Si la classe sélectionnée n'appartient plus à la section, la vider
            const current = select.querySelector(`option[value="${this.classId}"]`);
            if (this.classId && current && current.hidden) {
                this.classId = '';
                this.students = [];
                this.selected = [];
            }
        },

        async onClassChange() {
            this.students = [];
            this.selected = [];
            this.allSelected = false;
            if (!this.classId) return;
            await this.loadStudents();
        },

        async loadStudents() {
            this.loadingStudents = true;
            try {
                const res = await fetch(
                    `{{ route('bulletins.api.students') }}?class_id=${this.classId}`,
                    { headers: { 'X-Requested-With': 'XMLHttpRequest' } }
                );
                const data = await res.json();
                this.students = data.students || [];
            } catch (e) {
                console.error('Erreur chargement élèves :', e);
                this.students = [];
            } finally {
                this.loadingStudents = false;
            }
        },

        toggleAll() {
            this.allSelected = !this.allSelected;
            this.selected = this.allSelected
                ? this.students.map(s => s.id)
                : [];
        }
    }
}
</script>
@endpush
