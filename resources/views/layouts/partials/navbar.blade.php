{{-- ═══════════════════════════════════════════════════════
     NAVBAR — Barre de navigation supérieure
═══════════════════════════════════════════════════════ --}}

<header class="sticky top-0 z-30 bg-white border-b border-gray-200
               flex items-center h-16 px-4 gap-4 shadow-sm">

    {{-- Bouton hamburger (mobile) --}}
    <button onclick="toggleSidebar()"
            class="lg:hidden text-gray-500 hover:text-gray-700 flex-shrink-0">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round"
                  stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
        </svg>
    </button>

    {{-- Titre de la page --}}
    <div class="flex-1 min-w-0">
        <h1 class="text-base lg:text-lg font-semibold truncate"
            style="color: #1A3A6B;">
            @yield('page-title', 'Tableau de bord')
        </h1>
        @hasSection('page-subtitle')
        <p class="text-xs text-gray-500 truncate hidden sm:block">
            @yield('page-subtitle')
        </p>
        @endif
    </div>

    {{-- Partie droite de la navbar --}}
    <div class="flex items-center gap-2 flex-shrink-0">

        {{-- Année scolaire active --}}
        @php
            $activeYear = \Illuminate\Support\Facades\DB::table('academic_years')
                            ->where('is_active', true)->first();
        @endphp
        @if($activeYear)
        <span class="hidden md:flex items-center gap-1 text-xs
                     bg-blue-50 text-blue-700 px-2 py-1 rounded-full">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5
                         a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            {{ $activeYear->label }}
        </span>
        @endif

        {{-- Notifications --}}
        <button class="relative p-2 text-gray-500 hover:text-gray-700
                       hover:bg-gray-100 rounded-lg transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002
                         6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388
                         6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3
                         0 11-6 0v-1m6 0H9"/>
            </svg>
            {{-- Badge notification --}}
            <span class="absolute top-1.5 right-1.5 w-2 h-2 rounded-full
                         bg-red-500 ring-2 ring-white"></span>
        </button>

        {{-- Avatar utilisateur --}}
        @php $navUser = auth()->user(); @endphp
        <div class="relative" x-data="{ open: false }">
            <button @click="open = !open"
                    class="flex items-center gap-2 p-1.5 rounded-lg
                           hover:bg-gray-100 transition-colors">
                @if($navUser->photo && \Illuminate\Support\Facades\Storage::disk('public')->exists($navUser->photo))
                    <img src="{{ $navUser->photo_url }}"
                         alt="{{ $navUser->name }}"
                         class="w-8 h-8 rounded-full object-cover flex-shrink-0">
                @else
                    <div class="w-8 h-8 rounded-full flex items-center justify-center
                                font-bold text-xs flex-shrink-0"
                         style="background-color: #1A3A6B; color: white;">
                        {{ strtoupper(substr($navUser->name, 0, 2)) }}
                    </div>
                @endif
                <span class="hidden sm:block text-sm font-medium text-gray-700 max-w-24 truncate">
                    {{ explode(' ', $navUser->name)[0] }}
                </span>
                <svg class="w-4 h-4 text-gray-400 hidden sm:block" fill="none"
                     stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>

            {{-- Dropdown menu --}}
            <div x-show="open"
                 @click.away="open = false"
                 x-transition:enter="transition ease-out duration-100"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 class="absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-lg
                        border border-gray-100 py-1 z-50">
                <div class="px-4 py-2 border-b border-gray-100">
                    <p class="text-sm font-medium text-gray-800 truncate">
                        {{ auth()->user()->name }}
                    </p>
                    <p class="text-xs text-gray-500 truncate">
                        {{ auth()->user()->email }}
                    </p>
                </div>
                <a href="{{ route('profile.show') }}"
                   class="flex items-center gap-2 px-4 py-2 text-sm
                          text-gray-700 hover:bg-gray-50">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    Mon profil
                </a>
                <div class="border-t border-gray-100 mt-1 pt-1">
                    <form method="POST" action="{{ route('logout') }}" onsubmit="return confirm('Êtes-vous sûr de vouloir vous déconnecter ?');">
                        @csrf
                        <button type="submit"
                                class="w-full flex items-center gap-2 px-4 py-2
                                       text-sm text-red-600 hover:bg-red-50">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7
                                         a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                            Déconnexion
                        </button>
                    </form>
                </div>
            </div>
        </div>

    </div>
</header>
