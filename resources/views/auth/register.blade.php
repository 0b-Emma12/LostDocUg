@extends('layouts.app')

@section('title', 'Create account | LostDocUg')

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
            <h2 class="ul-auth-panel-headline">Start recovering documents privately.</h2>
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
                <p class="ul-account-eyebrow"><span class="ul-eyebrow-dot"></span> Secure account</p>
                <h1 class="ul-account-title">Create your account</h1>
                <p class="ul-account-sub">A verified email is required before reports or drafts are saved.</p>
                <form method="POST" action="{{ route('register.store') }}" class="ul-form-stack">
                    @csrf
                    <div class="ul-form-row">
                        <label class="ul-form-label" for="name">Name</label>
                        <input class="ul-form-input" id="name" name="name" value="{{ old('name') }}" autocomplete="name" required maxlength="120">
                        @error('name')<p class="ul-form-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="ul-form-row">
                        <label class="ul-form-label" for="email">Email address</label>
                        <input class="ul-form-input" id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required maxlength="255">
                        @error('email')<p class="ul-form-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="ul-form-row">
                        <label class="ul-form-label" for="password">Password</label>
                        <input class="ul-form-input" id="password" name="password" type="password" autocomplete="new-password" required>
                        <p class="ul-form-hint">Use at least 12 characters.</p>
                        @error('password')<p class="ul-form-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="ul-form-row">
                        <label class="ul-form-label" for="password_confirmation">Confirm password</label>
                        <input class="ul-form-input" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
                    </div>
                    <button class="button button-primary ul-form-submit" type="submit">Create account <span aria-hidden="true">&#8594;</span></button>
                </form>
                <p class="ul-auth-switch">Already registered? <a href="{{ route('login') }}">Sign in</a></p>
            </div>
        </div>
    </div>
</main>
@endsection
