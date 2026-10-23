@extends('errors.layout')

@section('title', 'Akses Ditolak — ' . config('app.name'))
@section('code', '403')
@section('heading', 'Akses Ditolak')
@section('message', 'Anda tidak memiliki izin untuk membuka halaman ini.')

@section('actions')
    <a class="error-button" href="/">Kembali ke Halaman Utama</a>
@endsection
