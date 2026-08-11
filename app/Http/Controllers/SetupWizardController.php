<?php

namespace App\Http\Controllers;

use App\Actions\SetupWizardAction;
use App\Models\SetupConfiguration;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class SetupWizardController extends Controller
{
    public function show()
    {
        if (SetupConfiguration::isSetupCompleted()) {
            return redirect()->route('login');
        }

        $config = SetupConfiguration::getConfig();

        if ($config->isSetupLocked()) {
            return redirect()->route('login')
                ->with('error', 'Setup sudah dikunci setelah 5 kali percobaan gagal. Jalankan: php artisan ruang-obat:reset-setup --force');
        }

        return view('setup-wizard', [
            'config' => $config,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'superadmin_name' => ['required', 'string', 'max:255'],
            'superadmin_email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
            'password_confirmation' => ['required', 'string'],
            'admin_dinas_name' => ['required', 'string', 'max:255'],
            'admin_dinas_email' => ['required', 'email', 'max:255', 'unique:users,email', 'different:superadmin_email'],
            'admin_dinas_password' => ['required', 'string', 'min:8', Password::min(8)->mixedCase()->numbers()->symbols()],
            'admin_gudang_name' => ['required', 'string', 'max:255'],
            'admin_gudang_email' => ['required', 'email', 'max:255', 'unique:users,email', 'different:superadmin_email', 'different:admin_dinas_email'],
            'admin_gudang_password' => ['required', 'string', 'min:8', Password::min(8)->mixedCase()->numbers()->symbols()],
            'organization_name' => ['required', 'string', 'max:255'],
            'organization_code' => ['required', 'string', 'max:255'],
            'organization_description' => ['nullable', 'string'],
        ], [
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.mixed_case' => 'Password harus mengandung huruf besar dan kecil.',
            'password.numbers' => 'Password harus mengandung angka.',
            'password.symbols' => 'Password harus mengandung simbol (!@#$%^&* dll).',
            'superadmin_email.unique' => 'Email superadmin sudah terdaftar di sistem.',
            'superadmin_email.different' => 'Email tidak boleh sama dengan email lain.',
            'admin_dinas_email.unique' => 'Email admin dinas sudah terdaftar di sistem.',
            'admin_dinas_email.different' => 'Email admin dinas tidak boleh sama dengan email superadmin.',
            'admin_gudang_email.unique' => 'Email admin gudang sudah terdaftar di sistem.',
            'admin_gudang_email.different' => 'Email admin gudang tidak boleh sama dengan email lain.',
            'admin_dinas_password.min' => 'Password admin dinas minimal 8 karakter.',
            'admin_dinas_password.mixed_case' => 'Password harus mengandung huruf besar dan kecil.',
            'admin_dinas_password.numbers' => 'Password harus mengandung angka.',
            'admin_dinas_password.symbols' => 'Password harus mengandung simbol.',
            'admin_gudang_password.min' => 'Password admin gudang minimal 8 karakter.',
            'admin_gudang_password.mixed_case' => 'Password harus mengandung huruf besar dan kecil.',
            'admin_gudang_password.numbers' => 'Password harus mengandung angka.',
            'admin_gudang_password.symbols' => 'Password harus mengandung simbol.',
        ]);

        try {
            $action = new SetupWizardAction;
            $action->execute([
                'superadmin_name' => $validated['superadmin_name'],
                'superadmin_email' => $validated['superadmin_email'],
                'password' => $validated['password'],
                'admin_dinas_name' => $validated['admin_dinas_name'],
                'admin_dinas_email' => $validated['admin_dinas_email'],
                'admin_dinas_password' => $validated['admin_dinas_password'],
                'admin_gudang_name' => $validated['admin_gudang_name'],
                'admin_gudang_email' => $validated['admin_gudang_email'],
                'admin_gudang_password' => $validated['admin_gudang_password'],
                'organization_name' => $validated['organization_name'],
                'organization_code' => $validated['organization_code'],
                'organization_description' => $validated['organization_description'] ?? '',
            ]);

            return redirect()->route('login')
                ->with('success', 'Setup berhasil diselesaikan. Silakan login dengan akun yang telah dibuat.');

        } catch (\Exception $e) {
            return back()
                ->withInput($request->except(['password', 'password_confirmation', 'admin_dinas_password', 'admin_dinas_password_confirm', 'admin_gudang_password', 'admin_gudang_password_confirm']))
                ->with('error', 'Setup gagal: '.$e->getMessage())
                ->with('currentStep', 3);
        }
    }
}
