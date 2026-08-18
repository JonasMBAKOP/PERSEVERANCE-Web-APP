{{--
    Partial formulaire personnel
    Variables : $staff (nullable), $positionLabels, $contractLabels, $diplomas,
                $availableUsers, $roles (create only)
--}}

@php
    $isEdit = isset($staff);
    $selectedPositions = old('positions', $isEdit
        ? $staff->positions->pluck('position')->toArray()
        : ['enseignant']);
@endphp

{{-- ── INFORMATIONS PERSONNELLES ────────────────────────────────────────── --}}
<h2 class="text-sm font-semibold uppercase tracking-wider mb-4 pb-2
           border-b border-gray-100"
    style="color: #1A3A6B;">
    Informations personnelles
</h2>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
            Nom <span class="text-red-500">*</span>
        </label>
        <input type="text" name="last_name"
               value="{{ old('last_name', $staff->last_name ?? '') }}"
               placeholder="Ex: NTANKEU"
               class="w-full px-3 py-2.5 border rounded-lg text-sm focus:outline-none
                      focus:border-blue-400 @error('last_name') border-red-400 @else border-gray-200 @enderror">
        @error('last_name')
            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
            Prénom <span class="text-red-500">*</span>
        </label>
        <input type="text" name="first_name"
               value="{{ old('first_name', $staff->first_name ?? '') }}"
               placeholder="Ex: Jean-Paul"
               class="w-full px-3 py-2.5 border rounded-lg text-sm focus:outline-none
                      focus:border-blue-400 @error('first_name') border-red-400 @else border-gray-200 @enderror">
        @error('first_name')
            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
            Sexe <span class="text-red-500">*</span>
        </label>
        <select name="gender"
                class="w-full px-3 py-2.5 border rounded-lg text-sm focus:outline-none
                       focus:border-blue-400 bg-white
                       @error('gender') border-red-400 @else border-gray-200 @enderror">
            <option value="">Sélectionner...</option>
            <option value="M" {{ old('gender', $staff->gender ?? '') === 'M' ? 'selected' : '' }}>
                Masculin
            </option>
            <option value="F" {{ old('gender', $staff->gender ?? '') === 'F' ? 'selected' : '' }}>
                Féminin
            </option>
        </select>
        @error('gender')
            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
            Date de naissance
        </label>
        <input type="date" name="date_of_birth"
               value="{{ old('date_of_birth', isset($staff) && $staff->date_of_birth
                   ? $staff->date_of_birth->format('Y-m-d') : '') }}"
               class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm
                      focus:outline-none focus:border-blue-400">
    </div>


    {{-- ── Ligne 1 : Diplôme + Établissement d'obtention ── --}}
    <div class="sm:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-4">

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                Diplôme / Qualification
            </label>
            <select name="diploma"
                    class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm
                           focus:outline-none focus:border-blue-400 bg-white">
                <option value="">Non renseigné</option>
                @foreach($diplomas as $diploma)
                <option value="{{ $diploma }}"
                        {{ old('diploma', $staff->diploma ?? '') === $diploma ? 'selected' : '' }}>
                    {{ $diploma }}
                </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                Établissement d'obtention du diplôme
            </label>
            <input type="text" name="origin_school"
                   value="{{ old('origin_school', $staff->origin_school ?? '') }}"
                   placeholder="Ex : ENSET Douala, Université de Yaoundé I…"
                   class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm
                          focus:outline-none focus:border-blue-400">
        </div>

    </div>

    <div class="sm:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">Photo</label>
        @if($isEdit && $staff->photo)
        <div class="flex items-center gap-3 mb-2">
            <img src="{{ $staff->photo_url }}" alt=""
                 class="w-12 h-12 rounded-full object-cover ring-2 ring-gray-100">
            <label class="flex items-center gap-2 text-sm text-gray-600">
                <input type="checkbox" name="remove_photo" value="1"
                       class="rounded" style="accent-color: #1A3A6B;">
                Supprimer la photo actuelle
            </label>
        </div>
        @endif
        <input type="file" name="photo" accept="image/jpeg,image/png"
               class="w-full text-sm text-gray-500 file:mr-3 file:py-2 file:px-4
                      file:rounded-lg file:border-0 file:text-sm file:font-medium
                      file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
        @error('photo')
            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
        @enderror
    </div>

</div>

{{-- ── CONTACTS ─────────────────────────────────────────────────────── --}}
<h2 class="text-sm font-semibold uppercase tracking-wider mb-4 pb-2
           border-b border-gray-100"
    style="color: #1A3A6B;">
    Contacts
</h2>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Téléphone</label>
        <input type="text" name="phone"
               value="{{ old('phone', $staff->phone ?? '') }}"
               placeholder="Ex: +237 6XX XXX XXX"
               class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm
                      focus:outline-none focus:border-blue-400">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">E-mail professionnel</label>
        <input type="email" name="email"
               value="{{ old('email', $staff->email ?? '') }}"
               placeholder="exemple@coptan.cm"
               class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm
                      focus:outline-none focus:border-blue-400">
    </div>

</div>

{{-- ── CARRIÈRE ─────────────────────────────────────────────────────── --}}
<h2 class="text-sm font-semibold uppercase tracking-wider mb-4 pb-2
           border-b border-gray-100"
    style="color: #1A3A6B;">
    Carrière
</h2>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">


    {{-- ── Ligne 2 : Date d'embauche + Type de contrat + Salaire/Tarif ── --}}
    <div class="sm:col-span-2 grid grid-cols-1 sm:grid-cols-3 gap-4">

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                Date d'embauche
            </label>
            <input type="date" name="start_date"
                   value="{{ old('start_date', isset($staff) && $staff->start_date
                       ? $staff->start_date->format('Y-m-d') : '') }}"
                   class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm
                          focus:outline-none focus:border-blue-400">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                Type de contrat <span class="text-red-500">*</span>
            </label>
            <select name="contract_type"
                    class="w-full px-3 py-2.5 border rounded-lg text-sm focus:outline-none
                           focus:border-blue-400 bg-white
                           @error('contract_type') border-red-400 @else border-gray-200 @enderror">
                @foreach($contractLabels as $value => $label)
                <option value="{{ $value }}"
                        {{ old('contract_type', $staff->contract_type ?? 'permanent') === $value ? 'selected' : '' }}>
                    {{ $label }}
                </option>
                @endforeach
            </select>
            @error('contract_type')
                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
            @enderror
        </div>

        {{-- Salaire mensuel (permanent) ou Tarif horaire (vacataire) --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                Salaire mensuel / Tarif horaire
            </label>
            <div class="relative">
                <input type="number" name="monthly_salary"
                       value="{{ old('monthly_salary', $staff->monthly_salary ?? '') }}"
                       placeholder="Ex : 120000"
                       min="0"
                       class="w-full px-3 py-2.5 pr-24 border border-gray-200 rounded-lg text-sm
                              focus:outline-none focus:border-blue-400">
                <span class="absolute inset-y-0 right-3 flex items-center text-xs text-gray-400 pointer-events-none">
                    FCFA / mois
                </span>
            </div>
            <div class="relative mt-2">
                <input type="number" name="hourly_rate"
                       value="{{ old('hourly_rate', $staff->hourly_rate ?? '') }}"
                       placeholder="Ex : 1500"
                       min="0"
                       class="w-full px-3 py-2.5 pr-20 border border-gray-200 rounded-lg text-sm
                              focus:outline-none focus:border-blue-400">
                <span class="absolute inset-y-0 right-3 flex items-center text-xs text-gray-400 pointer-events-none">
                    FCFA / h
                </span>
            </div>
        </div>

    </div>

</div>

{{-- ══════════════════════════════════════════════════════════════════
     POSTES — PLEINE LARGEUR — 2 colonnes : postes | affectation préfet
════════════════════════════════════════════════════════════════════ --}}
<div class="w-full">
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 items-start mb-6">

        {{-- ── Colonne gauche : Postes ── --}}
        <div class="bg-gray-50/50 rounded-xl border border-gray-200 p-5">
            <h2 class="text-sm font-semibold uppercase tracking-wider mb-4 pb-2
                       border-b border-gray-200 flex items-center gap-2"
                style="color: #1A3A6B;">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                Postes occupés <span class="text-red-400">*</span>
            </h2>

            @error('positions')
                <p class="mb-3 text-xs text-red-500 bg-red-50 border border-red-200 px-3 py-2 rounded-lg flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    {{ $message }}
                </p>
            @enderror
            @error('primary_position')
                <p class="mb-3 text-xs text-red-500">{{ $message }}</p>
            @enderror

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                @foreach($positionLabels as $value => $label)
                <label class="flex items-start gap-3 p-3 rounded-xl border cursor-pointer
                              transition-all duration-150 select-none
                              hover:bg-white hover:shadow-sm"
                       :class="positions.includes('{{ $value }}')
                           ? 'border-blue-300 bg-blue-50 shadow-sm' : 'border-gray-200 bg-white/60'"
                       @click.prevent="
                           if (positions.includes('{{ $value }}')) {
                               positions = positions.filter(p => p !== '{{ $value }}');
                               if (primary === '{{ $value }}') primary = positions[0] || '';
                           } else {
                               positions.push('{{ $value }}');
                               if (!primary) primary = '{{ $value }}';
                           }
                           $nextTick(() => checkCenseurVisibilityForm());">
                    <input type="checkbox" name="positions[]" value="{{ $value }}"
                           {{ in_array($value, $selectedPositions) ? 'checked' : '' }}
                           :checked="positions.includes('{{ $value }}')"
                           class="mt-0.5 w-4 h-4 rounded shrink-0" style="accent-color: #1A3A6B;"
                           @change="if ($event.target.checked) {
                               if (!positions.includes('{{ $value }}')) positions.push('{{ $value }}');
                               if (!primary) primary = '{{ $value }}';
                           } else {
                               positions = positions.filter(p => p !== '{{ $value }}');
                               if (primary === '{{ $value }}') primary = positions[0] || '';
                           }
                           $nextTick(() => checkCenseurVisibilityForm());">
                    <p class="text-sm font-medium text-gray-800">{{ $label }}</p>
                </label>
                @endforeach
            </div>

            {{-- Poste principal --}}
            <div class="mt-4 pt-4 border-t border-gray-200">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Poste principal <span class="text-red-500">*</span>
                </label>
                <select x-model="primary" name="primary_position"
                        class="w-full px-3 py-2.5 border border-gray-200
                               rounded-lg text-sm focus:outline-none focus:border-blue-400 bg-white">
                    <template x-for="pos in positions" :key="pos">
                        <option :value="pos" x-text="positionLabels[pos] || pos"></option>
                    </template>
                </select>
                <p class="mt-1 text-xs text-gray-400">
                    Sélectionnez au moins un poste ci-dessus.
                </p>
            </div>
        </div>

        {{-- ── Colonne droite : Affectation Préfet des Études ── --}}
        <div id="censeur-affectation-form-card"
             class="bg-gradient-to-br from-blue-50 to-indigo-50/40 rounded-xl
                    border border-blue-200 p-5 transition-all duration-300
                    {{ in_array('prefet_des_etudes', $selectedPositions) ? '' : 'hidden' }}">

            {{-- En-tête --}}
            <div class="flex items-start justify-between mb-4 pb-3 border-b border-blue-100">
                <div>
                    <h2 class="text-sm font-semibold uppercase tracking-wider flex items-center gap-2"
                        style="color: #1A3A6B;">
                        <span class="flex items-center justify-center w-6 h-6 rounded-lg bg-blue-600 text-white shrink-0">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5m0 0h4m-4 0V11m0 0l3-3m-3 3l-3-3"/>
                            </svg>
                        </span>
                        Affectation Préfet des Études
                    </h2>
                    <p class="text-xs text-blue-600/70 mt-1 ml-8">
                        Choisissez la/les section(s) et le/les cycle(s) sous sa responsabilité.
                    </p>
                </div>
                <span class="shrink-0 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider
                             rounded-full bg-blue-600 text-white">
                    Sections &amp; Cycles
                </span>
            </div>

            @if(isset($sections) && $sections->count())
            <div class="space-y-2">
                @foreach($sections as $sec)
                @php
                    $has1er      = false;
                    $has2nd      = false;
                    $isSecActive = false;
                @endphp
                <div class="p-3.5 rounded-xl border transition-all
                            border-blue-100 bg-white/60 hover:bg-white hover:border-blue-200">
                    <div class="flex items-center gap-2 mb-2.5">
                        <span class="w-2 h-2 rounded-full bg-gray-300 shrink-0"></span>
                        <span class="font-semibold text-sm text-gray-800">{{ $sec->name }}</span>
                        <span class="text-xs text-gray-400">({{ $sec->code }})</span>
                    </div>
                    <div class="flex items-center gap-6 pl-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox"
                                   name="censeur_assignments[{{ $sec->id }}][]"
                                   value="1er"
                                   class="w-4 h-4 rounded"
                                   style="accent-color:#1A3A6B;"
                                   onchange="updateFormSecHighlight(this)">
                            <span class="text-sm font-medium text-gray-600">1<sup>er</sup> Cycle</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox"
                                   name="censeur_assignments[{{ $sec->id }}][]"
                                   value="2nd"
                                   class="w-4 h-4 rounded"
                                   style="accent-color:#1A3A6B;"
                                   onchange="updateFormSecHighlight(this)">
                            <span class="text-sm font-medium text-gray-600">2<sup>nd</sup> Cycle</span>
                        </label>
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <div class="flex flex-col items-center justify-center py-8 text-center">
                <svg class="w-8 h-8 text-blue-200 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16"/>
                </svg>
                <p class="text-sm text-gray-400">Aucune section disponible.</p>
            </div>
            @endif
        </div>

    </div>
</div>

<script>
function checkCenseurVisibilityForm() {
    const prefetCb = document.querySelector('input[name="positions[]"][value="prefet_des_etudes"]');
    const card = document.getElementById('censeur-affectation-form-card');
    if (!card) return;
    if (prefetCb && prefetCb.checked) {
        card.classList.remove('hidden');
    } else {
        card.classList.add('hidden');
    }
}
function updateFormSecHighlight(checkbox) {
    const parentBox = checkbox.closest('div.p-3\\.5') || checkbox.closest('div[class*="rounded-xl"]');
    if (!parentBox) return;
    const anyChecked = parentBox.querySelectorAll('input[type="checkbox"]:checked').length > 0;
    const dot = parentBox.querySelector('span.rounded-full, span[class*="rounded-full"]');
    if (anyChecked) {
        parentBox.classList.add('border-blue-300', 'bg-white', 'shadow-sm');
        parentBox.classList.remove('border-blue-100', 'bg-white/60');
        if (dot) { dot.classList.add('bg-blue-600'); dot.classList.remove('bg-gray-300'); }
    } else {
        parentBox.classList.remove('border-blue-300', 'bg-white', 'shadow-sm');
        parentBox.classList.add('border-blue-100', 'bg-white/60');
        if (dot) { dot.classList.remove('bg-blue-600'); dot.classList.add('bg-gray-300'); }
    }
}
document.addEventListener('DOMContentLoaded', checkCenseurVisibilityForm);
</script>


@if($isEdit)
{{-- ── STATUT (édition) ─────────────────────────────────────────────── --}}
<div class="mb-6">
    <label class="flex items-center gap-3 p-3 rounded-lg border border-gray-200
                  cursor-pointer hover:bg-gray-50 w-fit">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" name="is_active" value="1"
               {{ old('is_active', $staff->is_active) ? 'checked' : '' }}
               class="rounded" style="accent-color: #1A3A6B;">
        <span class="text-sm font-medium text-gray-700">Membre actif</span>
    </label>
</div>
@endif

{{-- ── COMPTE UTILISATEUR ─────────────────────────────────────────────── --}}
<h2 class="text-sm font-semibold uppercase tracking-wider mb-4 pb-2
           border-b border-gray-100"
    style="color: #1A3A6B;">
    Compte utilisateur
</h2>

<div class="mb-6">

    @unless($isEdit)
    <div class="flex flex-wrap gap-2 mb-4">
        @foreach([
            'none'   => 'Sans compte',
            'link'   => 'Lier un compte existant',
            'create' => 'Créer un nouveau compte',
        ] as $key => $label)
        <button type="button" @click="accountMode = '{{ $key }}'"
                :class="accountMode === '{{ $key }}'
                    ? 'bg-blue-600 text-white'
                    : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors">
            {{ $label }}
        </button>
        @endforeach
    </div>
    @endunless

    {{-- Lier compte existant --}}
    <div x-show="accountMode === 'link' || {{ $isEdit ? 'true' : 'false' }}" x-transition>
        <label class="block text-sm font-medium text-gray-700 mb-1">
            Compte utilisateur lié
        </label>
        <select name="user_id"
                :disabled="accountMode !== 'link' && {{ $isEdit ? 'false' : 'true' }}"
                class="w-full sm:max-w-md px-3 py-2.5 border border-gray-200
                       rounded-lg text-sm focus:outline-none focus:border-blue-400 bg-white
                       disabled:bg-gray-50 disabled:text-gray-400">
            <option value="">Aucun compte lié</option>
            @foreach($availableUsers as $user)
            <option value="{{ $user->id }}"
                    {{ (string) old('user_id', $staff->user_id ?? '') === (string) $user->id ? 'selected' : '' }}>
                {{ $user->name }} — {{ $user->email }}
            </option>
            @endforeach
        </select>
        @error('user_id')
            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
        @enderror
        <p class="mt-1 text-xs text-gray-400">
            Seuls les comptes non encore liés à une fiche personnel sont proposés.
        </p>
    </div>

    {{-- Créer compte --}}
    @unless($isEdit)
    <div x-show="accountMode === 'create'" x-transition class="space-y-4 mt-2">

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    E-mail de connexion <span class="text-red-500">*</span>
                </label>
                <input type="email" name="account_email" value="{{ old('account_email') }}"
                       placeholder="nom@coptan.cm"
                       class="w-full px-3 py-2.5 border rounded-lg text-sm focus:outline-none
                              focus:border-blue-400 @error('account_email') border-red-400 @else border-gray-200 @enderror">
                @error('account_email')
                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Mot de passe <span class="text-red-500">*</span>
                </label>
                <input type="password" name="account_password"
                       class="w-full px-3 py-2.5 border rounded-lg text-sm focus:outline-none
                              focus:border-blue-400 @error('account_password') border-red-400 @else border-gray-200 @enderror">
                @error('account_password')
                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Confirmer le mot de passe
                </label>
                <input type="password" name="account_password_confirmation"
                       class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm
                              focus:outline-none focus:border-blue-400">
            </div>
        </div>

        <div>
            <p class="text-sm font-medium text-gray-700 mb-2">Rôle(s) du compte</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                @foreach($roles as $role)
                <label class="flex items-center gap-2 p-2 rounded-lg border border-gray-200
                              cursor-pointer hover:bg-gray-50">
                    <input type="checkbox" name="account_roles[]" value="{{ $role->name }}"
                           {{ in_array($role->name, old('account_roles', ['enseignant'])) ? 'checked' : '' }}
                           class="rounded" style="accent-color: #1A3A6B;">
                    <span class="text-sm text-gray-700">
                        {{ ucfirst(str_replace('-', ' ', $role->name)) }}
                    </span>
                </label>
                @endforeach
            </div>
        </div>
    </div>
    @endunless
</div>
