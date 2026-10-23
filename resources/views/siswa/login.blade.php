@extends('layouts.siswa')

@section('title', 'Masuk Ujian')

@section('styles')
    <style>
        .auth-shell { min-height: calc(100vh - 140px); }
        .auth-card { box-shadow: 0 24px 60px rgba(15,23,42,.14); }
    </style>
@endsection

@section('content')
    <div class="auth-shell">
        <div class="auth-card card">
            <div class="card-body">
                <div class="auth-head">
                    <div class="brand-mark">CBT</div>
                    <h1>Masuk Ruang Ujian</h1>
                    <p>Gunakan akun peserta yang diberikan sekolah.</p>
                </div>

                @if ($errors->any())
                    <div class="alert danger">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('siswa.login.store') }}" class="form-grid">
                    @csrf
                    <div class="field">
                        <label for="username">Username</label>
                        <input id="username" name="username" type="text" inputmode="text" autocomplete="username"
                               value="{{ old('username') }}" placeholder="contoh: 2026001" required autofocus>
                    </div>
                    <div class="field">
                        <label for="security_key">Security Key</label>
                        <input id="security_key" name="security_key" type="password" autocomplete="current-password"
                               placeholder="Masukkan security key" required>
                        <p class="muted small" style="margin: 7px 0 0;">Security key ada pada kartu peserta. Jangan bagikan kepada orang lain.</p>
                    </div>
                    <button type="submit" class="btn block">Masuk</button>
                </form>

                <p class="muted small" style="text-align:center; margin: 20px 0 0;">
                    Hubungi pengawas jika lupa username atau security key.
                </p>
            </div>
        </div>
    </div>
@endsection
