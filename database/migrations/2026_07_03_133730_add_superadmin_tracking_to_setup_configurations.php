<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('setup_configurations', function (Blueprint $table) {
            $table->string('superadmin_email')->nullable()->after('admin_email');
            $table->string('superadmin_name')->nullable()->after('superadmin_email');
            $table->integer('setup_attempt_count')->default(0)->after('superadmin_name');
            $table->timestamp('last_setup_attempt_at')->nullable()->after('setup_attempt_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('setup_configurations', function (Blueprint $table) {
            $table->dropColumn(['superadmin_email', 'superadmin_name', 'setup_attempt_count', 'last_setup_attempt_at']);
        });
    }
};
