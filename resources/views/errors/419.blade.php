@extends('errors.layout')

@section('title', 'Sesi Anda Sudah Berakhir — ' . config('app.name'))
@section('code', '419')
@section('heading', 'Sesi Anda Sudah Berakhir')
@section('message', 'Halaman ini kedaluwarsa karena sesi Anda telah berakhir. Muat ulang halaman, lalu masuk lagi menggunakan username dan security key. Jangan khawatir, jawaban yang sudah tersimpan tetap aman.')

@section('actions')
    <button type="button" class="error-button" onclick="window.location.reload()">Muat Ulang Halaman</button>
    <a class="error-button secondary" href="/ujian">Masuk Ujian</a>
@endsection
