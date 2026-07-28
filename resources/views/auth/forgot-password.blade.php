<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mot de passe oublié — PERSEVERANCE</title>
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
        .btn-primary:hover {
            background-color: #C8A415;
            transform: translateY(-1px);
        }
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
                    Mot de passe oublié ?
                </h1>
                <p class="text-sm text-gray-500 mt-2">
                    Entrez votre adresse e-mail. Nous vous enverrons
                    un lien pour réinitialiser votre mot de passe.
                </p>
            </div>

            {{-- Message de succès --}}
            @if (session('status'))
                <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg">
                    <p class="text-sm text-green-600">{{ session('status') }}</p>
                </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}">
                @csrf

                {{-- Email --}}
                <div class="mb-5">
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
                               value="{{ old('email') }}"
                               placeholder="nom@perseverance.cm"
                               required autofocus
                               class="input-field w-full pl-10 pr-4 py-3 border
                                      border-gray-200 rounded-lg text-sm text-gray-700
                                      bg-gray-50 @error('email') border-red-400 @enderror">
                    </div>
                    @error('email')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Bouton --}}
                <button type="submit"
                        class="btn-primary w-full py-3 rounded-lg text-white
                               font-semibold text-base flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7
                                 a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    Envoyer le lien
                </button>

                {{-- Retour connexion --}}
                <div class="text-center mt-4">
                    <a href="{{ route('login') }}"
                       class="text-sm font-semibold hover:underline flex items-center
                              justify-center gap-1" style="color: #1A3A6B;">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        Retour à la connexion
                    </a>
                </div>
            </form>
        </div>

        <div class="px-8 py-3 bg-gray-50 border-t border-gray-100 text-center">
            <p class="text-xs text-gray-400">
                Vérifiez également vos spams si vous ne recevez pas l'e-mail.
            </p>
        </div>
    </div>

    <p class="mt-6 text-xs" style="color: rgba(255,255,255,0.6);">
        PERSEVERANCE PLUS © 2026 • Tous droits réservés
    </p>

</body>
</html>