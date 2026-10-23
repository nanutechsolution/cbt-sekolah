@extends('layouts.exam')

@section('title', 'Ujian Ditangguhkan')

@section('content')
    <div style="min-height:100vh; display:grid; place-items:center; padding:24px;">
        <div style="width:min(520px,100%); background:#fff; border:1px solid #e2e8f0; border-radius:16px; padding:28px; text-align:center; box-shadow:0 18px 50px rgba(15,23,42,.12);">
            <div style="font-size:52px; line-height:1; margin-bottom:10px;">⏸</div>
            <h1 style="margin:0 0 8px;">Ujian Ditangguhkan</h1>
            <p style="color:#64748b; margin:0 0 18px;">
                Ujian Anda dihentikan karena melebihi batas pelanggaran.
                Hubungi pengawas atau guru untuk melanjutkan.
            </p>
            <div style="background:#fef3c7; color:#78350f; border:1px solid #fde68a; border-radius:10px; padding:12px; text-align:left; font-size:13px; margin-bottom:16px;">
                Pelanggaran tercatat: <strong>{{ $peserta->jumlah_pelanggaran }}</strong>
            </div>
            <a href="{{ route('siswa.dashboard') }}" style="display:block; background:#0f766e; color:#fff; text-decoration:none; padding:11px; border-radius:9px; font-weight:800; font-size:14px;">
                Kembali ke Dashboard
            </a>
        </div>
    </div>
@endsection
