@extends('layouts.app')

@section('title', 'Verify your email | LostDocUg')

@section('content')
<main class="ul-account-page">
    <div class="wrap">
        <div class="ul-auth-card">
            <p class="ul-account-eyebrow"><span class="ul-eyebrow-dot"></span> One more step</p>
            <h1 class="ul-account-title">Check your inbox</h1>
            <p class="ul-account-sub">We sent a verification link to your email address. Verify it before creating reports or drafts.</p>
            @if (session('status') === 'verification-link-sent')
                <p class="ul-inline-alert success" role="status">A fresh verification link has been sent.</p>
            @endif
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button class="button button-primary ul-form-submit" type="submit">Resend verification link</button>
            </form>
        </div>
    </div>
</main>
@endsection
