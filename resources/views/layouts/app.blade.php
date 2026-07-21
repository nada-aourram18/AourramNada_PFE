<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}"
    id="app-root"
>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.3/dist/cdn.min.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['"Plus Jakarta Sans"', 'system-ui', 'sans-serif'] },
                    colors: {
                        ink: '#1c1917',
                        navy: '#1c1917',
                        surface: '#fafaf9',
                        medical: { DEFAULT: '#0d9488', dark: '#0f766e', light: '#ccfbf1' },
                    },
                    boxShadow: {
                        soft: '0 1px 3px 0 rgb(0 0 0 / 0.05), 0 1px 2px -1px rgb(0 0 0 / 0.05)',
                    },
                },
            },
        };
    </script>
    <style>
        [x-cloak] { display: none !important; }
        body { font-family: "Plus Jakarta Sans", system-ui, sans-serif; }
    </style>
    @stack('head')
</head>
@php
    $navActive = 'flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-all duration-200 bg-medical text-white shadow-sm';
    $navInactive = 'flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-600 transition-all duration-200 hover:bg-medical-light/60 hover:text-medical-dark';
@endphp
<body class="min-h-screen bg-surface antialiased text-slate-900">

<div x-data="{ sidebarOpen: false, userMenu: false }" @keydown.escape.window="sidebarOpen = false; userMenu = false" class="min-h-screen">
    {{-- Mobile sidebar overlay --}}
    <div
        x-show="sidebarOpen"
        x-transition.opacity
        class="fixed inset-0 z-40 bg-slate-900/20 backdrop-blur-sm lg:hidden"
        @click="sidebarOpen = false"
        x-cloak
    ></div>

    {{-- Sidebar --}}
    <aside
        class="fixed inset-y-0 z-50 flex w-64 -translate-x-full flex-col border-r border-teal-100/80 bg-white transition-transform duration-200 ease-out lg:translate-x-0"
        :class="{ 'translate-x-0': sidebarOpen }"
    >
        @php
            $currentUser = auth()->user();
            $clinicName = filled($currentUser->clinic_name ?? null) ? $currentUser->clinic_name : __('messages.app.tagline');
        @endphp
        <div class="flex min-h-20 items-center gap-3 border-b border-teal-100/80 px-5 py-3">
            <x-user-avatar :user="$currentUser" size="sm" class="ring-2 ring-medical-light" />
            <div class="min-w-0">
                <div class="truncate text-base font-bold tracking-tight text-ink">Dr. {{ $currentUser->name }}</div>
                <div class="truncate text-[11px] font-semibold tracking-wide text-slate-500">{{ $clinicName }}</div>
            </div>
        </div>

        <nav class="flex flex-1 flex-col gap-0.5 overflow-y-auto p-3">
            <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? $navActive : $navInactive }}">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg {{ request()->routeIs('dashboard') ? 'bg-white/20' : 'bg-medical-light/70' }}">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/></svg>
                </span>
                {{ __('messages.nav.dashboard') }}
            </a>
            <a href="{{ route('patients.index') }}" class="{{ request()->routeIs('patients.*') ? $navActive : $navInactive }}">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg {{ request()->routeIs('patients.*') ? 'bg-white/20' : 'bg-medical-light/70' }}">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a6.062 6.062 0 0 1-1.037 1.584A11.944 11.944 0 0 1 12 21a11.955 11.955 0 0 1-3.002-.21M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z"/></svg>
                </span>
                {{ __('messages.nav.patients') }}
            </a>
            <a href="{{ route('appointments.index') }}" class="{{ request()->routeIs('appointments.*') ? $navActive : $navInactive }}">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg {{ request()->routeIs('appointments.*') ? 'bg-white/20' : 'bg-medical-light/70' }}">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5a2.25 2.25 0 0 0 2.25-2.25m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5a2.25 2.25 0 0 1 2.25 2.25v7.5"/></svg>
                </span>
                {{ __('messages.nav.appointments') }}
            </a>
            <a href="{{ route('calendar.index') }}" class="{{ request()->routeIs('calendar.*') ? $navActive : $navInactive }}">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg {{ request()->routeIs('calendar.*') ? 'bg-white/20' : 'bg-medical-light/70' }}">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5a2.25 2.25 0 0 0 2.25-2.25m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5a2.25 2.25 0 0 1 2.25 2.25v7.5m-13.5-3h4.5m-4.5 3h4.5m8.5-3H15m-3 3h1.5m-1.5 0h1.5m-6.75-6v.75m6-6v.75"/></svg>
                </span>
                {{ __('messages.nav.calendar') }}
            </a>
            @if(auth()->user()?->isAdmin())
                <a href="{{ route('settings.index') }}" class="{{ request()->routeIs('settings.*') ? $navActive : $navInactive }}">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg {{ request()->routeIs('settings.*') ? 'bg-white/20' : 'bg-medical-light/70' }}">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.292.24-.437.613-.43.992a6.932 6.932 0 0 1 0 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 0 1 0-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                    </span>
                    {{ __('messages.nav.settings') }}
                </a>
            @endif

        </nav>

        <div class="border-t border-teal-100/80 p-3">
            <div class="flex items-center gap-3 rounded-xl bg-medical-light/50 px-3 py-2.5">
                <x-user-avatar :user="auth()->user()" size="sm" class="ring-2 ring-medical-light" />
                <div class="min-w-0 flex-1">
                    <p class="truncate text-xs font-semibold text-ink">{{ auth()->user()->name }}</p>
                    @if(filled(auth()->user()->clinic_name))
                        <p class="truncate text-[10px] text-slate-500">{{ auth()->user()->clinic_name }}</p>
                    @endif
                    <p class="truncate text-[10px] font-medium text-medical">{{ auth()->user()->email }}</p>
                </div>
            </div>
            <form method="post" action="{{ route('logout') }}" class="mt-2">
                @csrf
                <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-xl border border-teal-100/90 py-2 text-xs font-semibold text-slate-600 transition-all duration-200 hover:bg-medical-light/50 hover:text-medical-dark active:scale-[0.98]">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M12 9h-8.25m0 0 3.75-3.75M3.75 12 12 12"/></svg>
                    {{ __('messages.nav.logout') }}
                </button>
            </form>
        </div>
    </aside>

    {{-- Main column --}}
    <div class="lg:pl-64">
        <header class="sticky top-0 z-30 border-b border-stone-200/60 bg-white/90 shadow-soft backdrop-blur-md">
            <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 sm:px-6 lg:px-8">
                <div class="flex min-w-0 flex-1 items-center gap-3">
                    <button
                        type="button"
                        class="inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white p-2 text-slate-600 transition-all duration-200 hover:bg-gray-50 active:scale-95 lg:hidden"
                        @click="sidebarOpen = true"
                        aria-label="Open menu"
                    >
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
                    </button>
                    <div class="min-w-0">
                        <h1 class="truncate text-lg font-semibold tracking-tight text-ink sm:text-xl">
                            @hasSection('heading')@yield('heading')@else@yield('title', config('app.name'))@endif
                        </h1>
                        @hasSection('subheading')
                            <p class="truncate text-sm text-slate-500">@yield('subheading')</p>
                        @endif
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-2 sm:gap-3">
                    <div class="flex items-center rounded-full border border-gray-200 bg-gray-50 p-0.5 shadow-sm">
                        <a href="{{ route('locale.switch', 'fr') }}" class="rounded-full px-3 py-1.5 text-xs font-semibold transition-all duration-200 active:scale-95 {{ app()->getLocale()==='fr' ? 'bg-white text-medical shadow-sm' : 'text-slate-600 hover:text-ink' }}">FR</a>
                        <a href="{{ route('locale.switch', 'en') }}" class="rounded-full px-3 py-1.5 text-xs font-semibold transition-all duration-200 active:scale-95 {{ app()->getLocale()==='en' ? 'bg-white text-medical shadow-sm' : 'text-slate-600 hover:text-ink' }}">EN</a>
                    </div>

                    <div class="relative" @click.outside="userMenu = false">
                        <button
                            type="button"
                            class="flex items-center gap-2 rounded-xl border border-gray-200 bg-white py-1 pl-1 pr-2 shadow-sm transition-all duration-200 hover:border-gray-300 hover:shadow-md active:scale-[0.98]"
                            @click="userMenu = !userMenu"
                        >
                            <x-user-avatar :user="auth()->user()" size="sm" class="ring-2 ring-medical-light" />
                            <svg class="hidden h-4 w-4 text-slate-400 sm:block" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                        </button>
                        <div
                            x-show="userMenu"
                            x-transition
                            class="absolute end-0 z-50 mt-2 w-52 overflow-hidden rounded-xl border border-gray-100 bg-white py-1 shadow-xl"
                            style="display: none;"
                            x-cloak
                        >
                            <a href="{{ route('profile.edit') }}" class="block px-4 py-2.5 text-sm font-medium text-slate-700 transition-colors hover:bg-gray-50">{{ __('messages.nav.profile') }}</a>
                            <form method="post" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="w-full px-4 py-2.5 text-start text-sm font-medium text-red-600 transition-colors hover:bg-red-50">{{ __('messages.nav.logout') }}</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <main class="px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
            @include('partials.flash')
            @yield('content')
        </main>
    </div>
</div>
@stack('scripts')
</body>
</html>
