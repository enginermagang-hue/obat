<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::create('pengaturan_laporan_new', function ($table) {
                $table->id();
                $table->foreignId('fasilitas_id')->nullable()->constrained('fasilitas_kesehatan')->nullOnDelete();
                $table->enum('grup', ['kop_surat', 'tanda_tangan', 'identitas_laporan', 'default_laporan', 'pdf', 'format_nomor', 'rko']);
                $table->string('key');
                $table->text('value');
                $table->timestamps();
            });

            DB::statement('INSERT INTO pengaturan_laporan_new SELECT * FROM pengaturan_laporan');
            Schema::disableForeignKeyConstraints();
            Schema::drop('pengaturan_laporan');
            Schema::rename('pengaturan_laporan_new', 'pengaturan_laporan');
            Schema::enableForeignKeyConstraints();

            Schema::table('pengaturan_laporan', function ($table) {
                $table->unique(['fasilitas_id', 'grup', 'key']);
            });
        } else {
            DB::statement("ALTER TABLE pengaturan_laporan MODIFY COLUMN grup ENUM('kop_surat','tanda_tangan','identitas_laporan','default_laporan','pdf','format_nomor','rko') NOT NULL");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::create('pengaturan_laporan_new', function ($table) {
                $table->id();
                $table->foreignId('fasilitas_id')->nullable()->constrained('fasilitas_kesehatan')->nullOnDelete();
                $table->enum('grup', ['kop_surat', 'tanda_tangan', 'identitas_laporan', 'default_laporan', 'pdf', 'format_nomor']);
                $table->string('key');
                $table->text('value');
                $table->timestamps();
            });

            DB::statement('INSERT INTO pengaturan_laporan_new SELECT id, fasilitas_id, grup, key, value, created_at, updated_at FROM pengaturan_laporan WHERE grup != \'rko\'');
            Schema::disableForeignKeyConstraints();
            Schema::drop('pengaturan_laporan');
            Schema::rename('pengaturan_laporan_new', 'pengaturan_laporan');
            Schema::enableForeignKeyConstraints();

            Schema::table('pengaturan_laporan', function ($table) {
                $table->unique(['fasilitas_id', 'grup', 'key']);
            });
        } else {
            DB::statement("DELETE FROM pengaturan_laporan WHERE grup = 'rko'");
            DB::statement("ALTER TABLE pengaturan_laporan MODIFY COLUMN grup ENUM('kop_surat','tanda_tangan','identitas_laporan','default_laporan','pdf','format_nomor') NOT NULL");
        }
    }
};
