@extends('errors.layout')

@section('title', 'Layanan Sedang Pemeliharaan — ' . config('app.name'))
@section('code', '503')
@section('heading', 'Layanan Sedang Pemeliharaan')
@section('message', 'Layanan ujian sementara tidak tersedia karena pemeliharaan. Silakan coba lagi beberapa saat lagi.')

@section('actions')
    <button type="button" class="error-button" onclick="window.location.reload()">Coba Lagi</button>
    <a class="error-button secondary" href="/">Kembali ke Halaman Utama</a>
@endsection
