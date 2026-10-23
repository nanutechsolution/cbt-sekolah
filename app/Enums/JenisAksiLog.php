<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum JenisAksiLog: string implements HasLabel
{
    case TambahWaktu = 'tambah_waktu';
    case BukaKunciDevice = 'buka_kunci_device';
    case ResetPeserta = 'reset_peserta';
    case SelesaikanManual = 'selesaikan_manual';
    case BuatAkun = 'buat_akun';
    case UbahAkun = 'ubah_akun';
    case AktifkanAkun = 'aktifkan_akun';
    case NonaktifkanAkun = 'nonaktifkan_akun';
    case ResetPassword = 'reset_password';
    case UbahProfil = 'ubah_profil';
    case ResetSecurityKey = 'reset_security_key';
    case UbahSecurityKey = 'ubah_security_key';

    public function getLabel(): string
    {
        return match ($this) {
            self::TambahWaktu => 'Tambah Waktu',
            self::BukaKunciDevice => 'Buka Kunci Device',
            self::ResetPeserta => 'Reset Peserta',
            self::SelesaikanManual => 'Selesaikan Manual',
            self::BuatAkun => 'Buat Akun',
            self::UbahAkun => 'Ubah Akun',
            self::AktifkanAkun => 'Aktifkan Akun',
            self::NonaktifkanAkun => 'Nonaktifkan Akun',
            self::ResetPassword => 'Reset Password',
            self::UbahProfil => 'Ubah Profil',
            self::ResetSecurityKey => 'Reset Security Key',
            self::UbahSecurityKey => 'Ubah Security Key',
        };
    }
}
