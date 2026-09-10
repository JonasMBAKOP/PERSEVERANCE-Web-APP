@extends('layouts.app')

@section('title', 'Modifier le compte')
@section('page-title', 'Modifier le compte')
@section('page-subtitle'){{ $user->name }}@endsection

@section('breadcrumb')
    <a href="{{ route('users.index') }}" class="hover:text-gray-700">
        Utilisateurs
    </a>
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round"
              stroke-width="2" d="M9 5l7 7-7 7"/>
    </svg>
    <span class="text-gray-700">{{ $user->name }}</span>
@endsection

@section('content')

<div class="max-w-2xl mx-auto space-y-4">

    {{-- ── FORMULAIRE PRINCIPAL ───────────────────────────────────────────── --}}
    <form method="POST" action="{{ route('users.update', $user) }}">
        @csrf @method('PUT')

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">

            {{-- Infos personnelles --}}
            <h2 class="text-sm font-semibold uppercase tracking-wider mb-4
                       pb-2 border-b border-gray-100"
                style="color: #1A3A6B;">
                Informations personnelles
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">

                @php
                    $nameBorderClass = $errors->has('name') ? 'border-red-400' : 'border-gray-200';
                @endphp
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Nom complet <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name"
                           value="{{ old('name', $user->name) }}"
                           class="w-full px-3 py-2.5 border rounded-lg text-sm
                                  focus:outline-none focus:border-blue-400 {{ $nameBorderClass }}">
                    @error('name')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                @php
                    $emailBorderClass = $errors->has('email') ? 'border-red-400' : 'border-gray-200';
                @endphp
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        E-mail <span class="text-red-500">*</span>
                    </label>
                    <input type="email" name="email"
                           value="{{ old('email', $user->email) }}"
                           class="w-full px-3 py-2.5 border rounded-lg text-sm
                                  focus:outline-none focus:border-blue-400 {{ $emailBorderClass }}">
                    @error('email')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Téléphone
                    </label>
                    <input type="text" name="phone"
                           value="{{ old('phone', $user->phone) }}"
                           placeholder="+237 6XX XXX XXX"
                           class="w-full px-3 py-2.5 border border-gray-200
                                  rounded-lg text-sm focus:outline-none
                                  focus:border-blue-400">
                </div>

            </div>

            {{-- Statut --}}
            <div class="flex items-center justify-between p-3 bg-gray-50
                        rounded-lg mb-6">
                <div>
                    <p class="text-sm font-medium text-gray-700">
                        Compte actif
                    </p>
                    <p class="text-xs text-gray-500">
                        Un compte inactif ne peut plus se connecter
                    </p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox"
                           name="is_active"
                           value="1"
                           {{ old('is_active', $user->is_active) ? 'checked' : '' }}
                           class="sr-only peer"
                           {{ $user->id === auth()->id() ? 'disabled' : '' }}>
                    <div class="w-11 h-6 bg-gray-200 rounded-full peer
                                peer-checked:bg-green-500
                                peer-focus:ring-2 peer-focus:ring-green-300
                                after:content-[''] after:absolute after:top-[2px]
                                after:left-[2px] after:bg-white after:rounded-full
                                after:h-5 after:w-5 after:transition-all
                                peer-checked:after:translate-x-full">
                    </div>
                </label>
            </div>

            {{-- Nouveau mot de passe (optionnel) --}}
            <h2 class="text-sm font-semibold uppercase tracking-wider mb-4
                       pb-2 border-b border-gray-100"
                style="color: #1A3A6B;">
                Changer le mot de passe
                <span class="text-gray-400 font-normal normal-case
                             tracking-normal ml-1">(optionnel)</span>
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
                @php
                    $passwordBorderClass = $errors->has('password') ? 'border-red-400' : 'border-gray-200';
                @endphp
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Nouveau mot de passe
                    </label>
                    <input type="password" name="password"
                           placeholder="Laisser vide = inchangé"
                           class="w-full px-3 py-2.5 border rounded-lg text-sm
                                  focus:outline-none focus:border-blue-400 {{ $passwordBorderClass }}">
                    @error('password')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Confirmer
                    </label>
                    <input type="password" name="password_confirmation"
                           placeholder="••••••••"
                           class="w-full px-3 py-2.5 border border-gray-200
                                  rounded-lg text-sm focus:outline-none
                                  focus:border-blue-400">
                </div>
            </div>

            {{-- Rôles --}}
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
                    $desc     = $descriptions[$role->name] ?? '';
                    $checked  = in_array($role->name, old('roles', $userRoles));
                @endphp
                <label class="flex items-start gap-3 p-3 rounded-lg border
                              cursor-pointer transition-colors hover:bg-gray-50
                              {{ $checked ? 'border-blue-400 bg-blue-50' : 'border-gray-200' }}">
                    <input type="checkbox"
                           name="roles[]"
                           value="{{ $role->name }}"
                           {{ $checked ? 'checked' : '' }}
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

            {{-- Actions --}}
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
                        style="background-color: #1A3A6B;">
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

    {{-- ── INFOS DU COMPTE ────────────────────────────────────────────────── --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h2 class="text-sm font-semibold uppercase tracking-wider mb-4
                   pb-2 border-b border-gray-100"
            style="color: #1A3A6B;">
            Informations du compte
        </h2>
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
                <p class="text-gray-500 text-xs">Créé le</p>
                <p class="font-medium text-gray-800">
                    {{ $user->created_at->format('d/m/Y à H:i') }}
                </p>
            </div>
            <div>
                <p class="text-gray-500 text-xs">Dernière connexion</p>
                <p class="font-medium text-gray-800">
                    {{ $user->last_login_at
                        ? $user->last_login_at->format('d/m/Y à H:i')
                        : 'Jamais' }}
                </p>
            </div>
        </div>
    </div>

</div>

@endsection
