@extends('layouts.app')

@section('title', __('messages.nav.profile'))
@section('heading', __('messages.nav.profile'))
@section('subheading', __('messages.profile.subtitle'))

@section('content')
<div class="mx-auto max-w-2xl">
    <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm ring-1 ring-slate-900/5">
        <div class="border-b border-gray-100 bg-gradient-to-br from-teal-50/80 via-white to-stone-50 px-6 py-8 sm:px-8">
            <div class="flex flex-col items-center gap-4 sm:flex-row sm:items-center">
                <div class="relative shrink-0">
                    @if($user->hasAvatar())
                        <img src="{{ $user->avatarUrl() }}" alt="" class="h-24 w-24 rounded-2xl object-cover shadow-lg ring-4 ring-white">
                    @else
                        <div class="flex h-24 w-24 items-center justify-center rounded-2xl bg-gradient-to-br from-teal-600 to-teal-900 text-3xl font-bold text-white shadow-lg ring-4 ring-white">
                            {{ strtoupper(mb_substr($user->name, 0, 1)) }}
                        </div>
                    @endif
                </div>
                <div class="text-center sm:text-start">
                    <p class="text-lg font-bold text-stone-900">{{ $user->name }}</p>
                    <p class="text-sm text-slate-500">{{ $user->email }}</p>
                    <span class="mt-2 inline-flex rounded-full bg-emerald-50 px-3 py-0.5 text-xs font-bold capitalize text-emerald-800 ring-1 ring-emerald-600/15">{{ $user->role }}</span>
                </div>
            </div>
        </div>

        <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="space-y-6 p-6 sm:p-8">
            @csrf
            @method('PUT')

            <div class="rounded-2xl border border-gray-100 bg-slate-50/80 p-5">
                <p class="mb-2 text-sm font-semibold text-stone-900">{{ __('messages.profile.avatar_section') }}</p>
                <p class="mb-4 text-xs text-slate-500">{{ __('messages.profile.avatar_hint') }}</p>
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                    <label class="inline-flex cursor-pointer items-center justify-center rounded-xl border-2 border-dashed border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-600 transition-all duration-200 hover:border-teal-400/50 hover:bg-teal-50/50 hover:text-teal-800">
                        <span>{{ __('messages.profile.avatar_choose') }}</span>
                        <input type="file" name="avatar" accept="image/jpeg,image/png,image/jpg,image/webp" class="sr-only">
                    </label>
                    @if($user->hasAvatar())
                        <label class="inline-flex cursor-pointer items-center gap-2 text-sm text-red-600 hover:text-red-700">
                            <input type="checkbox" name="remove_avatar" value="1" class="rounded border-slate-300 text-red-600 focus:ring-red-500">
                            {{ __('messages.profile.avatar_remove') }}
                        </label>
                    @endif
                </div>
                @error('avatar')
                    <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-semibold text-slate-700">{{ __('messages.register.name') }}</label>
                <input name="name" type="text" value="{{ old('name', $user->name) }}" required
                       class="w-full rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm shadow-sm transition-all duration-200 focus:border-medical focus:outline-none focus:ring-2 focus:ring-medical/20 @error('name') border-red-300 @enderror">
                @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-semibold text-slate-700">{{ __('messages.login.email') }}</label>
                <input name="email" type="email" value="{{ old('email', $user->email) }}" required autocomplete="email"
                       class="w-full rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm shadow-sm transition-all duration-200 focus:border-medical focus:outline-none focus:ring-2 focus:ring-medical/20 @error('email') border-red-300 @enderror">
                @error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="rounded-2xl border border-gray-100 bg-slate-50/80 p-5">
                <p class="mb-3 text-sm font-semibold text-stone-900">{{ __('messages.profile.password_section') }}</p>
                <div class="grid gap-4">
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold text-slate-600">{{ __('messages.profile.new_password') }}</label>
                        <input name="password" type="password" autocomplete="new-password"
                               class="w-full rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm shadow-sm focus:border-medical focus:outline-none focus:ring-2 focus:ring-medical/20 @error('password') border-red-300 @enderror"
                               placeholder="••••••••">
                        @error('password')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold text-slate-600">{{ __('messages.register.password_confirmation') }}</label>
                        <input name="password_confirmation" type="password" autocomplete="new-password"
                               class="w-full rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm shadow-sm focus:border-medical focus:outline-none focus:ring-2 focus:ring-medical/20">
                    </div>
                </div>
                <p class="mt-3 text-xs text-slate-500">{{ __('messages.profile.password_hint') }}</p>
            </div>

            <div class="flex flex-wrap gap-3 border-t border-gray-100 pt-6">
                <button type="submit" class="rounded-xl bg-medical px-6 py-2.5 text-sm font-bold text-white shadow-lg shadow-medical/25 transition-all duration-200 hover:bg-medical-dark active:scale-[0.98]">
                    {{ __('messages.profile.save') }}
                </button>
                <a href="{{ route('dashboard') }}" class="rounded-xl border border-gray-200 bg-white px-6 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition-all duration-200 hover:bg-gray-50 active:scale-[0.98]">
                    {{ __('messages.cancel') }}
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
