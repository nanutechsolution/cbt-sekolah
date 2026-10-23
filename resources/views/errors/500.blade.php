@extends('errors.layout')

@section('title', 'Terjadi Kesalahan — ' . config('app.name'))
@section('code', '500')
@section('heading', 'Terjadi Kesalahan')
@section('message', 'Sistem mengalami kendala saat memproses permintaan Anda. Silakan coba beberapa saat lagi. Jika masalah berlanjut, hubungi pengawas atau administrator.')

@section('actions')
    <button type="button" class="error-button" onclick="window.location.reload()">Muat Ulang Halaman</button>
    <a class="error-button secondary" href="/">Kembali ke Halaman Utama</a>
@endsection
