<?php

namespace App\Filament\Resources\PaketSoals\RelationManagers;

use App\Enums\TipeSoal;
use App\Models\PaketSoal;
use App\Services\PaketSoalLockService;
use App\Services\SoalWordImportService;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Unique;
use Illuminate\Validation\ValidationException;
use Throwable;

class SoalRelationManager extends RelationManager
{
    protected static string $relationship = 'soal';

    protected static ?string $title = 'Daftar Soal';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nomor')
                    ->label('Nomor Soal')
                    ->numeric()
                    ->integer()
                    ->minValue(1)
                    ->nullable()
                    ->helperText('Kosongkan untuk nomor otomatis.')
                    ->unique(
                        ignoreRecord: true,
                        modifyRuleUsing: fn (Unique $rule): Unique => $rule->where('paket_soal_id', $this->getOwnerRecord()->getKey()),
                    )
                    ->hiddenOn('create'),
                Select::make('tipe')
                    ->label('Tipe Soal')
                    ->options(TipeSoal::initialOptions())
                    ->default(TipeSoal::Pg->value)
                    ->live()
                    ->required()
                    ->native(false)
                    ->disabledOn('edit'),
                Textarea::make('pertanyaan')
                    ->label('Pertanyaan')
                    ->required()
                    ->rows(5)
                    ->columnSpanFull(),
                FileUpload::make('gambar')
                    ->label('Gambar Soal')
                    ->image()
                    ->disk('public')
                    ->directory('soal')
                    ->visibility('public')
                    ->maxSize(2048)
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->columnSpanFull(),
                FileUpload::make('audio')
                    ->label('Audio Soal')
                    ->disk('public')
                    ->directory('soal/audio')
                    ->visibility('public')
                    ->maxSize(10240)
                    ->acceptedFileTypes(['audio/mpeg', 'audio/wav', 'audio/ogg', 'audio/webm'])
                    ->helperText('Opsional. Format MP3, WAV, OGG, atau WebM. Maksimal 10 MB.')
                    ->columnSpanFull(),
                TextInput::make('audio_maks_putar')
                    ->label('Maksimal Putar Audio')
                    ->numeric()
                    ->integer()
                    ->minValue(1)
                    ->nullable()
                    ->helperText('Kosongkan jika tidak dibatasi.'),
                TextInput::make('bobot')
                    ->label('Bobot Nilai')
                    ->numeric()
                    ->step(0.01)
                    ->minValue(0.01)
                    ->maxValue(100)
                    ->default(1)
                    ->required(),
                Textarea::make('pembahasan')
                    ->label('Pembahasan')
                    ->rows(3)
                    ->columnSpanFull(),
                Repeater::make('opsi')
                    ->label('Opsi Jawaban')
                    ->relationship()
                    ->visible(fn (Get $get): bool => (TipeSoal::tryFrom((string) $get('tipe'))?->usesOptions() ?? false))
                    ->default([
                        ['label' => 'A', 'teks' => '', 'is_benar' => false, 'urutan' => 1],
                        ['label' => 'B', 'teks' => '', 'is_benar' => false, 'urutan' => 2],
                        ['label' => 'C', 'teks' => '', 'is_benar' => false, 'urutan' => 3],
                        ['label' => 'D', 'teks' => '', 'is_benar' => false, 'urutan' => 4],
                    ])
                    ->schema([
                        TextInput::make('label')
                            ->label('Label')
                            ->required()
                            ->maxLength(5),
                        Textarea::make('teks')
                            ->label(fn (Get $get): string => match (TipeSoal::tryFrom((string) $get('../../tipe'))) {
                                TipeSoal::IsianSingkat => 'Jawaban Diterima',
                                TipeSoal::Menjodohkan, TipeSoal::BenarSalahPernyataan => 'Pernyataan',
                                default => 'Teks Opsi',
                            })
                            ->required()
                            ->rows(2)
                            ->columnSpan(5),
                        Checkbox::make('is_benar')
                            ->label(fn (Get $get): string => TipeSoal::tryFrom((string) $get('../../tipe')) === TipeSoal::BenarSalahPernyataan
                                ? 'Kunci: Benar (kosongkan untuk Salah)'
                                : 'Kunci Jawaban')
                            ->visible(fn (Get $get): bool => ! in_array(TipeSoal::tryFrom((string) $get('../../tipe')), [TipeSoal::Menjodohkan, TipeSoal::Urutan], true))
                            ->columnSpan(2),
                        TextInput::make('pasangan')
                            ->label('Pasangan Benar')
                            ->required()
                            ->visible(fn (Get $get): bool => TipeSoal::tryFrom((string) $get('../../tipe')) === TipeSoal::Menjodohkan)
                            ->columnSpan(3),
                        FileUpload::make('gambar')
                            ->label('Gambar')
                            ->image()
                            ->disk('public')
                            ->directory('opsi-soal')
                            ->visibility('public')
                            ->maxSize(2048)
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->columnSpan(4),
                        TextInput::make('urutan')
                            ->label('Urutan')
                            ->numeric()
                            ->integer()
                            ->hidden(),
                    ])
                    ->columns(12)
                    ->minItems(1)
                    ->maxItems(10)
                    ->defaultItems(4)
                    ->reorderable()
                    ->orderColumn('urutan')
                    ->addActionLabel('Tambah Opsi')
                    ->rules(fn (Get $get): array => [
                        new class(TipeSoal::tryFrom((string) $get('tipe')) ?? TipeSoal::Pg) implements ValidationRule
                        {
                            public function __construct(private TipeSoal $tipe) {}

                            public function validate(string $attribute, mixed $value, Closure $fail): void
                            {
                                $opsi = collect($value);
                                $jumlahBenar = $opsi
                                    ->filter(fn (mixed $item): bool => filter_var(data_get($item, 'is_benar', false), FILTER_VALIDATE_BOOLEAN))
                                    ->count();

                                if (in_array($this->tipe, [TipeSoal::Pg, TipeSoal::BenarSalah], true) && $jumlahBenar !== 1) {
                                    $fail('Pilih tepat satu opsi sebagai kunci jawaban.');
                                }

                                if ($this->tipe === TipeSoal::PgKompleks && $jumlahBenar < 2) {
                                    $fail('Pilih minimal dua opsi sebagai kunci jawaban.');
                                }

                                if ($this->tipe === TipeSoal::IsianSingkat && $jumlahBenar < 1) {
                                    $fail('Tandai minimal satu jawaban sebagai jawaban yang diterima.');
                                }

                                if ($this->tipe === TipeSoal::Menjodohkan) {
                                    $pasangan = $opsi->pluck('pasangan')->map(fn (mixed $item): string => trim((string) $item));

                                    if ($pasangan->contains('')) {
                                        $fail('Isi pasangan untuk setiap opsi menjodohkan.');
                                    }

                                    if ($pasangan->filter()->unique()->count() !== $pasangan->filter()->count()) {
                                        $fail('Pasangan menjodohkan tidak boleh duplikat.');
                                    }
                                }
                            }
                        },
                    ])
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nomor')
            ->description(fn (): ?string => $this->getOwnerRecord()->isTerkunci()
                ? PaketSoalLockService::PESAN_TERKUNCI
                : null)
            ->columns([
                TextColumn::make('nomor')
                    ->label('No.')
                    ->sortable(),
                TextColumn::make('tipe')
                    ->label('Tipe')
                    ->badge(),
                TextColumn::make('pertanyaan')
                    ->label('Pertanyaan')
                    ->limit(80)
                    ->wrap()
                    ->searchable(),
                TextColumn::make('bobot')
                    ->label('Bobot')
                    ->sortable(),
                TextColumn::make('opsi_count')
                    ->label('Opsi')
                    ->counts('opsi')
                    ->badge(),
                TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('tipe')
                    ->label('Tipe Soal')
                    ->options(TipeSoal::initialOptions()),
            ])
            ->headerActions([
                Action::make('importWord')
                    ->label('Import Word')
                    ->icon(Heroicon::OutlinedDocumentArrowUp)
                    ->color('success')
                    ->visible(fn (): bool => ! $this->getOwnerRecord()->isTerkunci())
                    ->authorize(fn (): Response => $this->getCreateAuthorizationResponse())
                    ->modalHeading('Import soal dari Word')
                    ->modalDescription('Unggah file .docx. Format: pertanyaan bernomor (manual atau daftar bernomor Word), opsi A–E, lalu baris "Kunci: A" atau "Kunci: A,C". Hanya tipe soal yang aktif yang dapat diimpor; soal tanpa opsi akan dilewati karena tipe essay belum diaktifkan. Kunci harus sesuai dengan label opsi. Gunakan tombol "Template Word" untuk mengunduh contoh format.')
                    ->schema([
                        FileUpload::make('file')
                            ->label('File Word')
                            ->disk('local')
                            ->directory('import-word')
                            ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                            ->maxSize(10240)
                            ->required(),
                    ])
                    ->action(function (array $data): void {
                        $file = $data['file'];
                        $file = is_array($file) ? (string) reset($file) : (string) $file;

                        if (! str_starts_with($file, 'import-word/') || str_contains($file, '..')) {
                            Notification::make()
                                ->title('File import tidak valid')
                                ->danger()
                                ->send();

                            return;
                        }

                        $path = Storage::disk('local')->path($file);

                        try {
                            $hasil = app(SoalWordImportService::class)->import($this->getOwnerRecord(), $path);

                            Notification::make()
                                ->title('Import Word selesai')
                                ->body($hasil->summary())
                                ->success()
                                ->send();

                            if ($hasil->hasErrors()) {
                                Notification::make()
                                    ->title('Sebagian soal dilewati')
                                    ->body(implode(' ', $hasil->errorMessages()))
                                    ->warning()
                                    ->send();
                            }

                            $this->getOwnerRecord()->refresh();
                        } catch (ValidationException $exception) {
                            Notification::make()
                                ->title('Import Word gagal')
                                ->body(collect($exception->errors())->flatten()->implode(' '))
                                ->danger()
                                ->send();
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->title('Import Word gagal')
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();
                        } finally {
                            Storage::disk('local')->delete($file);
                        }
                    }),
                Action::make('wordTemplate')
                    ->label('Template Word')
                    ->icon(Heroicon::OutlinedDocumentArrowDown)
                    ->color('gray')
                    ->url(route('admin.import-template-word'))
                    ->openUrlInNewTab(),
                CreateAction::make()
                    ->label('Tambah Soal')
                    ->visible(fn (): bool => ! $this->getOwnerRecord()->isTerkunci()),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('nomor')
            ->emptyStateHeading('Belum ada soal')
            ->emptyStateDescription('Tambahkan soal atau import soal dari Word ke paket ini.');
    }

    public function getOwnerRecord(): PaketSoal
    {
        /** @var PaketSoal $owner */
        $owner = parent::getOwnerRecord();

        return $owner;
    }

    public function isReadOnly(): bool
    {
        return $this->getOwnerRecord()->isTerkunci();
    }

    protected function getCreateAuthorizationResponse(): Response
    {
        if (! Auth::check()) {
            return Response::deny('Silakan masuk terlebih dahulu.');
        }

        return Gate::inspect('createSoal', $this->getOwnerRecord());
    }
}
