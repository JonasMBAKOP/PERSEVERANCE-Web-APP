@php
    $positionsList = $positionLabels ?? [
        'enseignant'             => 'Enseignant(e)',
        'directeur'              => 'Directeur',
        'prefet_des_etudes'      => 'Préfet des études',
        'econome'                => 'Économe',
        'surveillant_general'    => 'Surveillant général',
        'surveillant_de_secteur' => 'Surveillant de secteur',
        'vigile'                 => 'Vigile',
        'agent_d_entretien'      => 'Agent d\'entretien',
        'secretaire'             => 'Secrétaire',
        'infirmier'              => 'Infirmier(ère)',
        'autre'                  => 'Autre',
    ];

    $currentPositions = old('positions',
        isset($staff)
            ? $staff->positions->pluck('position')->toArray()
            : ['enseignant']
    );

    foreach ($currentPositions as $position) {
        if (! array_key_exists($position, $positionsList)) {
            $positionsList[$position] = \App\Models\Staff::positionLabels()[$position]
                ?? ucfirst(str_replace(['-', '_'], ' ', $position));
        }
    }
    $currentPrimary = old('primary_position',
        isset($staff)
            ? ($staff->positions->where('is_primary', true)->first()?->position)
            : 'enseignant'
    );

    $roleLabels = [
        'super-admin'            => 'Super-admin',
        'directeur'              => 'Directeur',
        'censeur'                => 'Préfet des études',
        'econome'                => 'Économe',
        'surveillant-general'    => 'Surveillant général',
        'surveillant-de-secteur' => 'Surveillant de secteur',
        'secretaire'             => 'Secrétaire',
        'infirmier'              => 'Infirmier(ère)',
        'enseignant'             => 'Enseignant(e)',
    ];

    $staffModel = $staff ?? null;

    $isCenseurAssigned = function ($sectionId, $cycle) use ($staffModel) {
        $oldAssignments = old('censeur_assignments');
        if (is_array($oldAssignments)) {
            return isset($oldAssignments[$sectionId]) && is_array($oldAssignments[$sectionId]) && in_array($cycle, $oldAssignments[$sectionId]);
        }
        if ($staffModel && $staffModel->relationLoaded('censeurAssignments')) {
            return $staffModel->censeurAssignments->where('section_id', $sectionId)->where('cycle', $cycle)->isNotEmpty();
        }
        return false;
    };
@endphp

