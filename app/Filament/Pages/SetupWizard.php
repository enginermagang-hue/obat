<?php

namespace App\Filament\Pages;

use App\Actions\SetupWizardAction;
use App\Models\SetupConfiguration;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\HasMaxWidth;
use Filament\Pages\Concerns\HasTopbar;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\Rules\Password;

class SetupWizard extends Page implements HasForms
{
    use HasMaxWidth;
    use HasTopbar;
    use InteractsWithForms;

    protected static ?string $title = 'Setup Awal Aplikasi RUANG OBAT';

    protected static bool $shouldRegisterNavigation = false;

    protected static string $layout = 'filament-panels::components.layout.simple';

    protected string $view = 'filament.pages.setup-wizard';

    public function hasTopbar(): bool
    {
        return false;
    }

    public ?array $data = [];

    public function hasLogo(): bool
    {
        return true;
    }

    public function getMaxWidth(): ?Width
    {
        return Width::FourExtraLarge;
    }

    protected function getLayoutData(): array
    {
        return [
            'hasTopbar' => $this->hasTopbar(),
            'maxContentWidth' => $this->getMaxWidth(),
            'maxWidth' => $this->getMaxWidth(),
        ];
    }

    public function mount(): void
    {
        if (SetupConfiguration::isSetupCompleted()) {
            redirect()->route('filament.admin.pages.custom-dashboard');
        }

        $config = SetupConfiguration::getConfig();

        if ($config->isSetupLocked()) {
            Notification::make()
                ->danger()
                ->title('Setup Dikunci')
                ->body('Setup sudah dikunci setelah 5 kali percobaan gagal. Jalankan: php artisan ruang-obat:reset-setup --force')
                ->persistent()
                ->send();

            redirect()->route('login');
        }

        $this->form->fill([
            'superadmin_email' => $config->superadmin_email,
            'superadmin_name' => $config->superadmin_name,
            'organization_name' => $config->organization_name,
            'organization_code' => $config->organization_code,
            'organization_description' => $config->organization_description,
        ]);
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->statePath('data')
            ->components([
                Wizard::make([
                    Step::make('Super Admin')
                        ->description('Buat akun superadmin')
                        ->schema([
                            Section::make('Data Superadmin')
                                ->description('Akun administrator utama sistem')
                                ->schema([
                                    Grid::make(2)->schema([
                                        TextInput::make('superadmin_name')
                                            ->label('Nama Superadmin')
                                            ->required()
                                            ->placeholder('Contoh: Budi Santoso'),
                                        TextInput::make('superadmin_email')
                                            ->label('Email Superadmin')
                                            ->email()
                                            ->required()
                                            ->unique('users', 'email')
                                            ->placeholder('admin@dinkes.go.id'),
                                    ]),
                                    TextInput::make('password')
                                        ->label('Password Superadmin')
                                        ->password()
                                        ->required()
                                        ->minLength(8)
                                        ->rule(Password::default())
                                        ->placeholder('Min 8 karakter: uppercase, lowercase, number, special'),
                                    TextInput::make('password_confirm')
                                        ->label('Konfirmasi Password Superadmin')
                                        ->password()
                                        ->required()
                                        ->same('password'),
                                ]),
                        ]),

                    Step::make('Admin Dinas & Gudang')
                        ->description('Buat akun admin tambahan')
                        ->schema([
                            Section::make('Data Admin Dinas')
                                ->description('Akun untuk pengelola dari Dinas Kesehatan')
                                ->schema([
                                    Grid::make(2)->schema([
                                        TextInput::make('admin_dinas_name')
                                            ->label('Nama Admin Dinas')
                                            ->required()
                                            ->placeholder('Contoh: Admin Dinas'),
                                        TextInput::make('admin_dinas_email')
                                            ->label('Email Admin Dinas')
                                            ->email()
                                            ->required()
                                            ->unique('users', 'email')
                                            ->placeholder('admindinas@dinkes.go.id'),
                                    ]),
                                    TextInput::make('admin_dinas_password')
                                        ->label('Password Admin Dinas')
                                        ->password()
                                        ->required()
                                        ->minLength(8)
                                        ->rule(Password::default())
                                        ->placeholder('Min 8 karakter: uppercase, lowercase, number, special'),
                                    TextInput::make('admin_dinas_password_confirm')
                                        ->label('Konfirmasi Password Admin Dinas')
                                        ->password()
                                        ->required()
                                        ->same('admin_dinas_password'),
                                ]),

                            Section::make('Data Admin Gudang')
                                ->description('Akun untuk pengelola Gudang Farmasi')
                                ->schema([
                                    Grid::make(2)->schema([
                                        TextInput::make('admin_gudang_name')
                                            ->label('Nama Admin Gudang')
                                            ->required()
                                            ->placeholder('Contoh: Admin Gudang'),
                                        TextInput::make('admin_gudang_email')
                                            ->label('Email Admin Gudang')
                                            ->email()
                                            ->required()
                                            ->unique('users', 'email')
                                            ->placeholder('admingudang@dinkes.go.id'),
                                    ]),
                                    TextInput::make('admin_gudang_password')
                                        ->label('Password Admin Gudang')
                                        ->password()
                                        ->required()
                                        ->minLength(8)
                                        ->rule(Password::default())
                                        ->placeholder('Min 8 karakter: uppercase, lowercase, number, special'),
                                    TextInput::make('admin_gudang_password_confirm')
                                        ->label('Konfirmasi Password Admin Gudang')
                                        ->password()
                                        ->required()
                                        ->same('admin_gudang_password'),
                                ]),
                        ]),

                    Step::make('Organisasi')
                        ->description('Informasi dasar dinas kesehatan')
                        ->schema([
                            Section::make('Data Organisasi')
                                ->description('Masukkan informasi dinas kesehatan Anda')
                                ->schema([
                                    Grid::make(2)->schema([
                                        TextInput::make('organization_name')
                                            ->label('Nama Dinas Kesehatan')
                                            ->required()
                                            ->placeholder('Contoh: Dinas Kesehatan Kota Bandung'),
                                        TextInput::make('organization_code')
                                            ->label('Kode Dinas')
                                            ->required()
                                            ->unique('setup_configurations', 'organization_code')
                                            ->placeholder('Contoh: DINKES-BDG'),
                                    ]),
                                    MarkdownEditor::make('organization_description')
                                        ->label('Deskripsi')
                                        ->columnSpanFull()
                                        ->placeholder('Deskripsi singkat tentang organisasi'),
                                ]),
                        ]),

                    Step::make('Master Data')
                        ->description('Seed data obat & avatar otomatis')
                        ->schema([
                            Section::make('Seeding Master Data Otomatis')
                                ->description('Sistem akan menjalankan seeders berikut secara otomatis saat setup diselesaikan:')
                                ->schema([
                                    TextEntry::make('seed_info')
                                        ->label('')
                                        ->html()
                                        ->state('<ul class="list-disc ps-5 space-y-1 text-sm text-gray-600 dark:text-gray-400">
                                            <li><strong>RoleAndPermissionSeeder</strong> &mdash; 5 roles, permissions, default PDF &amp; nomor settings</li>
                                            <li><strong>FaskesSeeder</strong> &mdash; 26 Puskesmas &amp; 13 Pustu, pemetaan BPJS, data Kabupaten Kupang</li>
                                            <li><strong>SupplierSeeder</strong> &mdash; 10 supplier obat nasional (Kimia Farma, Kalbe Farma, Sanbe, dll.)</li>
                                            <li><strong>ObatSeeder</strong> &mdash; ~57 obat FORNAS standar (idempotent)</li>
                                            <li><strong>AvatarPresetSeeder</strong> &mdash; preset avatar boy &amp; girl</li>
                                        </ul>'),
                                ]),
                        ]),

                    Step::make('Konfirmasi')
                        ->description('Tinjau dan selesaikan')
                        ->schema([
                            Section::make('Ringkasan Setup')
                                ->description('Periksa data sebelum submit')
                                ->schema([
                                    TextInput::make('superadmin_name')
                                        ->label('Nama Superadmin')
                                        ->disabled(),
                                    TextInput::make('superadmin_email')
                                        ->label('Email Superadmin')
                                        ->disabled(),
                                    TextInput::make('organization_name')
                                        ->label('Dinas Kesehatan')
                                        ->disabled(),
                                ]),
                        ]),
                ])
                    ->columnSpanFull()
                    ->nextAction(fn ($action) => $action->label('Lanjut'))
                    ->previousAction(fn ($action) => $action->label('Kembali'))
                    ->submitAction(new HtmlString(Blade::render(<<<'BLADE'
                        <x-filament::button type="submit" size="sm">
                            Selesaikan Setup
                        </x-filament::button>
BLADE))),
            ]);
    }

    public function submit(): void
    {
        $data = $this->form->getState();

        try {
            $action = new SetupWizardAction;
            $action->execute([
                'superadmin_email' => $data['superadmin_email'],
                'superadmin_name' => $data['superadmin_name'],
                'password' => $data['password'],
                'admin_dinas_email' => $data['admin_dinas_email'],
                'admin_dinas_name' => $data['admin_dinas_name'],
                'admin_dinas_password' => $data['admin_dinas_password'],
                'admin_gudang_email' => $data['admin_gudang_email'],
                'admin_gudang_name' => $data['admin_gudang_name'],
                'admin_gudang_password' => $data['admin_gudang_password'],
                'organization_name' => $data['organization_name'],
                'organization_code' => $data['organization_code'],
                'organization_description' => $data['organization_description'] ?? '',
            ]);

            Notification::make()
                ->success()
                ->title('Setup Berhasil')
                ->body('Aplikasi RUANG OBAT telah dikonfigurasi. Silahkan login kembali.')
                ->send();

            redirect()->route('login');
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Setup Gagal')
                ->body('Error: '.$e->getMessage())
                ->send();
        }
    }
}
