<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    @php
        $heroLocal = public_path('images/hero-hospital-interior.jpg');
        $heroImage = file_exists($heroLocal)
            ? asset('images/hero-hospital-interior.jpg')
            : 'https://images.unsplash.com/photo-1519494026892-80bbd2d6fd0d?auto=format&fit=crop&w=1400&q=80';
        $heroQuote = 'La santé commence par une prévention régulière et un suivi médical de qualité.';
    @endphp
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
    @stack('head')
</head>
<body>
<div class="auth-shell">
    <div class="auth-hero-mobile" aria-hidden="true">
        <img src="{{ $heroImage }}" alt="">
        <div class="overlay"></div>
        <p class="caption">{{ $heroQuote }}</p>
    </div>

    <div class="auth-hero-desktop" aria-hidden="true">
        <img src="{{ $heroImage }}" alt="">
        <div class="overlay"></div>
        <p class="caption">{{ $heroQuote }}</p>
    </div>

    <div class="auth-panel">
        <div class="auth-panel-inner">
            @yield('content')
        </div>
    </div>
</div>
@stack('scripts')
</body>
</html>
