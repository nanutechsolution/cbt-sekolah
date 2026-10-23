@extends('errors.layout')

@section('title', 'Halaman Tidak Ditemukan — ' . config('app.name'))
@section('code', '404')
@section('heading', 'Halaman Tidak Ditemukan')
@section('message', 'Alamat yang Anda tuju tidak tersedia atau sudah dipindahkan.')

@section('actions')
    <a class="error-button" href="/">Kembali ke Halaman Utama</a>
    <a class="error-button secondary" href="/ujian">Masuk Ujian</a>
@endsection
