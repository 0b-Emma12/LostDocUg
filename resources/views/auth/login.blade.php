@extends('layouts.app')

@section('title', 'Sign in | LostDocUg')

@section('content')
<main class="ul-auth-page">
    <div class="ul-auth-page-inner">
        {{-- Left Panel --}}
        <div class="ul-auth-panel-left">
            <div class="ul-auth-panel-brand">
                <span class="ul-brand-mark">LD</span>
                <div>
                    <strong class="ul-brand-name">LostDocUg</strong>
                    <span class="ul-brand-tagline">Private Document Recovery</span>
                </div>
            </div>
            <h2 class="ul-auth-panel-headline">Recover documents privately. <br>Keep your data safe.</h2>
            <ul class="ul-auth-panel-perks">
                <li><span class="ul-auth-perk-icon">🔒</span> End-to-end encrypted reports</li>
                <li><span class="ul-auth-perk-icon">🔍</span> Private matching — no public search</li>
                <li><span class="ul-auth-perk-icon">👁️</span> Human review before any recovery</li>
                <li><span class="ul-auth-perk-icon">🇺🇬</span> Covers all 135+ Uganda districts</li>
            </ul>
        </div>

        {{-- Right Panel (form) --}}
        <div class="ul-auth-panel-right">
            <div class="ul-auth-card">
                <p class="ul-account-eyebrow"><span class="ul-eyebrow-dot"></span> Secure sign in</p>
                <h1 class="ul-account-title">Welcome back</h1>
                <p class="ul-account-sub">Sign in to manage your private reports and drafts.</p>
                <form method="POST" action="{{ route('login.store') }}" class="ul-form-stack">
                    @csrf
                    <div class="ul-form-row">
                        <label class="ul-form-label" for="email">Email address</label>
                        <input class="ul-form-input" id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required autofocus>
                        @error('email')<p class="ul-form-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="ul-form-row">
                        <label class="ul-form-label" for="password">Password</label>
                        <input class="ul-form-input" id="password" name="password" type="password" autocomplete="current-password" required>
                        @error('password')<p class="ul-form-error">{{ $message }}</p>@enderror
                    </div>
                    <label class="ul-form-check"><input type="checkbox" name="remember" value="1"><span>Remember this device</span></label>
                    <button class="button button-primary ul-form-submit" type="submit">Sign in <span aria-hidden="true">&#8594;</span></button>
                </form>
                <p class="ul-auth-switch">New to LostDocUg? <a href="{{ route('register') }}">Create an account</a></p>
            </div>
        </div>
    </div>
</main>
@endsection
