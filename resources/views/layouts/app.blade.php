<!DOCTYPE html>
<html lang="fr" class="h-full overflow-hidden">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#1A3A6B">
    <title>@yield('title', 'PERSEVERANCE PLUS') — GESPER</title>
    <link rel="icon" href="{{ asset('images/logo.jpg') }}" type="image/jpeg">
    <link rel="shortcut icon" href="{{ asset('images/logo.jpg') }}" type="image/jpeg">
    <link rel="apple-touch-icon" href="{{ asset('images/logo.jpg') }}">
    <link rel="mask-icon" href="{{ asset('images/logo.jpg') }}" color="#1A3A6B">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        [x-cloak] { display: none !important; }
        /* Scrollbar sidebar */
        .scrollbar-thin::-webkit-scrollbar { width: 4px; }
        .scrollbar-thin::-webkit-scrollbar-track { background: transparent; }
        .scrollbar-thin::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.2);
            border-radius: 2px;
        }
    </style>
</head>
<body class="h-full overflow-hidden bg-gray-50" x-data>

    {{-- ── OVERLAY MOBILE ───────────────────────────────────────────── --}}
    <div id="sidebar-overlay"
         onclick="toggleSidebar()"
         class="fixed inset-0 bg-black/50 z-30 hidden lg:hidden">
    </div>

    <div class="flex h-screen overflow-hidden">

        {{-- ── SIDEBAR ──────────────────────────────────────────────── --}}
        @include('layouts.partials.sidebar')

        {{-- ── CONTENU PRINCIPAL ───────────────────────────────────── --}}
        <div class="flex-1 flex flex-col min-w-0 min-h-0 overflow-hidden lg:ml-0">

            {{-- Navbar --}}
            @include('layouts.partials.navbar')

            {{-- Fil d'Ariane (optionnel) --}}
            @hasSection('breadcrumb')
            <div class="px-4 lg:px-6 pt-4">
                <nav class="flex items-center gap-2 text-sm text-gray-500">
                    @yield('breadcrumb')
                </nav>
            </div>
            @endif

            {{-- Contenu de la page --}}
            <main @class(['flex-1 py-4 lg:py-6 overflow-y-auto overflow-x-hidden', 'px-1 lg:px-2' => request()->routeIs('infirmary.*'), 'px-4 lg:px-6' => ! request()->routeIs('infirmary.*')])>
                {{-- Messages flash --}}
                @if(session('success'))
                <div class="mb-4 p-4 bg-green-50 border border-green-200
                            rounded-lg flex items-start gap-3">
                    <svg class="w-5 h-5 text-green-600 flex-shrink-0 mt-0.5"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-green-700 text-sm">{{ session('success') }}</p>
                </div>
                @endif

                @if(session('error'))
                <div class="mb-4 p-4 bg-red-50 border border-red-200
                            rounded-lg flex items-start gap-3">
                    <svg class="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-red-700 text-sm">{{ session('error') }}</p>
                </div>
                @endif

                @yield('content')
            </main>

            {{-- Footer --}}
            <footer class="shrink-0 px-4 lg:px-6 py-3 border-t border-gray-200 bg-white">
                <p class="text-xs text-gray-400 text-center">
                    PERSEVERANCE PLUS © 2026 — Plateforme de Gestion Scolaire
                </p>
            </footer>
            @stack('fixed_footer')

        </div>
    </div>

    {{-- Script sidebar responsive --}}
    <script>
        function toggleSidebar() {
            const sidebar  = document.getElementById('sidebar');
            const overlay  = document.getElementById('sidebar-overlay');
            const isOpen   = !sidebar.classList.contains('-translate-x-full');

            if (isOpen) {
                sidebar.classList.add('-translate-x-full');
                overlay.classList.add('hidden');
            } else {
                sidebar.classList.remove('-translate-x-full');
                overlay.classList.remove('hidden');
            }
        }

        // Fermer sidebar si on redimensionne vers desktop
        window.addEventListener('resize', () => {
            if (window.innerWidth >= 1024) {
                document.getElementById('sidebar-overlay').classList.add('hidden');
            }
        });
    </script>


    @if(request()->routeIs('finances.*'))
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (prefersReduced) return;

        const suffixPattern = /(FCFA|paiement\(s\)|paiements|reçu\(s\)|reçus|élève\(s\)|élèves|payeur\(s\)|débiteur\(s\))/i;
        const parseNumber = (raw) => Number(String(raw).replace(/[\s,.]/g, '')) || 0;
        const formatNumber = (value) => Math.round(value).toLocaleString('fr-FR');

        function financeCountUp(node, match, explicit = null) {
            const original = node.textContent;
            const target = explicit?.target ?? parseNumber(match[1]);
            if (!target || target < 2) return;

            const prefix = explicit?.prefix ?? original.slice(0, match.index);
            const suffix = explicit?.suffix ?? original.slice(match.index + match[1].length);
            const duration = 1150;
            const start = performance.now();

            const tick = (now) => {
                const progress = Math.min((now - start) / duration, 1);
                const eased = 1 - Math.pow(1 - progress, 3);
                node.textContent = prefix + formatNumber(target * eased) + suffix;
                if (progress < 1) requestAnimationFrame(tick);
                else node.textContent = original;
            };
            requestAnimationFrame(tick);
        }

        document.querySelectorAll('[data-count-up]').forEach((el) => {
            const target = Number(el.dataset.countUp || 0);
            const suffix = el.dataset.countSuffix || '';
            financeCountUp(el, ['0'], { target, prefix: '', suffix });
        });

        document.querySelectorAll('main *').forEach((el) => {
            if (el.dataset.countUp || el.children.length || ['SCRIPT','STYLE','SVG','OPTION','INPUT','TEXTAREA','SELECT'].includes(el.tagName)) return;
            const text = el.textContent.trim();
            if (!suffixPattern.test(text)) return;
            const match = text.match(/([0-9][0-9\s,.]*)/);
            if (match) financeCountUp(el, match);
        });
    });
    </script>
    @endif

    @stack('scripts')

</body>
</html>
