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
        Schema::create('xml_import_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('batch_id')
                ->constrained('xml_import_batches')
                ->cascadeOnDelete();

            $table->string('entity_name', 100)->nullable();
            $table->string('entity_external_id', 100)->nullable();

            $table->string('operation_type', 50)->nullable();
            $table->string('status', 50)->nullable();

            $table->text('message')->nullable();

            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('xml_import_logs');
    }
};
