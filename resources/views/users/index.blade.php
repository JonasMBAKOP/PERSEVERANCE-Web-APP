@extends('layouts.app')

@section('title', 'Utilisateurs')
@section('page-title', 'Comptes Utilisateurs')
@section('page-subtitle', 'Gestion des accès à la plateforme')

@section('content')

{{-- ── BARRE D'ACTIONS ─────────────────────────────────────────────────── --}}
<div class="flex flex-col sm:flex-row sm:items-center justify-between
            gap-3 mb-6">

    {{-- Stats --}}
    <div class="flex items-center gap-4 text-sm text-gray-500">
        <span>
            <strong class="text-gray-800">{{ $totalUsers }}</strong>
            compte(s)
        </span>
        <span class="hidden sm:block text-gray-300">|</span>
        <span class="hidden sm:block">
            <strong class="text-green-600">{{ $activeUsers }}</strong> actifs
        </span>
    </div>

    {{-- Bouton créer --}}
    <a href="{{ route('users.create') }}"
       class="inline-flex items-center gap-2 px-4 py-2 rounded-lg
              text-white text-sm font-semibold transition-colors"
       style="background-color: #1A5C2A;">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round"
                  stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Créer un compte
    </a>
</div>

{{-- ── FILTRES ───────────────────────────────────────────────────────────── --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-4">
    <form method="GET" action="{{ route('users.index') }}"
          class="flex flex-col sm:flex-row gap-3">

        {{-- Recherche --}}
        <div class="relative flex-1">
            <span class="absolute inset-y-0 left-3 flex items-center text-gray-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </span>
            <input type="text"
                   name="search"
                   value="{{ request('search') }}"
                   placeholder="Rechercher par nom ou email..."
                   class="w-full pl-9 pr-4 py-2 border border-gray-200 rounded-lg
                          text-sm focus:outline-none focus:border-blue-400">
        </div>

        {{-- Filtre rôle --}}
        <select name="role"
                class="px-3 py-2 border border-gray-200 rounded-lg text-sm
                       focus:outline-none focus:border-blue-400 bg-white">
            <option value="">Tous les rôles</option>
            @foreach($roles as $role)
                <option value="{{ $role->name }}"
                        {{ request('role') === $role->name ? 'selected' : '' }}>
                    {{ ucfirst(str_replace('-', ' ', $role->name)) }}
                </option>
            @endforeach
        </select>

        {{-- Filtre statut --}}
        <select name="status"
                class="px-3 py-2 border border-gray-200 rounded-lg text-sm
                       focus:outline-none focus:border-blue-400 bg-white">
            <option value="">Tous les statuts</option>
            <option value="active"   {{ request('status') === 'active'   ? 'selected' : '' }}>
                Actifs
            </option>
            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>
                Inactifs
            </option>
        </select>

        <button type="submit"
                class="px-4 py-2 rounded-lg text-white text-sm font-medium"
                style="background-color: #1A3A6B;">
            Filtrer
        </button>

        @if(request()->hasAny(['search','role','status']))
        <a href="{{ route('users.index') }}"
           class="px-4 py-2 rounded-lg border border-gray-200 text-gray-600
                  text-sm font-medium hover:bg-gray-50">
            Réinitialiser
        </a>
        @endif
    </form>
</div>

{{-- ── TABLEAU DES UTILISATEURS ─────────────────────────────────────────── --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">

    {{-- Desktop table --}}
    <div class="hidden md:block overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="border-b border-gray-100" style="background-color: #F8FAFC;">
                    <th class="text-left px-4 py-3 text-xs font-semibold
                               text-gray-500 uppercase tracking-wider">
                        Utilisateur
                    </th>
                    <th class="text-left px-4 py-3 text-xs font-semibold
                               text-gray-500 uppercase tracking-wider">
                        Rôle(s)
                    </th>
                    <th class="text-left px-4 py-3 text-xs font-semibold
                               text-gray-500 uppercase tracking-wider hidden lg:table-cell">
                        Dernière connexion
                    </th>
                    <th class="text-center px-4 py-3 text-xs font-semibold
                               text-gray-500 uppercase tracking-wider">
                        Statut
                    </th>
                    <th class="text-right px-4 py-3 text-xs font-semibold
                               text-gray-500 uppercase tracking-wider">
                        Actions
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($users as $user)
                <tr class="hover:bg-gray-50 transition-colors
                           {{ !$user->is_active ? 'opacity-60' : '' }}">

                    {{-- Utilisateur --}}
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            @php
                                $staffPhoto = $user->staff?->photo;
                            @endphp
                            @if($staffPhoto)
                                <img src="{{ asset('storage/' . $staffPhoto) }}" alt="{{ $user->name }}"
                                     class="w-9 h-9 rounded-full object-cover ring-2 ring-gray-100 flex-shrink-0">
                            @else
                                <div class="w-9 h-9 rounded-full flex items-center
                                            justify-center font-bold text-xs flex-shrink-0"
                                     style="background-color: #1A3A6B; color: white;">
                                    {{ strtoupper(substr($user->name, 0, 2)) }}
                                </div>
                            @endif
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-gray-900 truncate">
                                    {{ $user->name }}
                                    @if($user->id === auth()->id())
                                        <span class="text-xs text-blue-500">(moi)</span>
                                    @endif
                                </p>
                                <p class="text-xs text-gray-500 truncate">
                                    {{ $user->email }}
                                </p>
                            </div>
                        </div>
                    </td>

                    {{-- Rôles --}}
                    <td class="px-4 py-3">
                        <div class="flex flex-wrap gap-1">
                            @foreach($user->roles as $role)
                                @php
                                    $colors = [
                                        'super-admin'            => 'bg-gray-800 text-white',
                                        'directeur'              => 'bg-purple-100 text-purple-700',
                                        'censeur'                => 'bg-blue-100 text-blue-700',
                                        'econome'                => 'bg-orange-100 text-orange-700',
                                        'enseignant'             => 'bg-green-100 text-green-700',
                                        'surveillant-general'    => 'bg-gray-100 text-gray-700',
                                        'surveillant-de-secteur' => 'bg-indigo-100 text-indigo-700',
                                        'secretaire'             => 'bg-pink-100 text-pink-700',
                                    ];
                                    $color = $colors[$role->name] ?? 'bg-gray-100 text-gray-700';
                                @endphp
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium
                                             {{ $color }}">
                                    {{ ucfirst(str_replace('-', ' ', $role->name)) }}
                                </span>
                            @endforeach
                        </div>
                    </td>

                    {{-- Dernière connexion --}}
                    <td class="px-4 py-3 hidden lg:table-cell">
                        <span class="text-sm text-gray-500">
                            @if(cache()->has('user-is-online-' . $user->id))
                                En ligne
                            @elseif($user->last_login_at)
                                {{ $user->last_login_at->locale('fr')->diffForHumans() }}
                            @else
                                Jamais connecté
                            @endif
                        </span>
                    </td>

                    {{-- Statut --}}
                    <td class="px-4 py-3 text-center">
                        <form method="POST"
                              action="{{ route('users.toggle-active', $user) }}">
                            @csrf @method('PATCH')
                            <button type="submit"
                                    title="{{ $user->is_active ? 'Désactiver' : 'Activer' }}"
                                    class="inline-flex items-center gap-1 px-2.5 py-1
                                           rounded-full text-xs font-medium transition-colors
                                           {{ $user->is_active
                                               ? 'bg-green-100 text-green-700 hover:bg-green-200'
                                               : 'bg-red-100 text-red-700 hover:bg-red-200' }}">
                                <span class="w-1.5 h-1.5 rounded-full
                                             {{ $user->is_active
                                                 ? 'bg-green-500' : 'bg-red-500' }}">
                                </span>
                                {{ $user->is_active ? 'Actif' : 'Inactif' }}
                            </button>
                        </form>
                    </td>

                    {{-- Actions --}}
                    <td class="px-4 py-3 text-right">
                        <div class="flex items-center justify-end gap-2">

                            {{-- Modifier — visible seulement si on peut gérer ce user --}}
                            @if(auth()->user()->canManage($user) || auth()->id() === $user->id)
                            <a href="{{ route('users.edit', $user) }}"
                            title="Modifier"
                            class="p-1.5 rounded-lg text-gray-400 hover:text-blue-600
                                    hover:bg-blue-50 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5
                                            0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                </svg>
                            </a>
                            @endif

                            {{-- Supprimer — visible seulement si on peut gérer ce user --}}
                            @if(auth()->user()->canManage($user))
                            <form method="POST"
                                action="{{ route('users.destroy', $user) }}"
                                onsubmit="return confirm(
                                    'Supprimer définitivement le compte de {{ $user->name }} ?\nCette action est irréversible.')">
                                @csrf @method('DELETE')
                                <button type="submit"
                                        title="Supprimer"
                                        class="p-1.5 rounded-lg text-gray-400 hover:text-red-600
                                            hover:bg-red-50 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0
                                                01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0
                                                00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </form>
                            @endif

                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-4 py-12 text-center text-gray-400">
                        <svg class="w-12 h-12 mx-auto mb-3 opacity-30" fill="none"
                             stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  stroke-width="1.5"
                                  d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0
                                     -.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356
                                     -1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002
                                     5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6
                                     3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2
                                     0 014 0z"/>
                        </svg>
                        <p class="text-sm">Aucun utilisateur trouvé.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Mobile cards --}}
    <div class="md:hidden divide-y divide-gray-100">
        @forelse($users as $user)
        <div class="p-4 {{ !$user->is_active ? 'opacity-60' : '' }}">
            <div class="flex items-start justify-between gap-3">
                <div class="flex items-center gap-3 min-w-0">
                    @php
                        $staffPhoto = $user->staff?->photo;
                    @endphp
                    @if($staffPhoto)
                        <img src="{{ asset('storage/' . $staffPhoto) }}" alt="{{ $user->name }}"
                             class="w-10 h-10 rounded-full object-cover ring-2 ring-gray-100 flex-shrink-0">
                    @else
                        <div class="w-10 h-10 rounded-full flex items-center justify-center
                                    font-bold text-sm flex-shrink-0"
                             style="background-color: #1A3A6B; color: white;">
                            {{ strtoupper(substr($user->name, 0, 2)) }}
                        </div>
                    @endif
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-gray-900 truncate">
                            {{ $user->name }}
                        </p>
                        <p class="text-xs text-gray-500 truncate">{{ $user->email }}</p>
                        <div class="flex flex-wrap gap-1 mt-1">
                            @foreach($user->roles as $role)
                            <span class="px-2 py-0.5 rounded-full text-xs bg-blue-100
                                         text-blue-700">
                                {{ ucfirst(str_replace('-', ' ', $role->name)) }}
                            </span>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0">
                    <span class="px-2 py-0.5 rounded-full text-xs font-medium
                                 {{ $user->is_active
                                     ? 'bg-green-100 text-green-700'
                                     : 'bg-red-100 text-red-700' }}">
                        {{ $user->is_active ? 'Actif' : 'Inactif' }}
                    </span>
                    <a href="{{ route('users.edit', $user) }}"
                       class="p-1.5 rounded-lg text-gray-400 hover:text-blue-600
                              hover:bg-blue-50">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                             viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  stroke-width="2"
                                  d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0
                                     113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
        @empty
        <div class="p-8 text-center text-gray-400 text-sm">
            Aucun utilisateur trouvé.
        </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if($users->hasPages())
    <div class="px-4 py-3 border-t border-gray-100">
        {{ $users->links() }}
    </div>
    @endif

</div>

@endsection
