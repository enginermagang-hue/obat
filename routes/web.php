<?php

use App\Http\Controllers\CetakFakturController;
use App\Http\Controllers\CetakFakturPenerimaanController;
use App\Http\Controllers\CetakFakturReturController;
use App\Http\Controllers\CetakLplpoController;
use App\Http\Controllers\CetakNeracaController;
use App\Http\Controllers\CetakObatExcelController;
use App\Http\Controllers\CetakPermintaanController;
use App\Http\Controllers\CetakPermintaanExcelController;
use App\Http\Controllers\CetakRkoController;
use App\Http\Controllers\CustomAuthController;
use App\Http\Controllers\DownloadSuratPermintaanController;
use App\Http\Controllers\DownloadTemplatePrediksiController;
use App\Http\Controllers\GoogleSocialiteController;
use App\Http\Controllers\PanduanController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/panduan', [PanduanController::class, 'index'])->name('panduan.index');
Route::get('/panduan/{slug}', [PanduanController::class, 'show'])->name('panduan.show');

Route::middleware('web')->group(function () {
    Route::get('/login', [CustomAuthController::class, 'showLoginForm'])
        ->name('login')
        ->middleware('guest');

    Route::post('/login', [CustomAuthController::class, 'login'])
        ->name('login.post')
        ->middleware('guest');

    Route::post('/logout', [CustomAuthController::class, 'logout'])
        ->name('logout')
        ->middleware('auth');

    Route::get('/admin/distribusi/{distribusi}/cetak-faktur', CetakFakturController::class)
        ->name('admin.distribusi.cetak-faktur')
        ->middleware('auth');

    Route::get('/admin/penerimaan/{penerimaan}/cetak-faktur', CetakFakturPenerimaanController::class)
        ->name('admin.penerimaan.cetak-faktur')
        ->middleware('auth');

    Route::get('/admin/retur/{retur}/cetak-faktur', CetakFakturReturController::class)
        ->name('admin.retur.cetak-faktur')
        ->middleware('auth');

    Route::get('/admin/neraca/{neraca}/cetak-pdf', [CetakNeracaController::class, 'cetakPdf'])
        ->name('admin.neraca.cetak-pdf')
        ->middleware('auth');

    Route::get('/admin/neraca/{neraca}/cetak-xls', [CetakNeracaController::class, 'cetakXls'])
        ->name('admin.neraca.cetak-xls')
        ->middleware('auth');

    Route::get('/admin/permintaan/{permintaan}/cetak-faktur', CetakPermintaanController::class)
        ->name('admin.permintaan.cetak-faktur')
        ->middleware('auth');

    Route::get('/admin/permintaan/{permintaan}/download-surat', DownloadSuratPermintaanController::class)
        ->name('admin.permintaan.download-surat')
        ->middleware('auth');

    Route::get('/admin/permintaan/cetak-xls', CetakPermintaanExcelController::class)
        ->name('admin.permintaan.cetak-xls')
        ->middleware('auth');

    Route::get('/admin/obat/cetak-xls', CetakObatExcelController::class)
        ->name('admin.obat.cetak-xls')
        ->middleware('auth');

    Route::get('/admin/template/prediksi-wide', DownloadTemplatePrediksiController::class)
        ->name('admin.template.prediksi-wide')
        ->middleware('auth');

    Route::get('/admin/lplpo/{lplpo}/cetak-pdf', CetakLplpoController::class)
        ->name('admin.lplpo.cetak-pdf')
        ->middleware('auth');

    Route::get('/admin/rko/{rko}/cetak-pdf', [CetakRkoController::class, 'cetakPdf'])
        ->name('admin.rko.cetak-pdf')
        ->middleware('auth');

    Route::get('/admin/rko/{rko}/cetak-xls', [CetakRkoController::class, 'cetakXls'])
        ->name('admin.rko.cetak-xls')
        ->middleware('auth');

    Route::middleware('auth')->group(function () {
        Route::get('/auth/google/link', [GoogleSocialiteController::class, 'redirectForLinking'])
            ->name('auth.google.link');

        Route::get('/auth/google/callback/link', [GoogleSocialiteController::class, 'handleLinkingCallback'])
            ->name('auth.google.callback.link');

        Route::post('/auth/google/unlink', [GoogleSocialiteController::class, 'unlink'])
            ->name('auth.google.unlink');
    });
});