{{-- ══════════════════════════════════════════════════════════════════════
     BLOC 1 : POSTES OCCUPÉS & AFFECTATION PRÉFET DES ÉTUDES (PLEINE LARGEUR)
══════════════════════════════════════════════════════════════════════ --}}
<div class="w-full">
    <div id="positions-grid" class="grid grid-cols-1 gap-6 items-start">

        {{-- ── COLONNE GAUCHE : POSTES OCCUPÉS ── --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 transition-all">
            <div class="flex items-center justify-between mb-4 pb-3 border-b border-gray-100">
                <h3 class="text-sm font-semibold uppercase tracking-wider text-[#1A3A6B] flex items-center gap-2">
                    <span class="flex items-center justify-center w-7 h-7 rounded-lg bg-blue-50 text-[#1A3A6B]">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </span>
                    Postes occupés <span class="text-red-500">*</span>
                </h3>
                <span class="text-[11px] font-semibold text-gray-500 bg-gray-100 px-2.5 py-0.5 rounded-full">
                    {{ count($currentPositions) }} sélectionné(s)
                </span>
            </div>

            @error('positions')
            <p class="mb-3 text-xs text-red-500 bg-red-50 border border-red-200 px-3 py-2 rounded-lg flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                {{ $message }}
            </p>
            @enderror

            {{-- Grille 2 colonnes des postes --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                @foreach($positionsList as $val => $lbl)
                @if(auth()->user()->hasRole('censeur') && in_array($val, ['directeur', 'prefet_des_etudes'], true)) @continue @endif
                @if(! auth()->user()->hasRole('super-admin') && $val === 'directeur') @continue @endif
                @php $isChecked = in_array($val, $currentPositions); @endphp
                <div class="group flex items-center justify-between px-3.5 py-2.5 border rounded-xl
                            cursor-pointer transition-all duration-150 select-none
                            {{ $isChecked
                                ? 'border-blue-300 bg-blue-50/70 shadow-sm'
                                : 'border-gray-200 hover:border-gray-300 hover:bg-gray-50/70' }}">
                    <label class="flex items-center gap-2.5 cursor-pointer flex-1 min-w-0">
                        <input type="checkbox"
                               name="positions[]"
                               value="{{ $val }}"
                               {{ $isChecked ? 'checked' : '' }}
                               class="w-4 h-4 rounded shrink-0"
                               style="accent-color:#1A3A6B;"
                               onchange="toggleCard(this); checkCenseurVisibility();">
                        <span class="text-sm font-medium text-gray-800 truncate">{{ $lbl }}</span>
                    </label>
                    <label class="flex items-center gap-1 text-[11px] cursor-pointer shrink-0 ml-2 {{ $isChecked ? '' : 'opacity-30' }}"
                           title="Définir comme poste principal">
                        <input type="radio"
                               name="primary_position"
                               value="{{ $val }}"
                               {{ $currentPrimary === $val ? 'checked' : '' }}
                               class="w-3.5 h-3.5"
                               style="accent-color:#E87722;">
                        <span class="font-bold text-[#E87722]">Principal</span>
                    </label>
                </div>
                @endforeach
            </div>

            <p class="mt-3 text-xs text-gray-400 flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Cochez les postes occupés et marquez le poste principal avec <span class="font-bold text-[#E87722]">Principal</span>.
            </p>
        </div>

        {{-- ── COLONNE DROITE : AFFECTATION PRÉFET DES ÉTUDES ── --}}
        <div id="censeur-affectation-card"
             class="bg-gradient-to-br from-blue-50/80 to-indigo-50/40 rounded-2xl
                    border border-blue-200 p-6 transition-all duration-300 shadow-sm
                    {{ in_array('prefet_des_etudes', $currentPositions) ? '' : 'hidden' }}">

            <div class="flex items-start justify-between mb-4 pb-3 border-b border-blue-100">
                <div>
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-[#1A3A6B] flex items-center gap-2">
                        <span class="flex items-center justify-center w-7 h-7 rounded-lg bg-blue-600 text-white shrink-0 shadow-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5m0 0h4m-4 0V11m0 0l3-3m-3 3l-3-3"/>
                            </svg>
                        </span>
                        Affectation Préfet des Études
                    </h3>
                    <p class="text-xs text-blue-600/70 mt-1 ml-9">
                        Sélectionnez les sections et cycles placés sous la responsabilité de ce préfet.
                    </p>
                </div>
                <span class="shrink-0 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider rounded-full bg-blue-600 text-white shadow-sm">
                    Sections &amp; Cycles
                </span>
            </div>

            @if(isset($sections) && $sections->count())
            <div class="space-y-2.5">
                @foreach($sections as $sec)
                @php
                    $has1er      = $isCenseurAssigned($sec->id, '1er');
                    $has2nd      = $isCenseurAssigned($sec->id, '2nd');
                    $isSecActive = $has1er || $has2nd;
                @endphp
                <div class="group p-3.5 rounded-xl border transition-all duration-150
                            {{ $isSecActive
                                ? 'border-blue-300 bg-white shadow-sm'
                                : 'border-blue-100/80 bg-white/70 hover:bg-white hover:border-blue-200' }}">

                    <div class="flex items-center gap-2 mb-2">
                        <span class="w-2.5 h-2.5 rounded-full shrink-0 transition-colors {{ $isSecActive ? 'bg-blue-600' : 'bg-gray-300' }}"></span>
                        <span class="font-semibold text-sm text-gray-800">{{ $sec->name }}</span>
                        <span class="text-xs text-gray-400 font-normal">({{ $sec->code }})</span>
                        @if($isSecActive)
                        <span class="ml-auto text-[10px] font-bold uppercase text-blue-700 bg-blue-50 px-2 py-0.5 rounded-full border border-blue-100">Affecté</span>
                        @endif
                    </div>

                    <div class="flex items-center gap-6 pl-5">
                        <label class="flex items-center gap-2 cursor-pointer group/cycle">
                            <input type="checkbox"
                                   name="censeur_assignments[{{ $sec->id }}][]"
                                   value="1er"
                                   {{ $has1er ? 'checked' : '' }}
                                   class="w-4 h-4 rounded"
                                   style="accent-color:#1A3A6B;"
                                   onchange="updateSecCardHighlight(this)">
                            <span class="text-sm font-medium text-gray-600 group-hover/cycle:text-blue-900 transition-colors">
                                1<sup>er</sup> Cycle
                            </span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer group/cycle">
                            <input type="checkbox"
                                   name="censeur_assignments[{{ $sec->id }}][]"
                                   value="2nd"
                                   {{ $has2nd ? 'checked' : '' }}
                                   class="w-4 h-4 rounded"
                                   style="accent-color:#1A3A6B;"
                                   onchange="updateSecCardHighlight(this)">
                            <span class="text-sm font-medium text-gray-600 group-hover/cycle:text-blue-900 transition-colors">
                                2<sup>nd</sup> Cycle
                            </span>
                        </label>
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <div class="flex flex-col items-center justify-center py-8 text-center">
                <svg class="w-10 h-10 text-blue-200 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16"/>
                </svg>
                <p class="text-sm text-gray-500 font-medium">Aucune section disponible dans l'établissement.</p>
            </div>
            @endif
        </div>

    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════
     BLOC 2 : COMPTE DE CONNEXION (PLEINE LARGEUR AVEC mt-6)
══════════════════════════════════════════════════════════════════════ --}}
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mt-6 transition-all"
     x-data="{
         mode: '{{ old('user_option', isset($staff) && $staff->user_id ? 'existing' : 'none') }}'
     }">
    <input type="hidden" name="user_option" :value="mode">

    <div class="flex items-center justify-between mb-5 pb-3 border-b border-gray-100">
        <h3 class="text-sm font-semibold uppercase tracking-wider text-[#1A3A6B] flex items-center gap-2">
            <span class="flex items-center justify-center w-7 h-7 rounded-lg bg-blue-50 text-[#1A3A6B]">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
            </span>
            Compte de connexion
            <span class="text-gray-400 font-normal normal-case text-xs tracking-normal">
                (Optionnel)
            </span>
        </h3>
        <span class="text-xs text-gray-400">Accès au portail COPTAN</span>
    </div>

    {{-- Sélecteur d'onglets (boutons réactifs stables) --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-6">
        <button type="button"
                @click="mode = 'none'"
                class="flex items-center justify-center gap-2 p-3.5 rounded-xl border
                       text-sm font-medium transition-all duration-150 select-none cursor-pointer"
                :class="mode === 'none'
                    ? 'border-blue-500 bg-blue-50 text-blue-900 font-bold shadow-sm ring-1 ring-blue-400'
                    : 'border-gray-200 text-gray-600 hover:bg-gray-50/80'">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
            </svg>
            Sans compte
        </button>

        <button type="button"
                @click="mode = 'existing'"
                class="flex items-center justify-center gap-2 p-3.5 rounded-xl border
                       text-sm font-medium transition-all duration-150 select-none cursor-pointer"
                :class="mode === 'existing'
                    ? 'border-blue-500 bg-blue-50 text-blue-900 font-bold shadow-sm ring-1 ring-blue-400'
                    : 'border-gray-200 text-gray-600 hover:bg-gray-50/80'">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
            </svg>
            Compte existant
        </button>

        <button type="button"
                @click="mode = 'create'"
                class="flex items-center justify-center gap-2 p-3.5 rounded-xl border
                       text-sm font-medium transition-all duration-150 select-none cursor-pointer"
                :class="mode === 'create'
                    ? 'border-blue-500 bg-blue-50 text-blue-900 font-bold shadow-sm ring-1 ring-blue-400'
                    : 'border-gray-200 text-gray-600 hover:bg-gray-50/80'">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
            </svg>
            Créer un compte
        </button>
    </div>

    {{-- Mode : Compte existant --}}
    <div x-show="mode === 'existing'" x-cloak class="p-4 bg-blue-50/40 border border-blue-100 rounded-xl">
        <label class="block text-sm font-medium text-gray-700 mb-1.5">
            Sélectionner un compte utilisateur non attribué
        </label>
        <select name="user_id"
                class="w-full px-3.5 py-2.5 border border-gray-300 rounded-lg text-sm
                       focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-500 bg-white">
            <option value="">-- Choisir un utilisateur --</option>
            @foreach($availableUsers as $u)
            <option value="{{ $u->id }}"
                    {{ old('user_id', $staff->user_id ?? '') == $u->id ? 'selected' : '' }}>
                {{ $u->name }} ({{ $u->email }})
            </option>
            @endforeach
        </select>
        @error('user_id')
        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
        @enderror
    </div>

    {{-- Mode : Créer un compte --}}
    <div x-show="mode === 'create'" x-cloak class="p-5 bg-blue-50/40 border border-blue-100 rounded-xl space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Nom d'utilisateur <span class="text-red-500">*</span>
                </label>
                <input type="text" name="new_user_name"
                       value="{{ old('new_user_name') }}"
                       placeholder="Ex: j.kamga"
                       class="w-full px-3.5 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-500 bg-white">
                @error('new_user_name')
                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Adresse email <span class="text-red-500">*</span>
                </label>
                <input type="email" name="new_user_email"
                       value="{{ old('new_user_email') }}"
                       placeholder="exemple@coptan.cm"
                       class="w-full px-3.5 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-500 bg-white">
                @error('new_user_email')
                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Mot de passe <span class="text-red-500">*</span>
                </label>
                <input type="password" name="new_user_password"
                       placeholder="••••••••"
                       class="w-full px-3.5 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-500 bg-white">
                @error('new_user_password')
                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Rôle d'accès au système
                </label>
                <select name="new_user_role"
                        class="w-full px-3.5 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-500 bg-white">
                    <option value="">Sans rôle spécifique</option>
                    @foreach($roles ?? \Spatie\Permission\Models\Role::orderBy('name')->get() as $role)
                    <option value="{{ $role->name }}"
                            {{ old('new_user_role') === $role->name ? 'selected' : '' }}>
                        {{ $roleLabels[$role->name] ?? ucfirst(str_replace('-', ' ', $role->name)) }}
                    </option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════
     BARRE D'ACTIONS : Valider / Annuler (en bas du formulaire)
══════════════════════════════════════════════════════════════════════ --}}
<div class="w-full mt-4">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 px-6 py-4 flex flex-col sm:flex-row items-center justify-between gap-3">

        <p class="text-xs text-gray-400 flex items-center gap-1.5 shrink-0">
            <svg class="w-3.5 h-3.5 text-gray-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Vérifiez les informations avant de confirmer
        </p>

        <div class="flex items-center gap-3 shrink-0">
            @if(isset($staff))
            {{-- Page de modification --}}
            <a href="{{ route('staff.show', $staff) }}"
               class="px-5 py-2.5 rounded-xl text-sm font-medium text-gray-600 border border-gray-200 hover:bg-gray-50 transition-colors">
                Annuler
            </a>
            <button type="submit" form="edit-staff-form"
                    class="px-6 py-2.5 rounded-xl text-white font-bold text-sm flex items-center gap-2 shadow-sm transition-all hover:shadow-md"
                    style="background-color:#1A3A6B;">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
                </svg>
                Enregistrer les modifications
            </button>
            @else
            {{-- Page de création --}}
            <a href="{{ route('staff.index') }}"
               class="px-5 py-2.5 rounded-xl text-sm font-medium text-gray-600 border border-gray-200 hover:bg-gray-50 transition-colors">
                Annuler
            </a>
            <button type="submit" form="create-staff-form"
                    class="px-6 py-2.5 rounded-xl text-white font-bold text-sm flex items-center gap-2 shadow-sm transition-all hover:shadow-md"
                    style="background-color:#1A5C2A;">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Créer le dossier du personnel
            </button>
            @endif
        </div>

    </div>
</div>

<script>
/**
 * Active/Désactive la carte visuelle d'un poste
 */
function toggleCard(checkbox) {
    const card = checkbox.closest('div[class*="border"]');
    if (!card) return;
    const radioLabel = card.querySelector('label:last-of-type');

    if (checkbox.checked) {
        card.classList.add('border-blue-300', 'bg-blue-50/70', 'shadow-sm');
        card.classList.remove('border-gray-200', 'hover:border-gray-300', 'hover:bg-gray-50/70');
        radioLabel?.classList.remove('opacity-30');
    } else {
        card.classList.remove('border-blue-300', 'bg-blue-50/70', 'shadow-sm');
        card.classList.add('border-gray-200', 'hover:border-gray-300', 'hover:bg-gray-50/70');
        radioLabel?.classList.add('opacity-30');

        const radio = card.querySelector('input[type="radio"]');
        if (radio) radio.checked = false;
    }
}

/**
 * Affiche/Cache l'affectation du préfet des études
 * et adapte la largeur du bloc Postes occupés
 */
function checkCenseurVisibility() {
    const cb = document.querySelector(
        'input[name="positions[]"][value="prefet_des_etudes"]'
    );
    const affectationCard = document.getElementById('censeur-affectation-card');
    const positionsGrid   = document.getElementById('positions-grid');
    if (!affectationCard) return;

    if (cb && cb.checked) {
        affectationCard.classList.remove('hidden');
        // Passer en 2 colonnes dès xl
        positionsGrid?.classList.add('xl:grid-cols-2');
    } else {
        affectationCard.classList.add('hidden');
        // Retour pleine largeur (1 colonne)
        positionsGrid?.classList.remove('xl:grid-cols-2');
    }
}

/**
 * Met en valeur la carte de section quand un cycle est sélectionné
 */
function updateSecCardHighlight(checkbox) {
    const parentBox = checkbox.closest('div.group') || checkbox.closest('div[class*="rounded-xl"]');
    if (!parentBox) return;

    const anyChecked = parentBox.querySelectorAll('input[type="checkbox"]:checked').length > 0;
    const dot = parentBox.querySelector('span[class*="rounded-full"]');

    if (anyChecked) {
        parentBox.classList.add('border-blue-300', 'bg-white', 'shadow-sm');
        parentBox.classList.remove('border-blue-100/80', 'bg-white/70');
        if (dot) {
            dot.classList.add('bg-blue-600');
            dot.classList.remove('bg-gray-300');
        }
    } else {
        parentBox.classList.remove('border-blue-300', 'bg-white', 'shadow-sm');
        parentBox.classList.add('border-blue-100/80', 'bg-white/70');
        if (dot) {
            dot.classList.remove('bg-blue-600');
            dot.classList.add('bg-gray-300');
        }
    }
}

document.addEventListener('DOMContentLoaded', function () {
    checkCenseurVisibility();
});
</script>

