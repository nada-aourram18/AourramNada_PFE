@extends('layouts.auth-split')

@section('title', __('messages.register.title'))

@section('content')
<div class="auth-card">
    <h1 class="auth-title">{{ __('messages.register.title') }}</h1>

    <form method="post" action="{{ route('register') }}" class="auth-form">
        @csrf
        <div class="auth-field">
            <label>{{ __('messages.register.name') }}</label>
            <input name="name" value="{{ old('name') }}" type="text" required autocomplete="name">
            @error('name')<p class="error">{{ $message }}</p>@enderror
        </div>
        <div class="auth-field">
            <label>{{ __('messages.register.phone') }}</label>
            <input name="phone" value="{{ old('phone') }}" type="tel" required autocomplete="tel">
            @error('phone')<p class="error">{{ $message }}</p>@enderror
        </div>
        <div class="auth-field">
            <label>{{ __('messages.register.clinic_name') }}</label>
            <input name="clinic_name" value="{{ old('clinic_name') }}" type="text" required autocomplete="organization">
            @error('clinic_name')<p class="error">{{ $message }}</p>@enderror
        </div>
        <div class="auth-field">
            <label>{{ __('messages.register.specialty') }}</label>
            <input name="specialty" value="{{ old('specialty') }}" type="text" required autocomplete="organization-title" placeholder="{{ __('messages.register.specialty_placeholder') }}">
            @error('specialty')<p class="error">{{ $message }}</p>@enderror
        </div>
        <div class="auth-field">
            <label>{{ __('messages.login.email') }}</label>
            <input name="email" value="{{ old('email') }}" type="email" required autocomplete="email">
            @error('email')<p class="error">{{ $message }}</p>@enderror
        </div>
        <div class="auth-field">
            <label>{{ __('messages.login.password') }}</label>
            <input name="password" type="password" required autocomplete="new-password">
            @error('password')<p class="error">{{ $message }}</p>@enderror
        </div>
        <div class="auth-field">
            <label>{{ __('messages.register.password_confirmation') }}</label>
            <input name="password_confirmation" type="password" required autocomplete="new-password">
        </div>
        <button type="submit" class="auth-submit">{{ __('messages.register.submit') }}</button>
    </form>

    <p class="auth-footer">
        {{ __('messages.register.already_account') }}
        <a href="{{ route('login') }}">{{ __('messages.login.title') }}</a>
    </p>
</div>
@endsection
