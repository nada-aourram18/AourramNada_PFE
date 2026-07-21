@extends('layouts.app')

@section('title', __('messages.nav.settings'))
@section('heading', __('messages.nav.settings'))
@section('subheading', 'Intégrations, profil administrateur et équipe')

@section('content')
<div x-data="{ tab: 'integrations', showOpenAI: false, showAirtable: false }" class="space-y-6">
    <div class="inline-flex rounded-2xl border border-gray-100 bg-white p-1 shadow-sm">
        <button
            type="button"
            @click="tab = 'integrations'"
            :class="tab === 'integrations' ? 'bg-medical text-white shadow-md' : 'text-slate-600 hover:bg-gray-50'"
            class="rounded-xl px-4 py-2 text-sm font-semibold transition-all duration-200 active:scale-[0.98]"
        >Intégrations</button>
        <button
            type="button"
            @click="tab = 'profile'"
            :class="tab === 'profile' ? 'bg-medical text-white shadow-md' : 'text-slate-600 hover:bg-gray-50'"
            class="rounded-xl px-4 py-2 text-sm font-semibold transition-all duration-200 active:scale-[0.98]"
        >Profil</button>
        <button
            type="button"
            @click="tab = 'users'"
            :class="tab === 'users' ? 'bg-medical text-white shadow-md' : 'text-slate-600 hover:bg-gray-50'"
            class="rounded-xl px-4 py-2 text-sm font-semibold transition-all duration-200 active:scale-[0.98]"
        >Utilisateurs</button>
    </div>

    <form method="post" action="{{ route('settings.update') }}" class="space-y-6">
        @csrf

        <div x-show="tab === 'integrations'" x-transition class="space-y-6">
            <section class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-stone-900">Intégrations API</h2>
                <p class="mt-1 text-sm text-slate-500">Connexions sécurisées vers n8n, OpenAI, Google Calendar et Airtable.</p>
                <div class="mt-6 grid gap-5 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Webhook n8n</label>
                        <input name="n8n_webhook_url" value="{{ old('n8n_webhook_url', $n8n_webhook_url) }}" class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm transition-all duration-200 focus:border-medical focus:outline-none focus:ring-2 focus:ring-medical/20">
                        @error('n8n_webhook_url')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">OpenAI API Key</label>
                        <div class="relative">
                            <input
                                name="openai_api_key"
                                :type="showOpenAI ? 'text' : 'password'"
                                autocomplete="new-password"
                                placeholder="{{ $openai_api_key_masked ? '••••'.$openai_api_key_masked : '' }}"
                                class="w-full rounded-xl border border-gray-200 py-2.5 pe-12 ps-4 font-mono text-sm focus:border-medical focus:outline-none focus:ring-2 focus:ring-medical/20"
                            >
                            <button type="button" @click="showOpenAI = !showOpenAI" class="absolute end-2 top-1/2 -translate-y-1/2 rounded-lg p-2 text-slate-400 hover:bg-gray-100 hover:text-slate-700">
                                <svg x-show="!showOpenAI" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                                <svg x-show="showOpenAI" x-cloak class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88"/></svg>
                            </button>
                        </div>
                        @if($openai_api_key_masked)<p class="mt-1 text-xs text-slate-500">Valeur actuelle : {{ $openai_api_key_masked }}</p>@endif
                        @error('openai_api_key')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Google Calendar ID</label>
                        <input name="google_calendar_id" value="{{ old('google_calendar_id', $google_calendar_id) }}" class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-medical focus:outline-none focus:ring-2 focus:ring-medical/20">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Airtable Base ID</label>
                        <input name="airtable_base_id" value="{{ old('airtable_base_id', $airtable_base_id) }}" class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-medical focus:ring-2 focus:ring-medical/20">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Airtable API Key</label>
                        <div class="relative">
                            <input
                                name="airtable_api_key"
                                :type="showAirtable ? 'text' : 'password'"
                                autocomplete="new-password"
                                placeholder="{{ $airtable_api_key_masked ? '••••' : '' }}"
                                class="w-full rounded-xl border border-gray-200 py-2.5 pe-12 ps-4 font-mono text-sm focus:border-medical focus:ring-2 focus:ring-medical/20"
                            >
                            <button type="button" @click="showAirtable = !showAirtable" class="absolute end-2 top-1/2 -translate-y-1/2 rounded-lg p-2 text-slate-400 hover:bg-gray-100">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
                            </button>
                        </div>
                        @if($airtable_api_key_masked)<p class="mt-1 text-xs text-slate-500">Masquée : {{ $airtable_api_key_masked }}</p>@endif
                    </div>
                </div>
            </section>
        </div>

        <div x-show="tab === 'profile'" x-transition>
            <section class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-stone-900">Profil administrateur</h2>
                <div class="mt-6 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase text-slate-500">Nom</label>
                        <input name="profile_name" value="{{ old('profile_name', auth()->user()->name) }}" class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-medical focus:ring-2 focus:ring-medical/20">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase text-slate-500">Email</label>
                        <input name="profile_email" type="email" value="{{ old('profile_email', auth()->user()->email) }}" class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-medical focus:ring-2 focus:ring-medical/20">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase text-slate-500">Nouveau mot de passe</label>
                        <input name="profile_password" type="password" class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-medical focus:ring-2 focus:ring-medical/20">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase text-slate-500">Confirmation</label>
                        <input name="profile_password_confirmation" type="password" class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-medical focus:ring-2 focus:ring-medical/20">
                    </div>
                </div>
            </section>
        </div>

        <div class="flex flex-wrap gap-3" x-show="tab !== 'users'">
            <button type="submit" class="rounded-xl bg-medical px-6 py-2.5 text-sm font-bold text-white shadow-lg shadow-medical/25 transition-all duration-200 hover:bg-medical-dark active:scale-[0.98]">{{ __('messages.save') }}</button>
        </div>
    </form>

    <div x-show="tab === 'users'" x-transition class="grid gap-6 lg:grid-cols-2">
        <section class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-stone-900">Créer un réceptionniste</h2>
            <form method="post" action="{{ route('settings.users.store') }}" class="mt-4 space-y-3">
                @csrf
                <input name="name" value="{{ old('name') }}" required placeholder="Nom" class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-medical focus:ring-2 focus:ring-medical/20">
                @error('name')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                <input name="email" type="email" value="{{ old('email') }}" required placeholder="Email" class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-medical focus:ring-2 focus:ring-medical/20">
                @error('email')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                <input name="password" type="password" required placeholder="Mot de passe" class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-medical focus:ring-2 focus:ring-medical/20">
                <input name="password_confirmation" type="password" required placeholder="Confirmation" class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-medical focus:ring-2 focus:ring-medical/20">
                @error('password')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                <button type="submit" class="w-full rounded-xl bg-stone-800 py-3 text-sm font-bold text-white transition-all duration-200 hover:bg-stone-900 active:scale-[0.99]">Créer le compte</button>
            </form>
        </section>
        <section class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-stone-900">Comptes existants</h2>
            <ul class="mt-4 divide-y divide-gray-100">
                @foreach($users as $u)
                    <li class="flex items-center justify-between gap-3 py-3 transition-colors duration-200 hover:bg-gray-50/80">
                        <div class="min-w-0">
                            <div class="truncate font-semibold text-slate-900">{{ $u->name }}</div>
                            <div class="truncate text-xs text-slate-500">{{ $u->email }}</div>
                        </div>
                        <form method="post" action="{{ route('settings.users.destroy', $u) }}" onsubmit="return confirm('Supprimer ?');">
                            @csrf @method('DELETE')
                            <button type="submit" class="rounded-lg px-3 py-1.5 text-xs font-semibold text-red-600 transition-all duration-200 hover:bg-red-50 active:scale-95">{{ __('messages.delete') }}</button>
                        </form>
                    </li>
                @endforeach
            </ul>
        </section>
    </div>
</div>
@endsection
