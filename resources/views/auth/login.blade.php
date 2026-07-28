<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion — PERSEVERANCE</title>
    <link rel="icon" href="{{ asset('images/logo.jpg') }}" type="image/jpeg">
    <link rel="shortcut icon" href="{{ asset('images/logo.jpg') }}" type="image/jpeg">
    <link rel="apple-touch-icon" href="{{ asset('images/logo.jpg') }}">
    <link rel="mask-icon" href="{{ asset('images/logo.jpg') }}" color="#1A3A6B">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        /* ── FOND OR/JAUNE (couleur logo) ─────────────────────────── */
        .bg-login {
            background-color: #2D7DD2;
            /* Suppression du motif de points */
        }

        /* ── Animation card ────────────────────────────────────────── */
        .login-card {
            animation: fadeInUp 0.5s ease-out;
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ── Input focus vert ──────────────────────────────────────── */
        .input-field:focus {
            outline: none;
            border-color: #1A5C2A;
            box-shadow: 0 0 0 3px rgba(26, 92, 42, 0.15);
        }

        /* ── Bouton vert → hover Or/Jaune ──────────────────────────── */
        .btn-primary {
            background-color: #1A5C2A;
            transition: background-color 0.2s ease, transform 0.1s ease;
        }
        .btn-primary:hover {
            background-color: #C8A415;
            transform: translateY(-1px);
        }
        .btn-primary:active {
            transform: translateY(0);
        }
    </style>
</head>
<body class="bg-login min-h-screen flex flex-col items-center justify-center p-4">

    {{-- ── CARD PRINCIPAL ──────────────────────────────────────────────── --}}
    <div class="login-card w-full max-w-md bg-white rounded-2xl shadow-2xl overflow-hidden">

        {{-- Corps du formulaire --}}
        <div class="px-8 pt-8 pb-6">

            {{-- Logo — affiché normalement sans cercle --}}
            <div class="flex justify-center mb-4">
                <img src="{{ asset('images/logo.jpg') }}"
                     alt="Logo COPTAN"
                     class="max-h-24 max-w-24 object-contain">
            </div>

            {{-- Titre --}}
            <div class="text-center mb-6">
                <h1 class="text-xl uppercase font-bold" style="color: #1A3A6B;">
                    ECOLE PRIVEE LAÏC BILINGUE LA PERSEVERANCE PLUS
                </h1>
                <p class="text-sm text-gray-500 mt-1">
                    Plateforme de Gestion Scolaire
                </p>
            </div>

            {{-- Messages d'erreur globaux --}}
            @if ($errors->any())
                <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                    @foreach ($errors->all() as $error)
                        <p class="text-sm text-red-600">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            {{-- Session status --}}
            @if (session('status'))
                <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg">
                    <p class="text-sm text-green-600">{{ session('status') }}</p>
                </div>
            @endif

            {{-- Formulaire --}}
            <form method="POST" action="{{ route('login') }}">
                @csrf

                {{-- Adresse e-mail --}}
                <div class="mb-4">
                    <label class="block text-xs font-semibold tracking-wider uppercase mb-1"
                           style="color: #1A3A6B;">
                        Adresse E-mail
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-3 flex items-center text-gray-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7
                                         a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </span>
                        <input type="email"
                               name="email"
                               value="{{ old('email') }}"
                               placeholder="nom@perseverance.cm"
                               required
                               autofocus
                               class="input-field w-full pl-10 pr-4 py-3 border border-gray-200
                                      rounded-lg text-sm text-gray-700 bg-gray-50
                                      @error('email') border-red-400 @enderror">
                    </div>
                </div>

                {{-- Mot de passe --}}
                <div class="mb-4">
                    <label class="block text-xs font-semibold tracking-wider uppercase mb-1"
                           style="color: #1A3A6B;">
                        Mot de Passe
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-3 flex items-center text-gray-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2
                                         2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </span>
                        <input type="password"
                               id="password"
                               name="password"
                               placeholder="••••••••"
                               required
                               class="input-field w-full pl-10 pr-10 py-3 border border-gray-200
                                      rounded-lg text-sm text-gray-700 bg-gray-50
                                      @error('password') border-red-400 @enderror">
                        <button type="button"
                                onclick="togglePassword()"
                                class="absolute inset-y-0 right-3 flex items-center
                                       text-gray-400 hover:text-gray-600">
                            <svg id="eye-icon" class="w-4 h-4" fill="none"
                                 stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943
                                         9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943
                                         -9.542-7z"/>
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Se souvenir de moi --}}
                <div class="flex items-center mb-5">
                    <input type="checkbox"
                           name="remember"
                           id="remember"
                           class="w-4 h-4 rounded border-gray-300 cursor-pointer"
                           style="accent-color: #1A5C2A;">
                    <label for="remember"
                           class="ml-2 text-sm text-gray-600 cursor-pointer">
                        Se souvenir de moi
                    </label>
                </div>

                {{-- Bouton connexion --}}
                <button type="submit"
                        class="btn-primary w-full py-3 rounded-lg text-white
                               font-semibold text-base flex items-center justify-center gap-2">
                    Connexion
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                    </svg>
                </button>

                {{-- Mot de passe oublié --}}
                <div class="text-center mt-4">
                    <a href="{{ route('password.request') }}"
                       class="text-sm font-semibold hover:underline"
                       style="color: #1A3A6B;">
                        Mot de passe oublié ?
                    </a>
                </div>

            </form>
        </div>

        {{-- Footer du card — Année scolaire --}}
        <div class="px-8 py-3 bg-gray-50 border-t border-gray-100
                    flex items-center justify-center gap-2">
            <svg class="w-4 h-4 text-gray-400" fill="none"
                 stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5
                         a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <span class="text-sm text-gray-500">
                Année scolaire :
                <span class="font-semibold" style="color: #1A3A6B;">
                    {{ $activeYear ? $activeYear->label : 'Non définie' }}
                </span>
            </span>
        </div>

    </div>

    {{-- Copyright --}}
    <p class="mt-6 text-xs" style="color: white;">
        PERSEVERANCE PLUS © 2026 • Tous droits réservés
    </p>

    {{-- Script toggle password --}}
    <script>
        function togglePassword() {
            const input   = document.getElementById('password');
            const icon    = document.getElementById('eye-icon');
            const isHidden = input.type === 'password';
            input.type = isHidden ? 'text' : 'password';
            icon.innerHTML = isHidden
                ? `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                         d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943
                            -9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243
                            4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532
                            7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5
                            c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132
                            5.411m0 0L21 21"/>`
                : `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                         d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                   <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                         d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943
                            9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943
                            -9.542-7z"/>`;
        }
    </script>

</body>
</html>