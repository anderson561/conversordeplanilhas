<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('conversion_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('upload_id')->constrained()->cascadeOnDelete();
            $table->foreignId('mapping_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('pending'); // pending, processing, completed, failed
            $table->string('result_file_path')->nullable(); // Path to generated XML or ZIP
            $table->text('error_log')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversion_jobs');
    }
};
