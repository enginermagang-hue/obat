<?php

namespace App\Filament\Pages;

use App\Models\AvatarPreset;
use App\Models\UserPreference;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use UnitEnum;

class ProfilSaya extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-circle';

    protected static string|UnitEnum|null $navigationGroup = 'Sistem';

    protected static ?string $navigationLabel = 'Profil Saya';

    protected static ?int $navigationSort = 81;

    public function getView(): string
    {
        return 'filament.pages.profil-saya';
    }

    public function getTitle(): string
    {
        return 'Profil Saya';
    }

    public ?array $data = [];

    public function mount(): void
    {
        $user = auth()->user();

        $this->form->fill(
            array_merge(
                UserPreference::defaultsForUser($user),
                ['google_login_enabled' => $user->google_login_enabled ?? false],
                ['name' => $user->name, 'email' => $user->email]
            )
        );

        if (($this->data['avatar_type'] ?? '') === 'preset') {
            $this->data['avatar_path'] = UserPreference::where('user_id', $user->id)
                ->value('avatar_path');
        }
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('Informasi Akun')
                    ->description('Perbarui nama, email, atau password akun Anda.')
                    ->contained(false)
                    ->aside()
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignorable: auth()->user()),
                        TextInput::make('current_password')
                            ->label('Password Saat Ini')
                            ->password()
                            ->revealable()
                            ->rule('current_password')
                            ->required(fn ($get) => $get('email') !== auth()->user()->email || filled($get('password')))
                            ->helperText('Diperlukan untuk mengubah email atau password.'),
                        TextInput::make('password')
                            ->label('Password Baru')
                            ->password()
                            ->revealable()
                            ->confirmed()
                            ->nullable()
                            ->minLength(8)
                            ->maxLength(255)
                            ->dehydrated(fn (?string $state): bool => filled($state)),
                        TextInput::make('password_confirmation')
                            ->label('Konfirmasi Password Baru')
                            ->password()
                            ->revealable(),
                    ]),
                Section::make('Avatar')
                    ->description('Pengaturan avatar Anda. Anda dapat memilih untuk menggunakan avatar berdasarkan inisial nama, upload gambar, atau menggunakan preset.')
                    ->columns(1)
                    ->contained(false)
                    ->aside()
                    ->schema([
                        Select::make('avatar_type')
                            ->label('Tipe Avatar')
                            ->native(false)
                            ->required()
                            ->options([
                                'initials' => 'Inisial Nama',
                                'upload' => 'Upload Gambar',
                                'preset' => 'Preset',
                                'dicebear' => 'DiceBear Avatar',
                            ])
                            ->live()
                            ->helperText('Pilih jenis avatar yang ingin Anda gunakan.'),
                        Select::make('avatar_dicebear_style')
                            ->label('Gaya DiceBear')
                            ->helperText('Pilih gaya avatar DiceBear yang ingin Anda gunakan.')
                            ->native(false)
                            ->options(UserPreference::dicebearStyles())
                            ->live()
                            ->hidden(fn ($get) => $get('avatar_type') !== 'dicebear'),

                        FileUpload::make('avatar_path')
                            ->label('Upload Avatar')
                            ->image()
                            ->disk('public')
                            ->directory('avatars')
                            ->visibility('public')
                            ->maxSize(1024)
                            ->imageResizeTargetWidth('256')
                            ->imageResizeTargetHeight('256')
                            ->hidden(fn ($get) => $get('avatar_type') !== 'upload')
                            ->columnSpan(2),
                        Select::make('avatar_path')
                            ->label('Pilih Avatar Preset')
                            ->options(fn () => AvatarPreset::where('is_active', true)
                                ->get()
                                ->groupBy(fn ($preset) => ucfirst($preset->kategori))
                                ->map(fn ($group) => $group->mapWithKeys(fn ($preset) => [
                                    $preset->file_path => '<img src="'.asset($preset->file_path).'" class="w-8 h-8 rounded-full inline-block mr-2"> '.e($preset->nama),
                                ])))
                            ->searchable()
                            ->native(false)
                            ->allowHtml()
                            ->hidden(fn ($get) => $get('avatar_type') !== 'preset'),
                        View::make('filament.components.dicebear-preview')
                            ->hidden(fn ($get) => $get('avatar_type') !== 'dicebear')
                            ->viewData(fn ($get) => [
                                'style' => $get('avatar_dicebear_style') ?? 'avataaars',
                                'seed' => urlencode(auth()->user()?->email ?? 'guest'),
                            ]),
                    ]),
                Section::make('Login Google')
                    ->contained(false)
                    ->aside()
                    ->description('Pengaturan login dengan Google. Anda dapat mengaktifkan atau menonaktifkan login dengan Google.')
                    ->schema([
                        Toggle::make('google_login_enabled')
                            ->label('Aktifkan Login dengan Google')
                            ->hint('Jika diaktifkan, Anda bisa login menggunakan akun Google'),
                        View::make('filament.components.google-login-section'),
                    ])
                    ->extraAttributes([
                        'class' => 'mt-4',
                    ]),
                Section::make('Preferensi Lainnya')
                    ->description('')
                    ->contained(false)
                    ->aside()
                    ->schema([
                        Select::make('bahasa')
                            ->label('Bahasa')
                            ->options([
                                'id' => 'Indonesia',
                                'en' => 'English',
                            ]),
                        Select::make('items_per_halaman')
                            ->label('Item per Halaman')
                            ->options([
                                10 => '10',
                                25 => '25',
                                50 => '50',
                            ]),
                        Toggle::make('notifikasi_email')
                            ->label('Notifikasi Email'),
                        Toggle::make('notifikasi_browser')
                            ->label('Notifikasi Browser'),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $user = auth()->user();

        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
        ]);

        if (filled($data['password'] ?? null)) {
            $user->update(['password' => $data['password']]);
        }

        unset(
            $data['name'], $data['email'], $data['password'],
            $data['password_confirmation'], $data['current_password'],
        );

        if (array_key_exists('google_login_enabled', $data)) {
            $user->update(['google_login_enabled' => $data['google_login_enabled']]);
            unset($data['google_login_enabled']);
        }

        UserPreference::updateOrCreate(
            ['user_id' => $user->id],
            $data
        );

        $user->unsetRelation('preferences');

        Notification::make()
            ->title('Profil berhasil disimpan')
            ->success()
            ->send();
    }
}
