{{-- ═══════════════════════════════════════════════════════
     SIDEBAR — Navigation latérale COPTAN
     Responsive : overlay mobile / fixe desktop
═══════════════════════════════════════════════════════ --}}

<aside id="sidebar"
       class="fixed top-0 left-0 h-full z-40 flex flex-col
              transition-all duration-300 ease-in-out
              w-64 -translate-x-full
              lg:translate-x-0 lg:static lg:z-auto"
       style="background-color: #1A3A6B;">

    {{-- ── LOGO & NOM ÉCOLE ─────────────────────────────────── --}}
    <div class="flex items-center gap-3 px-4 py-4
                border-b border-white/10">
        <img src="{{ asset('images/logo.jpg') }}"
             alt="COPTAN"
             class="w-10 h-10 flex-shrink-0">
        {{-- <img src="{{ asset('images/logo.jpg') }}"
             alt="COPTAN"
             class="w-10 h-10 object-contain rounded-full
                    ring-2 ring-white/30 flex-shrink-0"> --}}
        <div class="overflow-hidden">
            <p class="text-white font-bold text-base leading-tight truncate">
                GESCOP
            </p>
            <p class="text-white/60 text-sm truncate">
                Gestion Scolaire
            </p>
        </div>
        {{-- Bouton fermer sidebar (mobile) --}}
        <button onclick="toggleSidebar()"
                class="ml-auto text-white/60 hover:text-white lg:hidden">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    {{-- ── NAVIGATION SCROLLABLE ────────────────────────────────── --}}
    <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1
                scrollbar-thin scrollbar-thumb-white/20">

        {{-- Dashboard --}}
        <x-sidebar-item
            icon="home"
            label="Tableau de bord"
            :href="auth()->user()->getDashboardRoute()"
            :active="request()->routeIs('*.dashboard')" />

        {{-- ── ACADÉMIQUE ────────────────────────────────────────── --}}
        @canany(['view-classes', 'manage-classes'])
            <div class="mx-1 mt-4 mb-1 rounded-lg px-3 py-2 text-[11px] font-bold uppercase tracking-[0.24em] shadow-sm"
                style="background-color: #D946EF; color: #ffffff;">
                Académique
            </div>

            @can('manage-academic-years')
                <x-sidebar-item
                    icon="calendar"
                    label="Années scolaires"
                    href="{{ route('academic-years.index') }}"
                    :active="request()->routeIs('academic-years.*')" />
            @endcan

            <x-sidebar-item
                icon="building"
                label="Sections & Classes"
                href="{{ route('classes.index') }}"
                {{-- :active="request()->routeIs('class-groups.*')" /> --}}
                :active="request()->routeIs('classes.*')" />

            @can('manage-subjects')
                <x-sidebar-item
                    icon="book"
                    label="Matières"
                    href="{{ route('subjects.index') }}"
                    :active="request()->routeIs('subjects.*')" />
            @endcan

            <x-sidebar-item
                icon="clock"
                label="Emploi du temps"
                href="{{ route('timetable.index') }}"
                :active="request()->routeIs('timetable.index')" />

            <x-sidebar-item 
                icon="calendar" 
                label="Mon emploi du temps"
                href="{{ route('timetable.teacher') }}"
                :active="request()->routeIs('timetable.teacher')" />
        @endcanany

        {{-- ── ÉLÈVES ────────────────────────────────────────────── --}}
        @can('view-students')
            <div class="mx-1 mt-4 mb-1 rounded-lg px-3 py-2 text-[10px] sm:text-[11px] font-bold uppercase tracking-[0.24em] shadow-sm"
                style="background-color: #1D4ED8; color: #ffffff;">
                Élèves
            </div>

            <x-sidebar-item
                icon="users"
                {{-- icon="currency-dollar" --}}
                label="Élèves"
                href="{{ route('students.index') }}"
                :active="request()->routeIs('students.*') && !request()->routeIs('students.documents.*') 
                    && !request()->routeIs('students.create')
                    && !request()->routeIs('students.enroll')" />

            @can('manage-enrollments')
                <x-sidebar-item
                    icon="user-plus"
                    label="Inscriptions"
                    href="{{ route ('students.create') }}"
                    :active="request()->routeIs('students.create', 'students.enroll')" />
            @endcan
        @endcan

        {{-- ── DOCUMENTS (hors finances) ─────────────────────────── --}}
        @can('view-students')
            <div class="mx-1 mt-4 mb-1 rounded-lg px-3 py-2 text-[10px] sm:text-[11px] font-bold uppercase tracking-[0.24em] shadow-sm"
                style="background-color: #B45309; color: #ffffff;">
                Documents
            </div>

            <x-sidebar-item
                icon="document"
                label="Impressions élèves"
                href="{{ route('students.documents.index') }}"
                :active="request()->routeIs('students.documents.*')" />
        @endcan

        {{-- ── PERSONNEL ─────────────────────────────────────────── --}}
        @can('view-staff')
            <div class="mx-1 mt-4 mb-1 rounded-lg px-3 py-2 text-[10px] sm:text-[11px] font-bold uppercase tracking-[0.24em] shadow-sm"
                style="background-color: #6D28D9; color: #ffffff;">
                Personnel
            </div>

            <x-sidebar-item
                icon="briefcase"
                {{-- icon="users" --}}
                {{-- label="Personnel" --}}
                label="Enseignants & Staff"
                href="{{ route('staff.index') }}"
                :active="request()->routeIs('staff.*') && !request()->routeIs('staff.presences.*') && !request()->routeIs('staff.passage-planning') && !request()->routeIs('staff.salaries') && !request()->routeIs('staff.salary.edit')" />

            <x-sidebar-item
                icon="calendar"
                label="Planning de passage"
                href="{{ route('staff.passage-planning') }}"
                :active="request()->routeIs('staff.passage-planning')" />

            <x-sidebar-item
                icon="clipboard"
                label="Présences"
                href="{{ route('staff.presences.index') }}"
                :active="request()->routeIs('staff.presences.*')" />

            <x-sidebar-item
                icon="bank"
                label="Salaires"
                href="{{ route('staff.salaries') }}"
                :active="request()->routeIs('staff.salaries', 'staff.salary.edit')" />
        @endcan    
        

        {{-- ── EVALUATIONS ET NOTES ─────────────────────────────────────────── --}}
        @canany(['view-grades', 'enter-grades',])
            <div class="mx-1 mt-4 mb-1 rounded-lg px-3 py-2 text-[11px] font-bold uppercase tracking-[0.24em] shadow-sm"
                style="background-color: #BE185D; color: #ffffff;">
                évaluations
            </div>
        @endcanany

        @can('manage-academic-years')
            <x-sidebar-item
                icon="eye"
                label="Vue Globale"
                href="{{ route('grades.index')}}"
                :active="request()->routeIs('grades.index')" />
        @endcan

        @canany(['view-grades', 'enter-grades'])
            <x-sidebar-item
                icon="search"
                label="Consultation Notes"
                href="{{ route('grades.notes')}}"
                :active="request()->routeIs('grades.notes')" />
        @endcanany

        @can('enter-grades')
            <x-sidebar-item
                icon="pencil"
                label="Saisie des notes"
                href="{{ route('grades.entry.form')}}"
                :active="request()->routeIs('grades.entry*')" />
        @endcan

        {{-- @can('validate-grades')
        <x-sidebar-item
            icon="check-circle"
            label="Validation des notes"
            href="#"
            :active="request()->routeIs('grades.validate*')" />
        @endcan --}}

        @can('view-bulletins')
            <x-sidebar-item
                icon="document"
                label="Bulletins"
                href="{{ route('bulletins.index') }}"
                :active="request()->routeIs('bulletins.*')" />
        @endcan
        {{-- @endcanany --}}

        
        {{-- ── PRÉSENCES ÉLÈVES ─────────────────────────────────────────── --}}
        @can('view-absences')
            <div class="mx-1 mt-4 mb-1 rounded-lg px-3 py-2 text-[11px] font-bold uppercase tracking-[0.24em] shadow-sm"
                style="background-color: #D97706; color: #ffffff;">
                Présences élèves
            </div>

            @can('manage-absences')
                <x-sidebar-item
                    icon="clipboard"
                    label="Appel du jour"
                    href="#"
                    :active="request()->routeIs('attendance.*')" />
            @endcan

            <x-sidebar-item
                icon="x-circle"
                label="Absences"
                href="{{ route('absences.index') }}"
                :active="request()->routeIs('absences.*')" />
        @endcan

        {{-- ── INFIRMERIE ──────────────────────────────────────────── --}}
        @can('view-health')
            <div class="mx-1 mt-4 mb-1 rounded-lg px-3 py-2 text-[11px] font-bold uppercase tracking-[0.24em] shadow-sm"
                style="background-color: #0F766E; color: #ffffff;">
                Infirmerie
            </div>
            <x-sidebar-item
                icon="clipboard"
                label="Consultations"
                href="{{ route('infirmary.index') }}"
                :active="request()->routeIs('infirmary.index') || request()->routeIs('infirmary.edit') || request()->routeIs('infirmary.print')" />

            <x-sidebar-item
                icon="folder-open"
                label="Dossiers Patients"
                href="{{ route('infirmary.patients') }}"
                :active="request()->routeIs('infirmary.patients') || request()->routeIs('infirmary.patients.show')" />

            @can('manage-health')
                <x-sidebar-item
                    icon="heart"
                    label="Nouvelle Consultation"
                    href="{{ route('infirmary.create') }}"
                    :active="request()->routeIs('infirmary.create')" />
            @endcan
        @endcan

        {{-- ── FINANCES ──────────────────────────────────────────── --}}
        @can('view-finances')
            <div class="mx-1 mt-4 mb-1 rounded-lg px-3 py-2 text-[11px] font-bold uppercase tracking-[0.24em] text-white shadow-sm"
                 style="background-color: #059669;">
                Finances
            </div>

            @if(auth()->user()->hasAnyRole(['directeur', 'super-admin']))
            <x-sidebar-item
                icon="currency-dollar"
                label="Gestion Globale"
                href="{{ route('finances.global') }}"
                :active="request()->routeIs('finances.global')" />
            @endif
                
            <x-sidebar-item
                icon="currency-dollar"
                label="Finances"
                href="{{ route('finances.index') }}"
                :active="request()->routeIs('finances.index', 'finances.class-students')" />
                
            <x-sidebar-item
                icon="cash"
                label="Paiements"
                href="{{ route('finances.payments') }}"
                :active="request()->routeIs('finances.payments', 'finances.student')" />
            <x-sidebar-item
                icon="currency-dollar"
                label="Bourses"
                href="{{ route('finances.scholarships') }}"
                :active="request()->routeIs('finances.scholarships*')" />
            <x-sidebar-item
                icon="x-circle"
                label="Élèves insolvables"
                href="{{ route('finances.insolvables') }}"
                :active="request()->routeIs('finances.insolvables*')" />

            @can('configure-fees')
            <x-sidebar-item
                icon="cog"
                label="Config. des frais"
                href="{{ route('finances.fees-list') }}"
                :active="request()->routeIs('finances.fees-list', 'finances.fees')" />
            @endcan

            <x-sidebar-item
                icon="chart-bar"
                label="Rapports financiers"
                href="{{ route('finances.reports') }}"
                :active="request()->routeIs('finances.reports*')" />
        @endcan

        {{-- ── DISCIPLINE ────────────────────────────────────────── --}}
        @can('view-discipline')
            <div class="mx-1 mt-4 mb-1 rounded-lg px-3 py-2 text-[11px] font-bold uppercase tracking-[0.24em] shadow-sm"
                style="background-color: #DC2626; color: #ffffff;">
                Discipline
            </div>

            <x-sidebar-item
                icon="shield"
                label="Incidents"
                href="{{ route('discipline.index') }}"
                :active="request()->routeIs('discipline.*')" />
        @endcan

        {{-- ── COMMUNICATION ─────────────────────────────────────── --}}
        <div class="mx-1 mt-4 mb-1 rounded-lg px-3 py-2 text-[11px] font-bold uppercase tracking-[0.24em] shadow-sm"
             style="background-color: #F59E0B; color: #ffffff;">
            Communication
        </div>

        <x-sidebar-item
            {{-- icon="bell" --}}
            {{-- icon="phone" --}}
            icon="speakerphone"
            label="Annonces"
            href="{{ route('communication.announcements.index') }}"
            {{-- :active="request()->routeIs('announcements.*')" /> --}}
            :active="request()->routeIs('communication.announcements.*')" />

        <x-sidebar-item
            icon="mail"
            label="Messagerie"
            href="{{ route('communication.messages.index') }}"
            {{-- :active="request()->routeIs('messages.*')" /> --}}
            :active="request()->routeIs('communication.messages.*')" />

        @can('manage-parent-communication')
            <x-sidebar-item
                icon="chat"
                label="Messages Parents"
                href="{{ route('communication.parents.index') }}"
                :active="request()->routeIs('communication.parents.*')" />
        @endcan

        {{-- ── ADMINISTRATION ────────────────────────────────────── --}}
        @canany(['manage-settings', 'manage-users'])
            <div class="mx-1 mt-4 mb-1 rounded-lg px-3 py-2 text-[11px] font-bold uppercase tracking-[0.24em] shadow-sm"
                style="background-color: #06B6D4; color: #ffffff;">
                Administration
            </div>

            @can('manage-users')
                <x-sidebar-item
                    icon="user-group"
                    label="Comptes utilisateurs"
                    href="{{ route('users.index') }}"
                    :active="request()->routeIs('users.*')" />
            @endcan

            @can('manage-settings')
                <x-sidebar-item
                    icon="adjustments"
                    label="Paramètres"
                    href="{{ route('settings.index') }}"
                    :active="request()->routeIs('settings.*')" />
            @endcan
        @endcanany

    </nav>

    {{-- ── INFOS UTILISATEUR (bas de sidebar) ─────────────────── --}}
    @php $sideUser = auth()->user(); @endphp
    <div class="border-t border-white/10 px-3 py-3">
        <div class="flex items-center gap-3">
            @if($sideUser->photo || $sideUser->staff?->photo)
                <img src="{{ $sideUser->photo_url }}"
                     alt="{{ $sideUser->name }}"
                     class="w-9 h-9 rounded-full object-cover flex-shrink-0 border border-white/20">
            @else
                <div class="w-9 h-9 rounded-full flex items-center justify-center
                            flex-shrink-0 font-bold text-sm"
                     style="background-color: #C8A415; color: #1A3A6B;">
                    {{ strtoupper(substr($sideUser->name, 0, 2)) }}
                </div>
            @endif
            <div class="flex-1 min-w-0">
                <p class="text-white text-sm font-medium truncate">
                    {{ $sideUser->name }}
                </p>
                <p class="text-white/50 text-xs truncate">
                    {{ ucfirst(auth()->user()->getRoleNames()->first() ?? '') }}
                </p>
            </div>
            {{-- Déconnexion --}}
            <form method="POST" action="{{ route('logout') }}" onsubmit="return confirm('Êtes-vous sûr de vouloir vous déconnecter ?');">
                @csrf
                <button type="submit"
                        title="Déconnexion"
                        class="text-white/50 hover:text-white transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7
                                 a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                </button>
            </form>
        </div>
    </div>

</aside>
