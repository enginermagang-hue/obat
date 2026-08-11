<?php

namespace App\Actions;

use App\Models\SetupConfiguration;
use App\Models\User;
use Database\Seeders\AvatarPresetSeeder;
use Database\Seeders\FaskesSeeder;
use Database\Seeders\ObatSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\SupplierSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SetupWizardAction
{
    public function execute(array $data): bool
    {
        return DB::transaction(function () use ($data) {
            try {
                $this->validateSetupData($data);
                app(RoleAndPermissionSeeder::class)->run();
                app(FaskesSeeder::class)->run();
                app(SupplierSeeder::class)->run();
                app(ObatSeeder::class)->run();
                app(AvatarPresetSeeder::class)->run();
                $this->createSuperAdminUser($data);
                $this->saveSetupConfiguration($data);

                return true;
            } catch (\Exception $e) {
                $this->rollbackSetup($data);

                throw $e;
            }
        });
    }

    protected function validateSetupData(array $data): void
    {
        $config = SetupConfiguration::getConfig();

        if ($config->isSetupLocked()) {
            throw new \Exception('Setup sudah dikunci setelah 5 kali percobaan gagal.');
        }

        if (User::whereIn('email', [
            $data['superadmin_email'],
            $data['admin_dinas_email'] ?? '',
            $data['admin_gudang_email'] ?? '',
        ])->exists()) {
            throw new \Exception('Salah satu email admin sudah terdaftar di sistem.');
        }

        $emails = array_filter([
            $data['superadmin_email'] ?? null,
            $data['admin_dinas_email'] ?? null,
            $data['admin_gudang_email'] ?? null,
        ]);
        if (count($emails) !== count(array_unique($emails))) {
            throw new \Exception('Setiap akun harus menggunakan email yang berbeda.');
        }
    }

    protected function createSuperAdminUser(array $data): User
    {
        $user = User::create([
            'name' => $data['superadmin_name'],
            'email' => $data['superadmin_email'],
            'password' => Hash::make($data['password']),
            'email_verified_at' => now(),
            'fasilitas_kesehatan_id' => null,
        ]);

        $user->syncRoles('super_admin');

        $this->createAdminDinasUser($data);
        $this->createAdminGudangUser($data);

        return $user;
    }

    protected function createAdminDinasUser(array $data): void
    {
        if (empty($data['admin_dinas_email']) || empty($data['admin_dinas_name'])) {
            return;
        }

        $user = User::create([
            'name' => $data['admin_dinas_name'],
            'email' => $data['admin_dinas_email'],
            'password' => Hash::make($data['admin_dinas_password'] ?? $data['password']),
            'email_verified_at' => now(),
            'fasilitas_kesehatan_id' => null,
        ]);

        $user->syncRoles('admin_dinas');
    }

    protected function createAdminGudangUser(array $data): void
    {
        if (empty($data['admin_gudang_email']) || empty($data['admin_gudang_name'])) {
            return;
        }

        $user = User::create([
            'name' => $data['admin_gudang_name'],
            'email' => $data['admin_gudang_email'],
            'password' => Hash::make($data['admin_gudang_password'] ?? $data['password']),
            'email_verified_at' => now(),
            'fasilitas_kesehatan_id' => null,
        ]);

        $user->syncRoles('admin_gudang');
    }

    protected function saveSetupConfiguration(array $data): void
    {
        $config = SetupConfiguration::getConfig();
        $config->update([
            'organization_name' => $data['organization_name'],
            'organization_code' => $data['organization_code'],
            'organization_description' => $data['organization_description'] ?? '',
            'superadmin_email' => $data['superadmin_email'],
            'superadmin_name' => $data['superadmin_name'],
            'is_setup_completed' => true,
            'setup_completed_at' => now(),
            'setup_attempt_count' => 0,
        ]);
    }

    protected function rollbackSetup(array $data): void
    {
        $emailsToDelete = array_filter([
            $data['superadmin_email'] ?? null,
            $data['admin_dinas_email'] ?? null,
            $data['admin_gudang_email'] ?? null,
        ]);

        if (! empty($emailsToDelete)) {
            User::whereIn('email', $emailsToDelete)->forceDelete();
        }

        $config = SetupConfiguration::getConfig();
        $config->incrementAttempt();
    }
}
