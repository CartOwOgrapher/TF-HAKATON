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
        Schema::create('xml_import_batches', function (Blueprint $table) {
            $table->id();

            $table->string('source_system', 100)->default('Global ERP');
            $table->string('file_name')->nullable();

            $table->timestamp('imported_at')->nullable();

            $table->foreignId('processed_by')
                ->nullable()
                ->references('id')->on('users')
                ->nullOnDelete();

            $table->longText('raw_payload')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('xml_import_batches');
    }
};
