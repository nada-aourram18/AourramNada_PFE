<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    @php
        $heroImage = asset('images/hero-hospital-interior.jpg');
    @endphp
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { medical: { DEFAULT: '#0d9488', dark: '#0f766e' } } } } };
    </script>
    <style>body { font-family: "Plus Jakarta Sans", system-ui, sans-serif; }</style>
</head>
<body class="min-h-screen bg-stone-100 antialiased text-stone-900">
<div class="relative min-h-screen">
    <img src="{{ $heroImage }}" alt="" class="absolute inset-0 h-full w-full object-cover object-center opacity-75" fetchpriority="high">
    <div class="absolute inset-0 bg-teal-950/25"></div>

    <div class="relative mx-auto flex min-h-screen w-full max-w-6xl items-center justify-center px-4 py-10 sm:px-8">
        <div class="w-full max-w-sm p-5 sm:p-6">
            <div class="flex flex-col gap-3 sm:flex-row">
                <a href="{{ route('login') }}"
                   class="inline-flex flex-1 items-center justify-center rounded-xl bg-medical px-6 py-2.5 text-sm font-semibold text-white shadow-md shadow-medical/20 transition hover:bg-medical-dark">
                    Login
                </a>
                <a href="{{ route('register') }}"
                   class="inline-flex flex-1 items-center justify-center rounded-xl bg-stone-200 px-6 py-2.5 text-sm font-semibold text-stone-800 transition hover:bg-stone-300">
                    Sign up
                </a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
