@extends('layouts.app')

@section('title', 'Modifier mon profil')
@section('page-title', 'Modifier mon profil')
@section('page-subtitle', 'Mettez à jour vos informations')

@section('breadcrumb')
    <a href="{{ route('profile.show') }}" class="hover:text-gray-700">Mon Profil</a>
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
    </svg>
    <span style="color:#1A3A6B;" class="font-medium">Modifier</span>
@endsection

@section('content')

<form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
    @csrf @method('PUT')

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- ── Colonne principale ────────────────────────────────────────── --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Identité --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-sm font-black mb-4 pb-2 border-b border-gray-100"
                    style="color:#1A3A6B;">
                    Informations personnelles
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Nom complet <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}"
                               class="w-full px-3 py-2.5 border rounded-lg text-sm
                                      focus:outline-none
                                      @error('name') border-red-400 @else border-gray-200 @enderror">
                        @error('name')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Email <span class="text-red-500">*</span>
                        </label>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}"
                               class="w-full px-3 py-2.5 border rounded-lg text-sm
                                      focus:outline-none
                                      @error('email') border-red-400 @else border-gray-200 @enderror">
                        @error('email')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Téléphone
                        </label>
                        <input type="text" name="phone" value="{{ old('phone', $user->phone) }}"
                               placeholder="+237 6XX XXX XXX"
                               class="w-full px-3 py-2.5 border border-gray-200 rounded-lg
                                      text-sm focus:outline-none">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Établissement d'origine
                        </label>
                        <input type="text" name="origin_school"
                               value="{{ old('origin_school', $user->staff?->origin_school) }}"
                               placeholder="Ex : ENSET Douala, Université de Yaoundé I…"
                               class="w-full px-3 py-2.5 border border-gray-200 rounded-lg
                                      text-sm focus:outline-none">
                    </div>
                </div>
            </div>

            {{-- Mot de passe --}}
            <div id="password" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-sm font-black mb-4 pb-2 border-b border-gray-100"
                    style="color:#1A3A6B;">
                    Changer le mot de passe
                    <span class="text-gray-400 font-normal normal-case tracking-normal ml-1">
                        (optionnel)
                    </span>
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Mot de passe actuel
                        </label>
                        <input type="password" name="current_password"
                               class="w-full px-3 py-2.5 border rounded-lg text-sm
                                      focus:outline-none
                                      @error('current_password') border-red-400 @else border-gray-200 @enderror">
                        @error('current_password')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Nouveau mot de passe
                        </label>
                        <input type="password" name="new_password"
                               class="w-full px-3 py-2.5 border rounded-lg text-sm
                                      focus:outline-none
                                      @error('new_password') border-red-400 @else border-gray-200 @enderror">
                        @error('new_password')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Confirmer
                        </label>
                        <input type="password" name="new_password_confirmation"
                               class="w-full px-3 py-2.5 border border-gray-200 rounded-lg
                                      text-sm focus:outline-none">
                    </div>
                </div>
                <p class="text-xs text-gray-400 mt-2">
                    Laissez vide si vous ne souhaitez pas changer de mot de passe.
                </p>
            </div>

        </div>

        {{-- ── Colonne droite ────────────────────────────────────────────── --}}
        <div class="space-y-4">

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5"
                 x-data="{ preview: '{{ $user->photo ? asset('storage/'.$user->photo) : '' }}' }">
                <h3 class="text-sm font-black mb-4 pb-2 border-b border-gray-100"
                    style="color:#1A3A6B;">
                    Photo de profil
                </h3>
                <div class="flex flex-col items-center gap-3">
                    <template x-if="preview">
                        <img :src="preview" class="w-28 h-28 rounded-full object-cover
                                                    ring-4 ring-gray-100">
                    </template>
                    <template x-if="!preview">
                        <div class="w-28 h-28 rounded-full flex items-center justify-center"
                             style="background-color:#EBF3FB;">
                            <span class="text-3xl font-black" style="color:#1A3A6B;">
                                {{ strtoupper(substr($user->name, 0, 2)) }}
                            </span>
                        </div>
                    </template>
                    <div class="flex items-center gap-3">
                        <label class="cursor-pointer text-xs font-bold hover:underline"
                               style="color:#1A3A6B;">
                            Changer la photo
                            <input type="file" name="photo" class="hidden" accept="image/*"
                                   @change="preview = URL.createObjectURL($event.target.files[0])">
                        </label>
                        @if($user->photo)
                        <button type="button"
                                onclick="document.getElementById('deletePhotoForm').submit()"
                                class="text-xs text-red-500 hover:underline">
                            Supprimer
                        </button>
                        @endif
                    </div>
                </div>
            </div>

            {{-- ── CACHET (sauf enseignants) ──────────────────────────────── --}}
            @if(!auth()->user()->hasRole('enseignant'))
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5"
                 x-data="{ sealPreview: '{{ $user->signature_seal ? asset('storage/'.$user->signature_seal) : '' }}' }">
                <h3 class="text-sm font-black mb-4 pb-2 border-b border-gray-100"
                    style="color:#1A3A6B;">
                    Cachet personnel
                </h3>
                <p class="text-xs text-gray-500 mb-3">
                    Votre signature ou cachet numérique apparaîtra sur les documents officiels.
                </p>
                <div class="flex flex-col items-center gap-3">
                    <template x-if="sealPreview">
                        <div class="flex items-center justify-center w-24 h-24 rounded-lg"
                             style="background-color:#F0F7FC; border:2px dashed #7FA6C4;">
                            <img :src="sealPreview" class="max-w-full max-h-full object-contain">
                        </div>
                    </template>
                    <template x-if="!sealPreview">
                        <div class="w-24 h-24 rounded-lg flex items-center justify-center"
                             style="background-color:#F0F7FC; border:2px dashed #7FA6C4;">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="12" cy="12" r="10" stroke="#7FA6C4" stroke-width="2" fill="#EFF6FF"/>
                                <path d="M7 12.5L10.5 16L17 9.5" stroke="#1A3A6B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                    </template>
                    <div class="flex items-center gap-3">
                        <label class="cursor-pointer text-xs font-bold hover:underline"
                               style="color:#1A3A6B;">
                            {{ $user->signature_seal ? 'Changer le cachet' : 'Ajouter un cachet' }}
                            <input type="file" name="signature_seal" class="hidden" accept="image/*"
                                   @change="sealPreview = URL.createObjectURL($event.target.files[0])">
                        </label>
                        @if($user->signature_seal)
                        <button type="button" onclick="if(confirm('Confirmer la suppression ?')) document.getElementById('deleteSealForm').submit()"
                                class="text-xs text-red-500 hover:underline">
                            Supprimer
                        </button>
                        @endif
                    </div>
                </div>
            </div>
            @endif

            <button type="submit"
                    class="w-full py-3.5 rounded-xl text-white font-bold text-sm
                           flex items-center justify-center gap-2 hover:shadow-md
                           transition-all" style="background-color:#1A5C2A;">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M5 13l4 4L19 7"/>
                </svg>
                Enregistrer les modifications
            </button>

            <a href="{{ route('profile.show') }}"
               class="block w-full py-2.5 rounded-xl text-center text-sm font-medium
                      text-gray-600 border border-gray-200 hover:bg-gray-50">
                Annuler
            </a>
        </div>

    </div>
</form>

<form method="POST" action="{{ route('profile.photo.delete') }}"
      id="deletePhotoForm" class="hidden">
    @csrf @method('DELETE')
</form>

<form method="POST" action="{{ route('profile.seal.delete') }}"
      id="deleteSealForm" class="hidden">
    @csrf @method('DELETE')
</form>

@endsection
