<?php

namespace App\Filament\Resources\PaketSoals\Schemas;

use App\Enums\UserRole;
use App\Models\GuruMengajar;
use App\Models\Mapel;
use App\Models\PaketSoal;
use App\Models\TahunAjaran;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class PaketSoalForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identitas Paket Soal')
                    ->schema([
                        TextInput::make('kode')
                            ->label('Kode Paket')
                            ->placeholder('PS-MTK-UTS-01')
                            ->helperText('Kode disimpan dalam huruf besar.')
                            ->required()
                            ->maxLength(50)
                            ->trim()
                            ->rule('alpha_dash')
                            ->unique(ignoreRecord: true)
                            ->validationMessages([
                                'alpha_dash' => 'Kode hanya boleh berisi huruf, angka, tanda hubung, dan garis bawah.',
                                'unique' => 'Kode paket soal sudah ada.',
                            ]),
                        TextInput::make('nama')
                            ->label('Nama Paket Soal')
                            ->placeholder('UTS Matematika Kelas X')
                            ->required()
                            ->maxLength(150)
                            ->trim(),
                        Select::make('mapel_id')
                            ->label('Mata Pelajaran')
                            ->options(fn (): array => self::mapelOptions())
                            ->searchable()
                            ->required()
                            ->native(false)
                            ->disabled(fn (?PaketSoal $record): bool => $record?->isTerkunci() ?? false),
                        Select::make('jenis_ujian_id')
                            ->label('Jenis Ujian')
                            ->relationship('jenisUjian', 'nama')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->disabled(fn (?PaketSoal $record): bool => $record?->isTerkunci() ?? false),
                        Select::make('tahun_ajaran_id')
                            ->label('Tahun Ajaran')
                            ->relationship('tahunAjaran', 'nama')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->default(fn (): ?int => TahunAjaran::query()->where('is_aktif', true)->value('id'))
                            ->disabled(fn (?PaketSoal $record): bool => $record?->isTerkunci() ?? false),
                        TextInput::make('tingkat')
                            ->label('Tingkat')
                            ->helperText('Tingkat kelas yang mengikuti ujian, 1 sampai 12.')
                            ->required()
                            ->numeric()
                            ->integer()
                            ->minValue(1)
                            ->maxValue(12)
                            ->disabled(fn (?PaketSoal $record): bool => $record?->isTerkunci() ?? false),
                    ])
                    ->columns(2),
                Section::make('Pengaturan Ujian')
                    ->schema([
                        Toggle::make('acak_soal')
                            ->label('Acak Urutan Soal')
                            ->default(true)
                            ->disabled(fn (?PaketSoal $record): bool => $record?->isTerkunci() ?? false),
                        Toggle::make('acak_opsi')
                            ->label('Acak Urutan Opsi')
                            ->default(true)
                            ->disabled(fn (?PaketSoal $record): bool => $record?->isTerkunci() ?? false),
                        Toggle::make('tampilkan_nilai')
                            ->label('Tampilkan Nilai ke Siswa')
                            ->default(true)
                            ->disabled(fn (?PaketSoal $record): bool => $record?->isTerkunci() ?? false),
                    ])
                    ->columns(3),
            ]);
    }

    /**
     * @return array<int, string>
     */
    private static function mapelOptions(): array
    {
        $user = Auth::user();

        if (! $user || $user->role === UserRole::Admin) {
            return Mapel::query()->orderBy('nama')->pluck('nama', 'id')->all();
        }

        $guruId = $user->guru?->id;

        if (! $guruId) {
            return [];
        }

        return GuruMengajar::query()
            ->where('guru_id', $guruId)
            ->with('mapel:id,nama')
            ->get()
            ->pluck('mapel')
            ->filter()
            ->unique('id')
            ->sortBy('nama')
            ->pluck('nama', 'id')
            ->all();
    }
}
