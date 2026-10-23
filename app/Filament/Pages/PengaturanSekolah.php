<?php

namespace App\Filament\Pages;

use App\Enums\JenjangSekolah;
use App\Models\PengaturanSekolah as PengaturanSekolahModel;
use App\Services\PengaturanSekolahService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Gate;
use UnitEnum;

class PengaturanSekolah extends Page
{
    /**
     * @var array<int, string>
     */
    private const FORM_FIELDS = [
        'nama_sekolah',
        'jenjang',
        'npsn',
        'alamat',
        'kota',
        'logo',
        'nama_kepala_sekolah',
        'nip_kepala_sekolah',
        'batas_pelanggaran',
        'auto_tangguhkan',
        'idle_timeout_menit',
    ];

    protected static ?string $title = 'Pengaturan Sekolah';

    protected static ?string $navigationLabel = 'Pengaturan Sekolah';

    protected static string|UnitEnum|null $navigationGroup = 'Pengaturan';

    protected static ?int $navigationSort = 100;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    /**
     * @var array<string, mixed> | null
     */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return Gate::allows('viewAny', PengaturanSekolahModel::class);
    }

    public function mount(PengaturanSekolahService $pengaturanSekolahService): void
    {
        Gate::authorize('viewAny', PengaturanSekolahModel::class);

        $this->form->fill(
            $pengaturanSekolahService->get()->only(self::FORM_FIELDS),
        );
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identitas Sekolah')
                ->columns(2)
                ->schema([
                    TextInput::make('nama_sekolah')
                        ->label('Nama Sekolah')
                        ->required()
                        ->maxLength(255)
                        ->autofocus(),
                    Select::make('jenjang')
                        ->label('Jenjang')
                        ->options(JenjangSekolah::class)
                        ->required()
                        ->native(false),
                    TextInput::make('npsn')
                        ->label('NPSN')
                        ->maxLength(20),
                    TextInput::make('kota')
                        ->label('Kota')
                        ->maxLength(100)
                        ->helperText('Digunakan untuk tanda tangan dokumen.'),
                    Textarea::make('alamat')
                        ->label('Alamat')
                        ->rows(3)
                        ->maxLength(1000)
                        ->columnSpanFull(),
                    FileUpload::make('logo')
                        ->label('Logo Sekolah')
                        ->image()
                        ->disk('public')
                        ->directory('sekolah')
                        ->visibility('public')
                        ->maxSize(2048)
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                        ->helperText('Format JPG, PNG, atau WEBP. Maksimal 2 MB.')
                        ->columnSpanFull(),
                ]),
            Section::make('Kepala Sekolah')
                ->columns(2)
                ->schema([
                    TextInput::make('nama_kepala_sekolah')
                        ->label('Nama Kepala Sekolah')
                        ->maxLength(255),
                    TextInput::make('nip_kepala_sekolah')
                        ->label('NIP Kepala Sekolah')
                        ->maxLength(50),
                ]),
            Section::make('Keamanan Ujian')
                ->columns(2)
                ->schema([
                    TextInput::make('batas_pelanggaran')
                        ->label('Batas Pelanggaran')
                        ->numeric()
                        ->integer()
                        ->minValue(1)
                        ->maxValue(255)
                        ->required()
                        ->default(5)
                        ->helperText('Jumlah pelanggaran sebelum ujian ditangguhkan.'),
                    TextInput::make('idle_timeout_menit')
                        ->label('Auto-logout (menit)')
                        ->numeric()
                        ->integer()
                        ->minValue(1)
                        ->maxValue(255)
                        ->required()
                        ->default(10)
                        ->helperText('Batas tidak aktif sebelum admin/guru logout otomatis.'),
                    Toggle::make('auto_tangguhkan')
                        ->label('Tangguhkan otomatis')
                        ->default(true)
                        ->helperText('Jika aktif, ujian ditangguhkan setelah batas pelanggaran tercapai.'),
                ]),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            $this->getFormContentComponent(),
        ]);
    }

    public function getFormContentComponent(): Component
    {
        return Form::make([EmbeddedSchema::make('form')])
            ->id('form')
            ->livewireSubmitHandler('save')
            ->footer([
                Actions::make($this->getFormActions())
                    ->alignment(Alignment::Start)
                    ->key('form-actions'),
            ]);
    }

    /**
     * @return array<Action>
     */
    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Simpan')
                ->submit('save')
                ->keyBindings(['mod+s']),
        ];
    }

    public function save(PengaturanSekolahService $pengaturanSekolahService): void
    {
        $pengaturanSekolah = $pengaturanSekolahService->get();

        Gate::authorize('update', $pengaturanSekolah);

        $pengaturanSekolah = $pengaturanSekolahService->update(
            $this->form->getState(),
        );

        $this->form->fill($pengaturanSekolah->only(self::FORM_FIELDS));

        Notification::make()
            ->success()
            ->title('Pengaturan sekolah berhasil disimpan.')
            ->send();
    }
}
