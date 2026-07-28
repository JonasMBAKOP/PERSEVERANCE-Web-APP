@extends('layouts.app')

@section('title', 'Paramètres')
@section('page-title', 'Paramètres de l\'établissement')
@section('page-subtitle', 'Configuration de LA PERSEVERANCE PLUS')

@section('content')

{{-- ── ONGLETS (Alpine.js) ──────────────────────────────────────────────── --}}
<div x-data="{ tab: '{{ session('active_tab', 'general') }}' }">

    {{-- Navigation des onglets --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-6 overflow-hidden">
        <div class="flex overflow-x-auto">
            <button @click="tab = 'general'"
                    :class="tab === 'general'
                        ? 'border-b-2 text-blue-700 font-semibold'
                        : 'text-gray-500 hover:text-gray-700'"
                    class="flex items-center gap-2 px-5 py-4 text-sm
                           whitespace-nowrap transition-colors border-b-2
                           border-transparent"
                    style="color: tab === 'general' ? '#1A3A6B' : ''">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9
                             0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1
                             1 0 011 1v5m-4 0h4"/>
                </svg>
                Informations générales
            </button>

            <button @click="tab = 'appearance'"
                    :class="tab === 'appearance'
                        ? 'border-b-2 text-blue-700 font-semibold'
                        : 'text-gray-500 hover:text-gray-700'"
                    class="flex items-center gap-2 px-5 py-4 text-sm
                           whitespace-nowrap transition-colors border-b-2
                           border-transparent">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2
                             2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0
                             00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                Logo & Apparence
            </button>

            <button @click="tab = 'phones'"
                    :class="tab === 'phones'
                        ? 'border-b-2 text-blue-700 font-semibold'
                        : 'text-gray-500 hover:text-gray-700'"
                    class="flex items-center gap-2 px-5 py-4 text-sm
                           whitespace-nowrap transition-colors border-b-2
                           border-transparent">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0
                             01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13
                             -2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2
                             2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                </svg>
                Téléphones
                @if($phones->count())
                    <span class="ml-1 px-1.5 py-0.5 rounded-full text-xs
                                 bg-blue-100 text-blue-700">
                        {{ $phones->count() }}
                    </span>
                @endif
            </button>

            <button @click="tab = 'agreements'"
                    :class="tab === 'agreements'
                        ? 'border-b-2 text-blue-700 font-semibold'
                        : 'text-gray-500 hover:text-gray-700'"
                    class="flex items-center gap-2 px-5 py-4 text-sm
                           whitespace-nowrap transition-colors border-b-2
                           border-transparent">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1
                             1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0
                             01-2 2z"/>
                </svg>
                Agréments
                @if($agreements->count())
                    <span class="ml-1 px-1.5 py-0.5 rounded-full text-xs
                                 bg-blue-100 text-blue-700">
                        {{ $agreements->count() }}
                    </span>
                @endif
            </button>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════ --}}
    {{-- ONGLET 1 — Informations générales                                  --}}
    {{-- ══════════════════════════════════════════════════════════════════ --}}
    <div x-show="tab === 'general'" x-transition>
        <form method="POST" action="{{ route('settings.update') }}">
            @csrf @method('PUT')

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                {{-- Colonne principale --}}
                <div class="lg:col-span-2 space-y-6">

                    {{-- Identité de l'établissement --}}
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <h3 class="text-sm font-semibold uppercase tracking-wider
                                   text-gray-400 mb-4 pb-2 border-b border-gray-100">
                            Identité de l'établissement
                        </h3>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="sm:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Nom complet <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="full_name"
                                       value="{{ old('full_name', $settings->full_name) }}"
                                       placeholder="Ex: Collège Polyvalent NTANKEU"
                                       class="w-full px-3 py-2.5 border rounded-lg text-sm
                                              focus:outline-none focus:ring-2
                                              @error('full_name') border-red-400 @else border-gray-200 @enderror"
                                       style="--tw-ring-color: #1A3A6B20">
                                @error('full_name')
                                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Sigle / Acronyme <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="short_name"
                                       value="{{ old('short_name', $settings->short_name) }}"
                                       placeholder="Ex: COPTAN"
                                       class="w-full px-3 py-2.5 border rounded-lg text-sm
                                              focus:outline-none
                                              @error('short_name') border-red-400 @else border-gray-200 @enderror">
                                @error('short_name')
                                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Type d'ordre
                                </label>
                                <select name="order_type"
                                        class="w-full px-3 py-2.5 border border-gray-200
                                               rounded-lg text-sm focus:outline-none bg-white">
                                    <option value="">Sélectionner...</option>
                                    @foreach(['Privé Laïc', 'Privé Catholique', 'Privé Protestant', 'Privé Islamique', 'Public'] as $type)
                                    <option value="{{ $type }}"
                                            {{ old('order_type', $settings->order_type) === $type ? 'selected' : '' }}>
                                        {{ $type }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="sm:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Devise / Slogan
                                </label>
                                <input type="text" name="motto"
                                       value="{{ old('motto', $settings->motto) }}"
                                       placeholder="Ex: Excellence, Discipline, Travail"
                                       class="w-full px-3 py-2.5 border border-gray-200
                                              rounded-lg text-sm focus:outline-none">
                            </div>

                            <div class="sm:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Ministère de tutelle
                                </label>
                                <input type="text" name="ministry"
                                       value="{{ old('ministry', $settings->ministry) }}"
                                       placeholder="Ministère des Enseignements Secondaires"
                                       class="w-full px-3 py-2.5 border border-gray-200
                                              rounded-lg text-sm focus:outline-none">
                            </div>
                        </div>
                    </div>

                    {{-- Coordonnées --}}
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <h3 class="text-sm font-semibold uppercase tracking-wider
                                   text-gray-400 mb-4 pb-2 border-b border-gray-100">
                            Coordonnées & Localisation
                        </h3>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="sm:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Adresse
                                </label>
                                <textarea name="address" rows="2"
                                          placeholder="Ex: Quartier Akwa, Avenue Kennedy..."
                                          class="w-full px-3 py-2.5 border border-gray-200
                                                 rounded-lg text-sm focus:outline-none
                                                 resize-none">{{ old('address', $settings->address) }}</textarea>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Ville
                                </label>
                                <input type="text" name="city"
                                       value="{{ old('city', $settings->city) }}"
                                       placeholder="Ex: Douala"
                                       class="w-full px-3 py-2.5 border border-gray-200
                                              rounded-lg text-sm focus:outline-none">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Région
                                </label>
                                <select name="region"
                                        class="w-full px-3 py-2.5 border border-gray-200
                                               rounded-lg text-sm focus:outline-none bg-white">
                                    <option value="">Sélectionner...</option>
                                    @foreach([
                                        'Adamaoua','Centre','Est','Extrême-Nord',
                                        'Littoral','Nord','Nord-Ouest','Ouest',
                                        'Sud','Sud-Ouest'
                                    ] as $region)
                                    <option value="{{ $region }}"
                                            {{ old('region', $settings->region) === $region ? 'selected' : '' }}>
                                        {{ $region }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Boîte postale
                                </label>
                                <input type="text" name="postal_box"
                                       value="{{ old('postal_box', $settings->postal_box) }}"
                                       placeholder="Ex: BP 1234"
                                       class="w-full px-3 py-2.5 border border-gray-200
                                              rounded-lg text-sm focus:outline-none">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    E-mail officiel
                                </label>
                                <input type="email" name="email"
                                       value="{{ old('email', $settings->email) }}"
                                       placeholder="Ex: contact@perseverance.cm"
                                       class="w-full px-3 py-2.5 border rounded-lg text-sm
                                              focus:outline-none
                                              @error('email') border-red-400 @else border-gray-200 @enderror">
                                @error('email')
                                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="sm:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Site web
                                </label>
                                <input type="url" name="website"
                                       value="{{ old('website', $settings->website) }}"
                                       placeholder="Ex: https://www.perseverance.cm"
                                       class="w-full px-3 py-2.5 border rounded-lg text-sm
                                              focus:outline-none
                                              @error('website') border-red-400 @else border-gray-200 @enderror">
                                @error('website')
                                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Colonne résumé --}}
                <div class="space-y-4">
                    {{-- Aperçu --}}
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                        <h3 class="text-sm font-semibold uppercase tracking-wider
                                   text-gray-400 mb-4 pb-2 border-b border-gray-100">
                            Aperçu actuel
                        </h3>
                        <div class="flex flex-col items-center text-center gap-3">
                            @if($settings->logo)
                                <img src="{{ asset('storage/' . $settings->logo) }}"
                                     alt="Logo"
                                     class="h-20 w-20 object-contain">
                            @else
                                <div class="h-20 w-20 rounded-full flex items-center
                                            justify-center text-white font-bold text-xl"
                                     style="background-color: #1A3A6B;">
                                    {{ strtoupper(substr($settings->short_name, 0, 2)) }}
                                </div>
                            @endif
                            <div>
                                <p class="font-bold text-sm" style="color: #1A3A6B;">
                                    {{ $settings->full_name }}
                                </p>
                                <p class="text-xs text-gray-500">{{ $settings->short_name }}</p>
                                @if($settings->city)
                                    <p class="text-xs text-gray-400 mt-1">
                                        {{ $settings->city }}
                                        @if($settings->region), {{ $settings->region }}@endif
                                    </p>
                                @endif
                                @if($settings->motto)
                                    <p class="text-xs italic text-gray-400 mt-1">
                                        "{{ $settings->motto }}"
                                    </p>
                                @endif
                                @if($settings->postal_box)
                                    <p class="text-xs text-gray-400 mt-1">
                                        BP : {{ $settings->postal_box }}
                                    </p>
                                @endif
                                @if(!$phones->isEmpty())
                                    <p class="text-xs text-gray-400 mt-1">
                                        Téléphone :
                                        @foreach($phones as $phone)
                                            {{ $phone->number }}{{ !$loop->last ? ' /' : '' }}
                                        @endforeach
                                    </p>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Bouton enregistrer --}}
                    <button type="submit"
                            class="w-full py-3 rounded-xl text-white font-semibold
                                   text-sm flex items-center justify-center gap-2
                                   transition-colors"
                            style="background-color: #1A5C2A;">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                             viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Enregistrer les modifications
                    </button>
                </div>

            </div>
        </form>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════ --}}
    {{-- ONGLET 2 — Logo & Apparence                                        --}}
    {{-- ══════════════════════════════════════════════════════════════════ --}}
    <div x-show="tab === 'appearance'" x-transition>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 items-start">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-sm font-semibold uppercase tracking-wider
                           text-gray-400 mb-6 pb-2 border-b border-gray-100">
                    Logo de l'établissement
                </h3>

                {{-- Logo actuel --}}
                <div class="flex items-center gap-6 mb-6 p-4 bg-gray-50 rounded-xl">
                    @if($settings->logo)
                        <img src="{{ asset('storage/' . $settings->logo) }}"
                             alt="Logo PERSEVERANCE"
                             class="h-24 w-24 object-contain rounded-lg
                                    border border-gray-200 bg-white p-1">
                        <div>
                            <p class="text-sm font-medium text-gray-800 mb-1">
                                Logo actuel
                            </p>
                            <p class="text-xs text-gray-500 mb-3">
                                {{-- {{ basename($settings->logo) }} --}}
                            </p>
                            <form method="POST"
                                  action="{{ route('settings.logo.delete') }}"
                                  onsubmit="return confirm('Supprimer le logo ?')">
                                @csrf @method('DELETE')
                                <button type="submit"
                                        class="flex items-center gap-1 text-xs
                                               text-red-600 hover:text-red-700">
                                    <svg class="w-3.5 h-3.5" fill="none"
                                         stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                              stroke-width="2"
                                              d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2
                                                 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1
                                                 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                    Supprimer le logo
                                </button>
                            </form>
                        </div>
                    @else
                        <div class="h-24 w-24 rounded-lg border-2 border-dashed
                                    border-gray-300 flex items-center justify-center
                                    bg-white">
                            <svg class="w-8 h-8 text-gray-300" fill="none"
                                 stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      stroke-width="1.5"
                                      d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586
                                         -1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2
                                         0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0
                                         002 2z"/>
                            </svg>
                        </div>
                        <p class="text-sm text-gray-500">
                            Aucun logo configuré
                        </p>
                    @endif
                </div>

                {{-- Upload nouveau logo --}}
                <form method="POST"
                      action="{{ route('settings.logo.update') }}"
                      enctype="multipart/form-data"
                      x-data="imageUpload()">
                    @csrf

                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        {{ $settings->logo ? 'Remplacer le logo' : 'Ajouter un logo' }}
                    </label>

                    {{-- Zone de drop --}}
                    <div class="border-2 border-dashed border-gray-300 rounded-xl p-8
                                text-center cursor-pointer hover:border-blue-400
                                hover:bg-blue-50 transition-colors"
                         @click="$refs.fileInput.click()"
                         @dragover.prevent
                         @drop.prevent="handleDrop($event)">

                        <template x-if="!preview">
                            <div>
                                <svg class="w-10 h-10 text-gray-300 mx-auto mb-3"
                                     fill="none" stroke="currentColor"
                                     viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          stroke-width="1.5"
                                          d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5
                                             5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                </svg>
                                <p class="text-sm text-gray-500">
                                    <span class="font-medium" style="color: #1A3A6B;">
                                        Cliquer pour choisir
                                    </span>
                                    ou glisser-déposer
                                </p>
                                <p class="text-xs text-gray-400 mt-1">
                                    JPG, PNG, SVG — Max 2 Mo
                                </p>
                            </div>
                        </template>

                        <template x-if="preview">
                            <div class="flex flex-col items-center gap-2">
                                <img :src="preview"
                                     class="h-24 w-24 object-contain rounded-lg
                                            border border-gray-200">
                                <p class="text-xs text-gray-500" x-text="fileName"></p>
                                <p class="text-xs text-blue-600">
                                    Cliquer pour changer
                                </p>
                            </div>
                        </template>

                        <input type="file" name="logo" x-ref="fileInput"
                               class="hidden" accept="image/*"
                               @change="handleFile($event)">
                    </div>

                    @error('logo')
                        <p class="mt-2 text-xs text-red-500">{{ $message }}</p>
                    @enderror

                    <button type="submit" x-show="preview"
                            class="mt-4 w-full py-2.5 rounded-lg text-white
                                   text-sm font-semibold transition-colors"
                            style="background-color: #1A5C2A;">
                        Enregistrer le logo
                    </button>
                </form>
            </div>

            {{-- Cachet du proviseur --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-sm font-semibold uppercase tracking-wider
                           text-gray-400 mb-2 pb-2 border-b border-gray-100">
                    Cachet du proviseur
                </h3>
                <p class="text-xs text-gray-500 mb-6">
                    Image du cachet officiel affichée sur les cartes scolaires et certains documents imprimables.
                </p>

                <div class="flex items-center gap-6 mb-6 p-4 bg-gray-50 rounded-xl">
                    @if($settings->signature_seal)
                        <img src="{{ asset('storage/' . $settings->signature_seal) }}"
                             alt="Cachet du proviseur"
                             class="h-24 w-24 object-contain rounded-lg
                                    border border-gray-200 bg-white p-1">
                        <div>
                            <p class="text-sm font-medium text-gray-800 mb-1">
                                Cachet actuel
                            </p>
                            <form method="POST"
                                  action="{{ route('settings.signature-seal.delete') }}"
                                  onsubmit="return confirm('Supprimer le cachet ?')">
                                @csrf @method('DELETE')
                                <button type="submit"
                                        class="flex items-center gap-1 text-xs
                                               text-red-600 hover:text-red-700">
                                    <svg class="w-3.5 h-3.5" fill="none"
                                         stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                              stroke-width="2"
                                              d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2
                                                 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1
                                                 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                    Supprimer le cachet
                                </button>
                            </form>
                        </div>
                    @else
                        <div class="h-24 w-24 rounded-lg border-2 border-dashed
                                    border-gray-300 flex items-center justify-center bg-white">
                            <svg class="w-8 h-8 text-gray-300" fill="none"
                                 stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      stroke-width="1.5"
                                      d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955
                                         11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824
                                         10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133
                                         -2.052-.382-3.016z"/>
                            </svg>
                        </div>
                        <p class="text-sm text-gray-500">
                            Aucun cachet configuré
                        </p>
                    @endif
                </div>

                <form method="POST"
                      action="{{ route('settings.signature-seal.update') }}"
                      enctype="multipart/form-data"
                      x-data="imageUpload()">
                    @csrf

                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        {{ $settings->signature_seal ? 'Remplacer le cachet' : 'Ajouter un cachet' }}
                    </label>

                    <div class="border-2 border-dashed border-gray-300 rounded-xl p-8
                                text-center cursor-pointer hover:border-blue-400
                                hover:bg-blue-50 transition-colors"
                         @click="$refs.sealFileInput.click()"
                         @dragover.prevent
                         @drop.prevent="handleDrop($event)">

                        <template x-if="!preview">
                            <div>
                                <svg class="w-10 h-10 text-gray-300 mx-auto mb-3"
                                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          stroke-width="1.5"
                                          d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                </svg>
                                <p class="text-sm text-gray-500">
                                    <span class="font-medium" style="color:#1A3A6B;">
                                        Cliquer pour choisir
                                    </span>
                                    ou glisser-déposer
                                </p>
                                <p class="text-xs text-gray-400 mt-1">
                                    JPG, PNG, WEBP — Max 2 Mo — fond transparent recommandé
                                </p>
                            </div>
                        </template>

                        <template x-if="preview">
                            <div class="flex flex-col items-center gap-2">
                                <img :src="preview"
                                     class="h-24 w-24 object-contain rounded-lg border border-gray-200">
                                <p class="text-xs text-gray-500" x-text="fileName"></p>
                                <p class="text-xs text-blue-600">Cliquer pour changer</p>
                            </div>
                        </template>

                        <input type="file" name="signature_seal" x-ref="sealFileInput"
                               class="hidden" accept="image/*"
                               @change="handleFile($event)">
                    </div>

                    @error('signature_seal')
                        <p class="mt-2 text-xs text-red-500">{{ $message }}</p>
                    @enderror

                    <button type="submit" x-show="preview"
                            class="mt-4 w-full py-2.5 rounded-lg text-white
                                   text-sm font-semibold transition-colors"
                            style="background-color:#1A5C2A;">
                        Enregistrer le cachet
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════ --}}
    {{-- ONGLET 3 — Téléphones                                              --}}
    {{-- ══════════════════════════════════════════════════════════════════ --}}
    <div x-show="tab === 'phones'" x-transition>
        <div class="max-w-2xl space-y-4">

            {{-- Formulaire d'ajout --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6"
                 x-data="{ open: false }">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold uppercase tracking-wider
                               text-gray-400">
                        Numéros de téléphone
                    </h3>
                    <button @click="open = !open"
                            class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg
                                   text-white text-sm font-medium"
                            style="background-color: #1A5C2A;">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                             viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Ajouter
                    </button>
                </div>

                {{-- Formulaire ajout --}}
                <div x-show="open" x-transition class="mt-4 pt-4 border-t border-gray-100">
                    <form method="POST" action="{{ route('settings.phones.store') }}">
                        @csrf
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">
                                    Numéro <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="number"
                                       placeholder="+237 6XX XXX XXX"
                                       class="w-full px-3 py-2 border border-gray-200
                                              rounded-lg text-sm focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">
                                    Libellé
                                </label>
                                <input type="text" name="label"
                                       placeholder="Ex: Secrétariat, WhatsApp"
                                       class="w-full px-3 py-2 border border-gray-200
                                              rounded-lg text-sm focus:outline-none">
                            </div>
                        </div>
                        <div class="flex items-center justify-between">
                            <label class="flex items-center gap-2 text-sm text-gray-600">
                                <input type="checkbox" name="is_primary" value="1"
                                       style="accent-color: #1A3A6B;">
                                Numéro principal
                            </label>
                            <div class="flex gap-2">
                                <button type="button" @click="open = false"
                                        class="px-3 py-1.5 border border-gray-200
                                               rounded-lg text-sm text-gray-600
                                               hover:bg-gray-50">
                                    Annuler
                                </button>
                                <button type="submit"
                                        class="px-3 py-1.5 rounded-lg text-white
                                               text-sm font-medium"
                                        style="background-color: #1A3A6B;">
                                    Enregistrer
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Liste des numéros --}}
            @if($phones->isEmpty())
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8
                        text-center text-gray-400">
                <svg class="w-10 h-10 mx-auto mb-2 opacity-30" fill="none"
                     stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0
                             01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13
                             -2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2
                             2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                </svg>
                <p class="text-sm">Aucun numéro enregistré</p>
            </div>
            @else
            <div class="bg-white rounded-xl shadow-sm border border-gray-100
                        overflow-hidden">
                <ul class="divide-y divide-gray-100">
                    @foreach($phones as $phone)
                    <li class="p-4" x-data="{ editing: false }">
                        <div x-show="!editing"
                             class="flex items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-full flex items-center
                                            justify-center flex-shrink-0"
                                     style="background-color: #EBF3FB;">
                                    <svg class="w-4 h-4" style="color: #1A3A6B;"
                                         fill="none" stroke="currentColor"
                                         viewBox="0 0 24 24">
                                        <path stroke-linecap="round"
                                              stroke-linejoin="round" stroke-width="2"
                                              d="M3 5a2 2 0 012-2h3.28a1 1 0
                                                 01.948.684l1.498 4.493a1 1 0
                                                 01-.502 1.21l-2.257 1.13a11.042
                                                 11.042 0 005.516 5.516l1.13-2.257a1
                                                 1 0 011.21-.502l4.493 1.498a1 1 0
                                                 01.684.949V19a2 2 0 01-2 2h-1C9.716
                                                 21 3 14.284 3 6V5z"/>
                                    </svg>
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <p class="text-sm font-medium text-gray-800">
                                            {{ $phone->number }}
                                        </p>
                                        @if($phone->is_primary)
                                            <span class="px-1.5 py-0.5 rounded text-xs
                                                         font-medium"
                                                  style="background-color: #D4EDDA;
                                                         color: #1A5C2A;">
                                                Principal
                                            </span>
                                        @endif
                                    </div>
                                    @if($phone->label)
                                        <p class="text-xs text-gray-400">
                                            {{ $phone->label }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                            <div class="flex items-center gap-1">
                                {{-- Définir comme principal --}}
                                @if(!$phone->is_primary)
                                <form method="POST"
                                      action="{{ route('settings.phones.primary', $phone) }}">
                                    @csrf @method('PATCH')
                                    <button type="submit"
                                            title="Définir comme principal"
                                            class="p-1.5 rounded-lg text-gray-400
                                                   hover:text-green-600
                                                   hover:bg-green-50 transition-colors">
                                        <svg class="w-4 h-4" fill="none"
                                             stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round"
                                                  stroke-linejoin="round" stroke-width="2"
                                                  d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </button>
                                </form>
                                @endif
                                {{-- Modifier --}}
                                <button @click="editing = true"
                                        class="p-1.5 rounded-lg text-gray-400
                                               hover:text-blue-600 hover:bg-blue-50
                                               transition-colors">
                                    <svg class="w-4 h-4" fill="none"
                                         stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round"
                                              stroke-linejoin="round" stroke-width="2"
                                              d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5
                                                 2.5 0 113.536 3.536L6.5 21.036H3v-3.572
                                                 L16.732 3.732z"/>
                                    </svg>
                                </button>
                                {{-- Supprimer --}}
                                <form method="POST"
                                      action="{{ route('settings.phones.destroy', $phone) }}"
                                      onsubmit="return confirm('Supprimer ce numéro ?')">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                            class="p-1.5 rounded-lg text-gray-400
                                                   hover:text-red-600 hover:bg-red-50
                                                   transition-colors">
                                        <svg class="w-4 h-4" fill="none"
                                             stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round"
                                                  stroke-linejoin="round" stroke-width="2"
                                                  d="M19 7l-.867 12.142A2 2 0 0116.138
                                                     21H7.862a2 2 0 01-1.995-1.858L5 7m5
                                                     4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1
                                                     1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </div>

                        {{-- Formulaire d'édition inline --}}
                        <div x-show="editing" x-transition class="mt-2">
                            <form method="POST"
                                  action="{{ route('settings.phones.update', $phone) }}">
                                @csrf @method('PUT')
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-3">
                                    <input type="text" name="number"
                                           value="{{ $phone->number }}"
                                           class="px-3 py-2 border border-gray-200
                                                  rounded-lg text-sm focus:outline-none">
                                    <input type="text" name="label"
                                           value="{{ $phone->label }}"
                                           placeholder="Libellé (optionnel)"
                                           class="px-3 py-2 border border-gray-200
                                                  rounded-lg text-sm focus:outline-none">
                                </div>
                                <div class="flex items-center justify-between">
                                    <label class="flex items-center gap-2 text-sm
                                                  text-gray-600">
                                        <input type="checkbox" name="is_primary" value="1"
                                               {{ $phone->is_primary ? 'checked' : '' }}
                                               style="accent-color: #1A3A6B;">
                                        Principal
                                    </label>
                                    <div class="flex gap-2">
                                        <button type="button" @click="editing = false"
                                                class="px-3 py-1.5 border border-gray-200
                                                       rounded-lg text-sm text-gray-600
                                                       hover:bg-gray-50">
                                            Annuler
                                        </button>
                                        <button type="submit"
                                                class="px-3 py-1.5 rounded-lg text-white
                                                       text-sm"
                                                style="background-color: #1A3A6B;">
                                            Enregistrer
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </li>
                    @endforeach
                </ul>
            </div>
            @endif

        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════ --}}
    {{-- ONGLET 4 — Agréments                                               --}}
    {{-- ══════════════════════════════════════════════════════════════════ --}}
    <div x-show="tab === 'agreements'" x-transition>
        <div class="max-w-2xl space-y-4">

            {{-- Formulaire d'ajout --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6"
                 x-data="{ open: false }">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold uppercase tracking-wider
                               text-gray-400">
                        Numéros d'agrément
                    </h3>
                    <button @click="open = !open"
                            class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg
                                   text-white text-sm font-medium"
                            style="background-color: #1A5C2A;">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                             viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Ajouter
                    </button>
                </div>

                <div x-show="open" x-transition
                     class="mt-4 pt-4 border-t border-gray-100">
                    <form method="POST"
                          action="{{ route('settings.agreements.store') }}">
                        @csrf
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">
                                    Numéro <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="number"
                                       placeholder="Ex: 042/B2/MINEDUB/SG"
                                       class="w-full px-3 py-2 border border-gray-200
                                              rounded-lg text-sm focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">
                                    Cycle <span class="text-red-500">*</span>
                                </label>
                                <select name="cycle"
                                        class="w-full px-3 py-2 border border-gray-200
                                               rounded-lg text-sm focus:outline-none
                                               bg-white">
                                    <option value="">Sélectionner...</option>
                                    <option value="premier_cycle">
                                        Premier Cycle (6ème – 3ème)
                                    </option>
                                    <option value="second_cycle">
                                        Second Cycle (2nde – Terminale)
                                    </option>
                                    <option value="autre">Autre</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">
                                    Description
                                </label>
                                <input type="text" name="label"
                                       placeholder="Description optionnelle"
                                       class="w-full px-3 py-2 border border-gray-200
                                              rounded-lg text-sm focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">
                                    Date de délivrance
                                </label>
                                <input type="date" name="issued_date"
                                       class="w-full px-3 py-2 border border-gray-200
                                              rounded-lg text-sm focus:outline-none">
                            </div>
                        </div>
                        <div class="flex justify-end gap-2">
                            <button type="button" @click="open = false"
                                    class="px-3 py-1.5 border border-gray-200
                                           rounded-lg text-sm text-gray-600
                                           hover:bg-gray-50">
                                Annuler
                            </button>
                            <button type="submit"
                                    class="px-3 py-1.5 rounded-lg text-white text-sm"
                                    style="background-color: #1A3A6B;">
                                Enregistrer
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Liste des agréments --}}
            @if($agreements->isEmpty())
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8
                        text-center text-gray-400">
                <svg class="w-10 h-10 mx-auto mb-2 opacity-30" fill="none"
                     stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          stroke-width="1.5"
                          d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1
                             1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0
                             01-2 2z"/>
                </svg>
                <p class="text-sm">Aucun agrément enregistré</p>
            </div>
            @else
            <div class="bg-white rounded-xl shadow-sm border border-gray-100
                        overflow-hidden">
                <ul class="divide-y divide-gray-100">
                    @foreach($agreements as $agreement)
                    <li class="p-4" x-data="{ editing: false }">
                        <div x-show="!editing"
                             class="flex items-start justify-between gap-3">
                            <div class="flex items-start gap-3">
                                <div class="w-9 h-9 rounded-full flex items-center
                                            justify-center flex-shrink-0 mt-0.5"
                                     style="background-color: #FBF5E6;">
                                    <svg class="w-4 h-4" style="color: #C8A415;"
                                         fill="none" stroke="currentColor"
                                         viewBox="0 0 24 24">
                                        <path stroke-linecap="round"
                                              stroke-linejoin="round" stroke-width="2"
                                              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2
                                                 2 0 012-2h5.586a1 1 0 01.707.293l5.414
                                                 5.414a1 1 0 01.293.707V19a2 2 0
                                                 01-2 2z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-800">
                                        {{ $agreement->number }}
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        {{ $agreement->cycle_label }}
                                    </p>
                                    @if($agreement->label)
                                        <p class="text-xs text-gray-400">
                                            {{ $agreement->label }}
                                        </p>
                                    @endif
                                    @if($agreement->issued_date)
                                        <p class="text-xs text-gray-400">
                                            Délivré le :
                                            {{ $agreement->issued_date->format('d/m/Y') }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                            <div class="flex items-center gap-1 flex-shrink-0">
                                <button @click="editing = true"
                                        class="p-1.5 rounded-lg text-gray-400
                                               hover:text-blue-600 hover:bg-blue-50">
                                    <svg class="w-4 h-4" fill="none"
                                         stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round"
                                              stroke-linejoin="round" stroke-width="2"
                                              d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5
                                                 2.5 0 113.536 3.536L6.5 21.036H3v-3.572
                                                 L16.732 3.732z"/>
                                    </svg>
                                </button>
                                <form method="POST"
                                      action="{{ route('settings.agreements.destroy',
                                                        $agreement) }}"
                                      onsubmit="return confirm('Supprimer cet agrément ?')">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                            class="p-1.5 rounded-lg text-gray-400
                                                   hover:text-red-600 hover:bg-red-50">
                                        <svg class="w-4 h-4" fill="none"
                                             stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round"
                                                  stroke-linejoin="round" stroke-width="2"
                                                  d="M19 7l-.867 12.142A2 2 0 0116.138
                                                     21H7.862a2 2 0 01-1.995-1.858L5 7m5
                                                     4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1
                                                     1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </div>

                        {{-- Formulaire édition inline --}}
                        <div x-show="editing" x-transition class="mt-2">
                            <form method="POST"
                                  action="{{ route('settings.agreements.update',
                                                    $agreement) }}">
                                @csrf @method('PUT')
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-3">
                                    <input type="text" name="number"
                                           value="{{ $agreement->number }}"
                                           class="px-3 py-2 border border-gray-200
                                                  rounded-lg text-sm focus:outline-none">
                                    <select name="cycle"
                                            class="px-3 py-2 border border-gray-200
                                                   rounded-lg text-sm focus:outline-none
                                                   bg-white">
                                        <option value="premier_cycle"
                                                {{ $agreement->cycle === 'premier_cycle' ? 'selected' : '' }}>
                                            Premier Cycle
                                        </option>
                                        <option value="second_cycle"
                                                {{ $agreement->cycle === 'second_cycle' ? 'selected' : '' }}>
                                            Second Cycle
                                        </option>
                                        <option value="autre"
                                                {{ $agreement->cycle === 'autre' ? 'selected' : '' }}>
                                            Autre
                                        </option>
                                    </select>
                                    <input type="text" name="label"
                                           value="{{ $agreement->label }}"
                                           placeholder="Description"
                                           class="px-3 py-2 border border-gray-200
                                                  rounded-lg text-sm focus:outline-none">
                                    <input type="date" name="issued_date"
                                           value="{{ $agreement->issued_date?->format('Y-m-d') }}"
                                           class="px-3 py-2 border border-gray-200
                                                  rounded-lg text-sm focus:outline-none">
                                </div>
                                <div class="flex justify-end gap-2">
                                    <button type="button" @click="editing = false"
                                            class="px-3 py-1.5 border border-gray-200
                                                   rounded-lg text-sm text-gray-600">
                                        Annuler
                                    </button>
                                    <button type="submit"
                                            class="px-3 py-1.5 rounded-lg text-white
                                                   text-sm"
                                            style="background-color: #1A3A6B;">
                                        Enregistrer
                                    </button>
                                </div>
                            </form>
                        </div>
                    </li>
                    @endforeach
                </ul>
            </div>
            @endif

        </div>
    </div>

</div>

{{-- Script Alpine.js pour les uploads d'images --}}
<script>
function imageUpload() {
    return {
        preview: null,
        fileName: '',
        handleFile(event) {
            const file = event.target.files[0];
            if (!file) return;
            this.fileName = file.name;
            const reader = new FileReader();
            reader.onload = (e) => { this.preview = e.target.result; };
            reader.readAsDataURL(file);
        },
        handleDrop(event) {
            const file = event.dataTransfer.files[0];
            if (!file) return;
            this.fileName = file.name;
            const input = event.currentTarget.querySelector('input[type="file"]');
            if (input) input.files = event.dataTransfer.files;
            const reader = new FileReader();
            reader.onload = (e) => { this.preview = e.target.result; };
            reader.readAsDataURL(file);
        }
    }
}
</script>

@endsection