<?php

namespace App\Console\Commands;

use App\Actions\SetupWizardAction;
use App\Models\SetupConfiguration;
use Illuminate\Console\Command;

class SetupApplication extends Command
{
    protected $signature = 'ruang-obat:setup';

    protected $description = 'Setup aplikasi RUANG OBAT untuk pertama kali';

    public function handle(): int
    {
        $this->info('╔════════════════════════════════════════════════════════════════╗');
        $this->info('║         SELAMAT DATANG DI RUANG OBAT - SETUP AWAL APLIKASI         ║');
        $this->info('║      Sistem Informasi Manajemen Obat - Drug Inventory System    ║');
        $this->info('╚════════════════════════════════════════════════════════════════╝');
        $this->newLine();

        $config = SetupConfiguration::getConfig();

        if ($config->is_setup_completed) {
            $this->warn('Setup sudah pernah dilakukan sebelumnya.');
            if (! $this->confirm('Ingin melakukan setup ulang?')) {
                return self::SUCCESS;
            }
        }

        if ($config->isSetupLocked()) {
            $this->error('Setup sudah dikunci setelah 5 kali percobaan gagal.');
            $this->line('Jalankan: php artisan ruang-obat:reset-setup --force');

            return self::FAILURE;
        }

        $this->info('Mari kita mulai setup aplikasi RUANG OBAT...');
        $this->newLine();

        // STEP 1: Super Admin Account
        $this->info('══ STEP 1: SUPER ADMIN ACCOUNT ══');
        $superadminEmail = $this->ask('Email Superadmin', 'admin@dinkes.go.id');
        $superadminName = $this->ask('Nama Superadmin', 'Administrator');

        $passwordChoice = $this->choice('Pilih metode password untuk Super Admin:', ['Manual input', 'Generate random'], 0);
        $password = $passwordChoice === 'Manual input'
            ? $this->askPassword('Password (min 8 char, uppercase, lowercase, number, special)')
            : $this->generatePassword();

        $this->newLine();

        // STEP 1.5: Admin Accounts
        $this->info('══ STEP 2: ADMIN DINAS & GUDANG ACCOUNT ══');

        $adminDinasEmail = $this->ask('Email Admin Dinas', 'admindinas@dinkes.go.id');
        $adminDinasName = $this->ask('Nama Admin Dinas', 'Admin Dinas');
        $adminDinasPasswordChoice = $this->choice('Pilih metode password untuk Admin Dinas:', ['Manual input', 'Generate random', 'Sama dengan Super Admin'], 2);

        if ($adminDinasPasswordChoice === 'Manual input') {
            $adminDinasPassword = $this->askPassword('Password Admin Dinas');
        } elseif ($adminDinasPasswordChoice === 'Generate random') {
            $adminDinasPassword = $this->generatePassword();
        } else {
            $adminDinasPassword = $password;
        }

        $this->newLine();

        $adminGudangEmail = $this->ask('Email Admin Gudang', 'admingudang@dinkes.go.id');
        $adminGudangName = $this->ask('Nama Admin Gudang', 'Admin Gudang');
        $adminGudangPasswordChoice = $this->choice('Pilih metode password untuk Admin Gudang:', ['Manual input', 'Generate random', 'Sama dengan Super Admin'], 2);

        if ($adminGudangPasswordChoice === 'Manual input') {
            $adminGudangPassword = $this->askPassword('Password Admin Gudang');
        } elseif ($adminGudangPasswordChoice === 'Generate random') {
            $adminGudangPassword = $this->generatePassword();
        } else {
            $adminGudangPassword = $password;
        }

        $this->newLine();

        // STEP 2: Organization Info
        $this->info('══ STEP 3: INFORMASI ORGANISASI ══');
        $orgName = $this->ask('Nama Dinas Kesehatan', 'Dinas Kesehatan');
        $orgCode = $this->ask('Kode Dinas', 'DINKES-001');
        $orgDesc = $this->ask('Deskripsi (opsional)', '');
        $this->newLine();

        // STEP 4: Seed Master Data
        $this->info('══ STEP 4: SEED MASTER DATA OTOMATIS ══');
        $this->line('Setelah konfirmasi, sistem akan otomatis menjalankan:');
        $this->line('  - RoleAndPermissionSeeder (roles, permissions, default settings)');
        $this->line('  - ObatSeeder (~57 obat FORNAS, idempotent)');
        $this->line('  - AvatarPresetSeeder (preset avatar boy & girl)');
        $this->newLine();

        // STEP 5: Konfirmasi
        $this->info('══ KONFIRMASI DATA ══');
        $this->table(['Konfigurasi', 'Nilai'], [
            ['Email Superadmin', $superadminEmail],
            ['Nama Superadmin', $superadminName],
            ['Email Admin Dinas', $adminDinasEmail],
            ['Nama Admin Dinas', $adminDinasName],
            ['Email Admin Gudang', $adminGudangEmail],
            ['Nama Admin Gudang', $adminGudangName],
            ['Nama Dinas', $orgName],
            ['Kode Dinas', $orgCode],
        ]);
        $this->newLine();

        if (! $this->confirm('Simpan konfigurasi ini?')) {
            $this->warn('Setup dibatalkan.');

            return self::FAILURE;
        }

        // Execute Setup
        try {
            $action = new SetupWizardAction;
            $action->execute([
                'superadmin_email' => $superadminEmail,
                'superadmin_name' => $superadminName,
                'password' => $password,
                'admin_dinas_email' => $adminDinasEmail,
                'admin_dinas_name' => $adminDinasName,
                'admin_dinas_password' => $adminDinasPassword,
                'admin_gudang_email' => $adminGudangEmail,
                'admin_gudang_name' => $adminGudangName,
                'admin_gudang_password' => $adminGudangPassword,
                'organization_name' => $orgName,
                'organization_code' => $orgCode,
                'organization_description' => $orgDesc,
            ]);

            $this->info('✓ Setup berhasil diselesaikan!');
            $this->newLine();
            $this->info('Kredensial Login:');
            $this->line("  Super Admin Email: {$superadminEmail}");
            if ($passwordChoice === 'Generate random') {
                $this->line("  Super Admin Password: {$password}");
            }
            $this->line("  Admin Dinas Email: {$adminDinasEmail}");
            if ($adminDinasPasswordChoice === 'Generate random') {
                $this->line("  Admin Dinas Password: {$adminDinasPassword}");
            }
            $this->line("  Admin Gudang Email: {$adminGudangEmail}");
            if ($adminGudangPasswordChoice === 'Generate random') {
                $this->line("  Admin Gudang Password: {$adminGudangPassword}");
            }
            if ($passwordChoice === 'Generate random' || $adminDinasPasswordChoice === 'Generate random' || $adminGudangPasswordChoice === 'Generate random') {
                $this->warn('⚠ COPY PASSWORD DI ATAS SEBELUM TUTUP TERMINAL!');
            }
            $this->newLine();
            $this->warn('⚠ PENTING: Ubah password setelah login pertama kali!');

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Setup gagal: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    private function generatePassword(): string
    {
        $length = 16;
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*';
        $password = '';

        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }

        $this->line("Generated password: {$password}");

        return $password;
    }

    private function askPassword(string $question): string
    {
        do {
            $password = $this->secret($question);
            $confirm = $this->secret('Confirm password');

            if ($password !== $confirm) {
                $this->error('Password tidak cocok!');

                continue;
            }

            if (strlen($password) < 8) {
                $this->error('Password minimal 8 karakter!');

                continue;
            }

            break;
        } while (true);

        return $password;
    }
}
