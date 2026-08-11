<x-filament-panels::page.simple>
    <div class="mx-auto max-w-4xl space-y-6">
        <!-- Header -->
        <div class="rounded-lg bg-gradient-to-r from-blue-50 to-indigo-50 p-8 dark:from-blue-900/20 dark:to-indigo-900/20">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                Selamat Datang di RUANG OBAT
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-300">
                Sistem Informasi Manajemen Obat - Setup Awal Aplikasi
            </p>
            <p class="mt-4 text-sm text-gray-600 dark:text-gray-400">
                Ikuti langkah-langkah di bawah ini untuk mengkonfigurasi aplikasi RUANG OBAT sesuai dengan kebutuhan organisasi Anda.
            </p>
        </div>

        <!-- Form -->
        <div class="rounded-lg bg-white p-8 shadow-sm dark:bg-gray-900">
            <form wire:submit="submit" class="space-y-6">
                {{ $this->form }}

                <div class="flex items-center justify-between border-t border-gray-200 pt-6 dark:border-gray-700">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Setup ini hanya perlu dilakukan sekali saat pertama kali menggunakan aplikasi.
                    </p>
                </div>
            </form>
        </div>

        <!-- Info Box -->
        <div class="rounded-lg border border-amber-200 bg-amber-50 p-6 dark:border-amber-800 dark:bg-amber-900/20">
            <div class="flex gap-4">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-amber-600 dark:text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold text-amber-900 dark:text-amber-100">
                        Perhatian Penting
                    </h3>
                    <p class="mt-2 text-sm text-amber-800 dark:text-amber-200">
                        Setelah menyelesaikan setup ini, sistem akan menghapus akun demo dan menggunakan konfigurasi yang Anda buat. Pastikan semua data sudah benar sebelum menyelesaikan.
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page.simple>
