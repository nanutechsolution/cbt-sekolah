@extends('layouts.exam')

@section('title', 'Ruang Ujian')

@section('content')
    @livewire('exam-room', ['peserta' => $peserta])
@endsection
