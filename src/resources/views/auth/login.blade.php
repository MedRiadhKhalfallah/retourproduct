<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Connexion — {{ config('app.name') }}</title>
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 font-sans text-slate-800 antialiased">
<main class="min-h-screen flex items-center justify-center px-4 py-10">
    <section class="w-full max-w-md rounded-3xl bg-white p-6 sm:p-8 shadow-xl shadow-slate-200/70">
        <x-app-logo :href="route('login')" class="mb-6 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-stone-500 focus-visible:ring-offset-2" />
        <h1 class="text-2xl font-bold tracking-tight">Connexion</h1>
        <p class="mt-1 text-sm text-slate-500">Connectez-vous pour accéder à la gestion des stocks.</p>

        @if ($errors->any())
            <div class="mt-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('login.store') }}" class="mt-6 space-y-5">
            @csrf
            <label class="block text-sm font-medium">
                Adresse e-mail
                <input type="email" name="email" value="{{ old('email') }}" autocomplete="username" required autofocus
                    class="mt-1.5 w-full rounded-xl border border-slate-300 px-4 py-3 text-base outline-none transition focus:border-slate-700 focus:ring-4 focus:ring-slate-100">
            </label>
            <label class="block text-sm font-medium">
                Mot de passe
                <input type="password" name="password" autocomplete="current-password" required
                    class="mt-1.5 w-full rounded-xl border border-slate-300 px-4 py-3 text-base outline-none transition focus:border-slate-700 focus:ring-4 focus:ring-slate-100">
            </label>
            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" name="remember" value="1" class="h-4 w-4 rounded border-slate-300">
                Se souvenir de moi
            </label>
            <button type="submit" class="w-full rounded-xl bg-slate-900 px-4 py-3.5 font-semibold text-white transition hover:bg-slate-700 focus:outline-none focus:ring-4 focus:ring-slate-300">
                Se connecter
            </button>
        </form>
        <p class="mt-6 text-center text-xs text-slate-400">Accès réservé aux utilisateurs autorisés.</p>
    </section>
</main>
</body>
</html>
