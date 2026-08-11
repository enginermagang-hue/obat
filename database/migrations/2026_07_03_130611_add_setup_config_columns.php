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
        Schema::create('setup_configurations', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_setup_completed')->default(false);
            $table->string('organization_name')->nullable();
            $table->string('organization_code')->nullable();
            $table->text('organization_description')->nullable();
            $table->string('primary_facility_name')->nullable();
            $table->string('primary_facility_code')->nullable();
            $table->string('admin_email')->nullable();
            $table->string('admin_name')->nullable();
            $table->text('pdf_header')->nullable();
            $table->text('pdf_footer')->nullable();
            $table->string('document_number_format')->default('INV-{YYYY}{MM}{DD}-{SEQ}');
            $table->integer('document_number_sequence')->default(0);
            $table->timestamp('setup_completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('setup_configurations');
    }
};
