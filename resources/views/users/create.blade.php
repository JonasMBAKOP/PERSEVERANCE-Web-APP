@extends('layouts.app')

@section('title', 'Créer un compte')
@section('page-title', 'Créer un compte utilisateur')
@section('page-subtitle', 'Ajouter un nouveau membre à la plateforme')

@section('breadcrumb')
    <a href="{{ route('users.index') }}" class="hover:text-gray-700">
        Utilisateurs
    </a>
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round"
              stroke-width="2" d="M9 5l7 7-7 7"/>
    </svg>
    <span class="text-gray-700">Nouveau compte</span>
@endsection

@section('content')

<div class="max-w-2xl mx-auto">
    <form method="POST" action="{{ route('users.store') }}">
        @csrf

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">

            {{-- ── INFORMATIONS PERSONNELLES ──────────────────────────── --}}
            <h2 class="text-sm font-semibold uppercase tracking-wider mb-4
                       pb-2 border-b border-gray-100"
                style="color: #1A3A6B;">
                Informations personnelles
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">

                {{-- Nom complet --}}
                @php
                    $nameBorderClass = $errors->has('name') ? 'border-red-400' : 'border-gray-200';
                @endphp
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Nom complet <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           name="name"
                           value="{{ old('name') }}"
                           placeholder="Ex: KAMGA Jean-Paul"
                           class="w-full px-3 py-2.5 border rounded-lg text-sm
                                  focus:outline-none focus:border-blue-400 {{ $nameBorderClass }}" {{-- tailwindcss:ignore --}}>
                    @error('name')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Email --}}
                @php
                    $emailBorderClass = $errors->has('email') ? 'border-red-400' : 'border-gray-200';
                @endphp
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Adresse e-mail <span class="text-red-500">*</span>
                    </label>
                    <input type="email"
                           name="email"
                           value="{{ old('email') }}"
                           placeholder="nom@coptan.cm"
                           class="w-full px-3 py-2.5 border rounded-lg text-sm
                                  focus:outline-none focus:border-blue-400 {{ $emailBorderClass }}" {{-- tailwindcss:ignore --}}>
                    @error('email')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Téléphone --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Téléphone
                    </label>
                    <input type="text"
                           name="phone"
                           value="{{ old('phone') }}"
                           placeholder="Ex: +237 6XX XXX XXX"
                           class="w-full px-3 py-2.5 border border-gray-200
                                  rounded-lg text-sm focus:outline-none
                                  focus:border-blue-400">
                </div>

            </div>

            {{-- ── MOT DE PASSE ────────────────────────────────────────── --}}
            <h2 class="text-sm font-semibold uppercase tracking-wider mb-4
                       pb-2 border-b border-gray-100"
                style="color: #1A3A6B;">
                Mot de passe
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">

                @php
                    $passwordBorderClass = $errors->has('password') ? 'border-red-400' : 'border-gray-200';
                @endphp
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Mot de passe <span class="text-red-500">*</span>
                    </label>
                    <input type="password"
                           name="password"
                           placeholder="••••••••"
                           class="w-full px-3 py-2.5 border rounded-lg text-sm
                                  focus:outline-none focus:border-blue-400 {{ $passwordBorderClass }}" {{-- tailwindcss:ignore --}}>
                    @error('password')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Confirmer le mot de passe <span class="text-red-500">*</span>
                    </label>
                    <input type="password"
                           name="password_confirmation"
                           placeholder="••••••••"
                           class="w-full px-3 py-2.5 border border-gray-200
                                  rounded-lg text-sm focus:outline-none
                                  focus:border-blue-400">
                </div>

            </div>

            {{-- ── RÔLES ───────────────────────────────────────────────── --}}
            <h2 class="text-sm font-semibold uppercase tracking-wider mb-4
                       pb-2 border-b border-gray-100"
                style="color: #1A3A6B;">
                Rôle(s) <span class="text-red-500">*</span>
            </h2>

            @error('roles')
                <p class="mb-3 text-xs text-red-500">{{ $message }}</p>
            @enderror

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-6">
                @foreach($roles as $role)
                @php
                    $descriptions = [
                        'super-admin'            => 'Accès total à la plateforme',
                        'directeur'              => 'Gestion globale de l\'établissement',
                        'censeur'                => 'Gestion académique et discipline',
                        'econome'                => 'Inscriptions et finances',
                        'enseignant'             => 'Notes et classes assignées',
                        'surveillant-general'    => 'Présences et discipline',
                        'surveillant-de-secteur' => 'Suivi de secteur, présences et discipline',
                        'secretaire'             => 'Secrétariat, édition de documents et courriers',
                    ];
                    $desc = $descriptions[$role->name] ?? '';
                @endphp
                <label class="flex items-start gap-3 p-3 rounded-lg border
                              cursor-pointer transition-colors hover:bg-gray-50
                              {{ in_array($role->name, old('roles', []))
                                  ? 'border-blue-400 bg-blue-50'
                                  : 'border-gray-200' }}">
                    <input type="checkbox"
                           name="roles[]"
                           value="{{ $role->name }}"
                           {{ in_array($role->name, old('roles', [])) ? 'checked' : '' }}
                           class="mt-0.5 rounded"
                           style="accent-color: #1A3A6B;">
                    <div>
                        <p class="text-sm font-medium text-gray-800">
                            {{ $role->name === 'assistant-direction' ? 'Assistant(e) de Direction' : ucfirst(str_replace('-', ' ', $role->name)) }}
                        </p>
                        <p class="text-xs text-gray-500">{{ $desc }}</p>
                    </div>
                </label>
                @endforeach
            </div>

            {{-- ── ACTIONS ─────────────────────────────────────────────── --}}
            <div class="flex flex-col sm:flex-row gap-3 pt-4 border-t border-gray-100">
                <a href="{{ route('users.index') }}"
                   class="flex-1 sm:flex-none px-6 py-2.5 border border-gray-200
                          rounded-lg text-sm font-medium text-gray-600
                          hover:bg-gray-50 text-center">
                    Annuler
                </a>
                <button type="submit"
                        class="flex-1 sm:flex-none px-6 py-2.5 rounded-lg
                               text-white text-sm font-semibold
                               flex items-center justify-center gap-2"
                        style="background-color: #1A5C2A;">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor"
                         viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Créer le compte
                </button>
            </div>

        </div>
    </form>
</div>

@endsection
