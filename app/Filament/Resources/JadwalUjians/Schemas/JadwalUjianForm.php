<?php

namespace App\Filament\Resources\JadwalUjians\Schemas;

use App\Enums\StatusPaketSoal;
use App\Enums\UserRole;
use App\Models\GuruMengajar;
use App\Models\Kelas;
use App\Models\PaketSoal;
use Closure;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class JadwalUjianForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Jadwal Ujian')
                    ->schema([
                        Select::make('paket_soal_id')
                            ->label('Paket Soal')
                            ->options(fn (): array => self::paketOptions())
                            ->searchable()
                            ->required()
                            ->live()
                            ->native(false),
                        TextInput::make('nama')
                            ->label('Nama Jadwal')
                            ->placeholder('UTS Matematika Kelas X - Sesi 1')
                            ->required()
                            ->maxLength(150)
                            ->trim(),
                        DateTimePicker::make('mulai')
                            ->label('Mulai')
                            ->native(false)
                            ->displayFormat('d/m/Y H:i')
                            ->seconds(false)
                            ->minutesStep(5)
                            ->locale('id')
                            ->required()
                            ->rules(
                                [self::validasiMulaiTidakDiMasaLalu()],
                                condition: fn (string $operation): bool => $operation === 'create',
                            )
                            ->helperText('Awal jendela ujian. Siswa belum bisa masuk sebelum waktu ini.'),
                        DateTimePicker::make('selesai')
                            ->label('Selesai')
                            ->native(false)
                            ->displayFormat('d/m/Y H:i')
                            ->seconds(false)
                            ->minutesStep(5)
                            ->locale('id')
                            ->required()
                            ->rules([self::validasiSelesai()])
                            ->helperText('Akhir jendela ujian. Jadwal ditutup pada waktu ini, berapa pun sisa durasi siswa.'),
                        TextInput::make('durasi_menit')
                            ->label('Durasi (menit)')
                            ->numeric()
                            ->integer()
                            ->minValue(1)
                            ->maxValue(600)
                            ->default(60)
                            ->required()
                            ->helperText('Lama pengerjaan per siswa. Jendela ujian (Selesai − Mulai) minimal sepanjang durasi ini.'),
                        Select::make('kelas')
                            ->label('Kelas Peserta')
                            ->multiple()
                            ->options(fn (Get $get): array => self::kelasOptions($get))
                            ->searchable()
                            ->required()
                            ->helperText('Kelas harus sesuai tingkat paket soal.'),
                    ])
                    ->columns(2),
            ]);
    }

    /**
     * Reject a start time in the past when a new schedule is created.
     * Editing an existing schedule keeps its historical start time.
     */
    private static function validasiMulaiTidakDiMasaLalu(): Closure
    {
        return function (): Closure {
            return function (string $attribute, mixed $value, Closure $fail): void {
                $mulai = self::parseWaktu($value);

                if ($mulai === null) {
                    return;
                }

                if ($mulai->lessThan(now()->startOfMinute())) {
                    $fail('Waktu mulai tidak boleh di masa lalu saat membuat jadwal baru.');
                }
            };
        };
    }

    /**
     * The end of the window must be after the start and long enough for the duration.
     */
    private static function validasiSelesai(): Closure
    {
        return function (Get $get): Closure {
            return function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                $mulai = self::parseWaktu($get('mulai'));
                $selesai = self::parseWaktu($value);

                if (($mulai === null) || ($selesai === null)) {
                    return;
                }

                if ($selesai->lessThanOrEqualTo($mulai)) {
                    $fail('Waktu selesai harus setelah waktu mulai.');

                    return;
                }

                $durasi = (int) $get('durasi_menit');

                if ($durasi < 1) {
                    return;
                }

                $menitJendela = intdiv($selesai->getTimestamp() - $mulai->getTimestamp(), 60);

                if ($menitJendela < $durasi) {
                    $fail("Jendela ujian hanya {$menitJendela} menit, lebih pendek dari durasi {$durasi} menit. Perpanjang waktu selesai atau kurangi durasi.");
                }
            };
        };
    }

    private static function parseWaktu(mixed $value): ?Carbon
    {
        if (blank($value)) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return array<int, string>
     */
    private static function paketOptions(): array
    {
        $user = Auth::user();

        if (! $user) {
            return [];
        }

        $query = PaketSoal::query()
            ->where('status', StatusPaketSoal::Siap->value)
            ->with(['mapel:id,nama'])
            ->orderBy('nama');

        if ($user->role !== UserRole::Admin) {
            $query->tampilUntukUser($user);
        }

        return $query->get()
            ->mapWithKeys(fn (PaketSoal $paket): array => [
                $paket->id => $paket->nama
                    .' — '.($paket->mapel?->nama ?? '-')
                    .' (Tingkat '.$paket->tingkat.')',
            ])
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private static function kelasOptions(Get $get): array
    {
        $paketId = $get('paket_soal_id');

        if (blank($paketId)) {
            return [];
        }

        $paket = PaketSoal::query()->find($paketId);

        if ($paket === null) {
            return [];
        }

        $user = Auth::user();
        $query = Kelas::query()
            ->where('tingkat', $paket->tingkat)
            ->orderBy('nama');

        if ($user && $user->role !== UserRole::Admin) {
            $guruId = $user->guru?->id;

            if (! $guruId) {
                return [];
            }

            $query->whereIn('id', GuruMengajar::query()
                ->where('guru_id', $guruId)
                ->where('mapel_id', $paket->mapel_id)
                ->where('tahun_ajaran_id', $paket->tahun_ajaran_id)
                ->select('kelas_id'));
        }

        return $query->pluck('nama', 'id')->all();
    }
}
