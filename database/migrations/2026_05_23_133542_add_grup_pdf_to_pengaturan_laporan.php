<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            // SQLite does not support MODIFY COLUMN; recreate table
            Schema::create('pengaturan_laporan_new', function ($table) {
                $table->id();
                $table->foreignId('fasilitas_id')->nullable()->constrained('fasilitas_kesehatan')->nullOnDelete();
                $table->enum('grup', ['kop_surat', 'tanda_tangan', 'identitas_laporan', 'default_laporan', 'pdf']);
                $table->string('key');
                $table->text('value');
                $table->timestamps();
                $table->unique(['fasilitas_id', 'grup', 'key']);
            });

            DB::statement('INSERT INTO pengaturan_laporan_new SELECT * FROM pengaturan_laporan');
            Schema::drop('pengaturan_laporan');
            Schema::rename('pengaturan_laporan_new', 'pengaturan_laporan');
        } else {
            DB::statement("ALTER TABLE pengaturan_laporan MODIFY COLUMN grup ENUM('kop_surat','tanda_tangan','identitas_laporan','default_laporan','pdf') NOT NULL");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::create('pengaturan_laporan_old', function ($table) {
                $table->id();
                $table->foreignId('fasilitas_id')->nullable()->constrained('fasilitas_kesehatan')->nullOnDelete();
                $table->enum('grup', ['kop_surat', 'tanda_tangan', 'identitas_laporan', 'default_laporan']);
                $table->string('key');
                $table->text('value');
                $table->timestamps();
                $table->unique(['fasilitas_id', 'grup', 'key']);
            });

            DB::statement('INSERT INTO pengaturan_laporan_old SELECT id, fasilitas_id, grup, key, value, created_at, updated_at FROM pengaturan_laporan');
            Schema::drop('pengaturan_laporan');
            Schema::rename('pengaturan_laporan_old', 'pengaturan_laporan');
        } else {
            DB::statement("ALTER TABLE pengaturan_laporan MODIFY COLUMN grup ENUM('kop_surat','tanda_tangan','identitas_laporan','default_laporan') NOT NULL");
        }
    }
};
