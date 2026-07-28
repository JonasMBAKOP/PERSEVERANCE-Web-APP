<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouveau mot de passe — PERSEVERANCE</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .bg-login { background-color: #2D7DD2; }
        .login-card { animation: fadeInUp 0.5s ease-out; }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .input-field:focus {
            outline: none;
            border-color: #1A5C2A;
            box-shadow: 0 0 0 3px rgba(26, 92, 42, 0.15);
        }
        .btn-primary {
            background-color: #1A5C2A;
            transition: background-color 0.2s ease, transform 0.1s ease;
        }
        .btn-primary:hover { background-color: #C8A415; transform: translateY(-1px); }
        .strength-bar { height: 4px; border-radius: 2px; transition: all 0.3s ease; }
    </style>
</head>
<body class="bg-login min-h-screen flex flex-col items-center justify-center p-4">

    <div class="login-card w-full max-w-md bg-white rounded-2xl shadow-2xl overflow-hidden">
        <div class="px-8 pt-8 pb-6">

            {{-- Logo --}}
            <div class="flex justify-center mb-4">
                <img src="{{ asset('images/logo.jpg') }}"
                     alt="Logo COPTAN"
                     class="max-h-20 max-w-20 object-contain">
            </div>

            {{-- Titre --}}
            <div class="text-center mb-6">
                <h1 class="text-xl font-bold" style="color: #1A3A6B;">
                    Nouveau mot de passe
                </h1>
                <p class="text-sm text-gray-500 mt-2">
                    Choisissez un mot de passe sécurisé pour votre compte.
                </p>
            </div>

            <form method="POST" action="{{ route('password.store') }}">
                @csrf
                <input type="hidden" name="token" value="{{ $request->route('token') }}">

                {{-- Email (caché mais requis) --}}
                <div class="mb-4">
                    <label class="block text-xs font-semibold tracking-wider
                                  uppercase mb-1" style="color: #1A3A6B;">
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
                               value="{{ old('email', $request->email) }}"
                               required
                               class="input-field w-full pl-10 pr-4 py-3 border
                                      border-gray-200 rounded-lg text-sm text-gray-700 bg-gray-50
                                      @error('email') border-red-400 @enderror">
                    </div>
                    @error('email')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Nouveau mot de passe --}}
                <div class="mb-4">
                    <label class="block text-xs font-semibold tracking-wider
                                  uppercase mb-1" style="color: #1A3A6B;">
                        Nouveau mot de passe
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
                               oninput="checkStrength(this.value)"
                               class="input-field w-full pl-10 pr-10 py-3 border
                                      border-gray-200 rounded-lg text-sm text-gray-700 bg-gray-50
                                      @error('password') border-red-400 @enderror">
                        <button type="button" onclick="togglePass('password', 'eye1')"
                                class="absolute inset-y-0 right-3 flex items-center
                                       text-gray-400 hover:text-gray-600">
                            <svg id="eye1" class="w-4 h-4" fill="none"
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

                    {{-- Barre de force du mot de passe --}}
                    <div class="mt-2">
                        <div class="flex gap-1 mb-1">
                            <div id="bar1" class="strength-bar flex-1 bg-gray-200"></div>
                            <div id="bar2" class="strength-bar flex-1 bg-gray-200"></div>
                            <div id="bar3" class="strength-bar flex-1 bg-gray-200"></div>
                            <div id="bar4" class="strength-bar flex-1 bg-gray-200"></div>
                        </div>
                        <p id="strength-text" class="text-xs text-gray-400"></p>
                    </div>

                    {{-- Critères --}}
                    <div class="mt-2 space-y-1">
                        <p id="c1" class="text-xs text-gray-400 flex items-center gap-1">
                            <span>○</span> Au moins 8 caractères
                        </p>
                        <p id="c2" class="text-xs text-gray-400 flex items-center gap-1">
                            <span>○</span> Une majuscule
                        </p>
                        <p id="c3" class="text-xs text-gray-400 flex items-center gap-1">
                            <span>○</span> Un chiffre
                        </p>
                    </div>
                    @error('password')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Confirmation --}}
                <div class="mb-5">
                    <label class="block text-xs font-semibold tracking-wider
                                  uppercase mb-1" style="color: #1A3A6B;">
                        Confirmer le mot de passe
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-3 flex items-center text-gray-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955
                                         11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824
                                         10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133
                                         -2.052-.382-3.016z"/>
                            </svg>
                        </span>
                        <input type="password"
                               id="password_confirmation"
                               name="password_confirmation"
                               placeholder="••••••••"
                               required
                               class="input-field w-full pl-10 pr-10 py-3 border
                                      border-gray-200 rounded-lg text-sm text-gray-700 bg-gray-50">
                        <button type="button"
                                onclick="togglePass('password_confirmation', 'eye2')"
                                class="absolute inset-y-0 right-3 flex items-center
                                       text-gray-400 hover:text-gray-600">
                            <svg id="eye2" class="w-4 h-4" fill="none"
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

                <button type="submit"
                        class="btn-primary w-full py-3 rounded-lg text-white
                               font-semibold text-base">
                    Réinitialiser le mot de passe
                </button>

            </form>
        </div>
    </div>

    <p class="mt-6 text-xs" style="color: rgba(255,255,255,0.6);">
        PERSEVERANCE PLUS © 2026 • Tous droits réservés
    </p>

    <script>
        function togglePass(id, iconId) {
            const input = document.getElementById(id);
            input.type = input.type === 'password' ? 'text' : 'password';
        }

        function checkStrength(val) {
            const bars  = ['bar1','bar2','bar3','bar4'];
            const c1    = val.length >= 8;
            const c2    = /[A-Z]/.test(val);
            const c3    = /[0-9]/.test(val);
            const score = [c1, c2, c3, val.length >= 12].filter(Boolean).length;

            // Critères visuels
            document.getElementById('c1').innerHTML =
                `<span>${c1 ? 'Valide' : 'En attente'}</span> Au moins 8 caractères`;
            document.getElementById('c1').className =
                `text-xs flex items-center gap-1 ${c1 ? 'text-green-600' : 'text-gray-400'}`;

            document.getElementById('c2').innerHTML =
                `<span>${c2 ? 'Valide' : 'En attente'}</span> Une majuscule`;
            document.getElementById('c2').className =
                `text-xs flex items-center gap-1 ${c2 ? 'text-green-600' : 'text-gray-400'}`;

            document.getElementById('c3').innerHTML =
                `<span>${c3 ? 'Valide' : 'En attente'}</span> Un chiffre`;
            document.getElementById('c3').className =
                `text-xs flex items-center gap-1 ${c3 ? 'text-green-600' : 'text-gray-400'}`;

            // Barres de force
            const colors = ['bg-red-400','bg-orange-400','bg-yellow-400','bg-green-500'];
            const labels = ['','Faible','Moyen','Fort','Très fort'];
            bars.forEach((b, i) => {
                document.getElementById(b).className =
                    `strength-bar flex-1 ${i < score ? colors[score-1] : 'bg-gray-200'}`;
            });
            document.getElementById('strength-text').textContent =
                val.length ? labels[score] : '';
        }
    </script>

</body>
</html>