<?php

namespace App\Console\Commands;

use App\Models\SetupConfiguration;
use App\Models\User;
use Illuminate\Console\Command;

class ResetSetupCommand extends Command
{
    protected $signature = 'ruang-obat:reset-setup {--force : Skip confirmation}';

    protected $description = 'Reset setup wizard configuration and delete associated records';

    public function handle(): int
    {
        $this->info('╔════════════════════════════════════════════════════════════════╗');
        $this->info('║                    RESET SETUP WIZARD                           ║');
        $this->info('║              ⚠️  WARNING: This action cannot be undone!  ⚠️      ║');
        $this->info('╚════════════════════════════════════════════════════════════════╝');
        $this->newLine();

        if (! $this->option('force')) {
            $this->warn('This will:');
            $this->line('  1. Delete superadmin user');
            $this->line('  2. Reset setup configuration');
            $this->newLine();

            if (! $this->confirm('Are you sure you want to proceed?')) {
                $this->info('Reset cancelled.');

                return self::SUCCESS;
            }
        }

        try {
            $config = SetupConfiguration::getConfig();

            // Delete superadmin user if exists
            if ($config->superadmin_email) {
                User::where('email', $config->superadmin_email)->forceDelete();
                $this->line('✓ Superadmin user deleted');
            }

            // Delete admin dinas and admin gudang that were created during setup
            // This is a best-effort deletion for the reset process
            $deletedAdminCount = User::whereHas('roles', function ($query) {
                $query->whereIn('name', ['admin_dinas', 'admin_gudang']);
            })->forceDelete();

            if ($deletedAdminCount > 0) {
                $this->line("✓ {$deletedAdminCount} Admin user(s) deleted");
            }

            // Reset setup configuration
            $config->update([
                'is_setup_completed' => false,
                'superadmin_email' => null,
                'superadmin_name' => null,
                'organization_name' => null,
                'organization_code' => null,
                'setup_attempt_count' => 0,
                'last_setup_attempt_at' => null,
                'setup_completed_at' => null,
            ]);
            $this->line('✓ Setup configuration reset');

            $this->newLine();
            $this->info('✓ Setup wizard has been reset successfully!');
            $this->line('You can now run: php artisan ruang-obat:setup');

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Error during reset: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
