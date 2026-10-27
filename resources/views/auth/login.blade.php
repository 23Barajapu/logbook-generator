@extends('layouts.app')

@section('title', 'Masuk Sistem')

@section('content')
<div class="auth-page">
    <div class="auth-card">
        <div class="auth-logo">
            <div class="auth-logo-mark" aria-hidden="true">L</div>
            <h1 class="auth-title">Logbook Generator</h1>
            <p class="auth-subtitle">Manajemen Aktivitas, Approval & Generator Logbook</p>
        </div>

        <form action="{{ route('login.post') }}" method="POST" novalidate>
            @csrf
            <div class="form-group">
                <label for="email" class="form-label">Alamat Email</label>
                <input type="email" id="email" name="email"
                       class="form-control"
                       value="{{ old('email') }}"
                       required autofocus autocomplete="username"
                       placeholder="nama@institusi.ac.id"
                       aria-describedby="{{ $errors->has('email') ? 'email-error' : '' }}">
                @error('email')
                    <span class="form-error" id="email-error" role="alert">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <input type="password" id="password" name="password"
                       class="form-control"
                       required autocomplete="current-password"
                       placeholder="Masukkan password">
                @error('password')
                    <span class="form-error" role="alert">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group" style="display:flex;align-items:center;justify-content:space-between;">
                <label style="display:flex;align-items:center;gap:8px;font-size:13px;color:var(--text-secondary);cursor:pointer;">
                    <input type="checkbox" name="remember" value="1">
                    Ingat sesi saya
                </label>
            </div>

            <button type="submit" id="btn-login" class="btn btn-primary btn-lg btn-block">
                Masuk ke Sistem
            </button>
        </form>

        <div class="auth-footer-link">
            Belum punya akun? <a href="{{ route('register') }}">Daftar sebagai Mahasiswa Baru</a>
        </div>
    </div>
</div>
@endsection
