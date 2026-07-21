<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>500</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { theme: { extend: { colors: { medical: '#0d9488' }}}}</script>
</head>
<body class="min-h-screen bg-stone-50 flex items-center justify-center p-6">
<div class="max-w-md text-center">
    <div class="text-6xl font-bold text-red-600">500</div>
    <h1 class="mt-4 text-xl font-semibold text-slate-900">Erreur serveur</h1>
    <p class="mt-2 text-sm text-slate-600">Une erreur inattendue s’est produite. Veuillez réessayer plus tard.</p>
    <a href="{{ url('/') }}" class="mt-6 inline-block rounded-lg bg-medical px-4 py-2 text-sm font-semibold text-white">Retour</a>
</div>
</body>
</html>
