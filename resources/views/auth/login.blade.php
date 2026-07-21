@extends('layouts.auth-split')

@section('title', __('messages.login.title'))

@section('content')
<div class="auth-card">
    <h1 class="auth-title">{{ __('messages.login.title') }}</h1>

    <form method="post" action="{{ route('login') }}" class="auth-form">
        @csrf
        <div class="auth-field">
            <label>{{ __('messages.login.email') }}</label>
            <input name="email" value="{{ old('email') }}" type="email" required autocomplete="username">
            @error('email')
                <p class="error">{{ $message }}</p>
            @enderror
        </div>
        <div class="auth-field">
            <label>{{ __('messages.login.password') }}</label>
            <input name="password" type="password" required autocomplete="current-password">
        </div>
        <label class="auth-remember">
            <input type="checkbox" name="remember" value="1">
            {{ __('messages.login.remember') }}
        </label>
        <button type="submit" class="auth-submit">{{ __('messages.login.submit') }}</button>
    </form>

    <p class="auth-footer">
        {{ __('messages.login.no_account') }}
        <a href="{{ route('register') }}">{{ __('messages.register.title') }}</a>
    </p>
</div>
@endsection
