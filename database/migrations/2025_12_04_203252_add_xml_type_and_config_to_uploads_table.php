<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('uploads', function (Blueprint $table) {
            $table->string('xml_type')->default('servico')->after('mime_type');
            $table->string('state', 2)->nullable()->after('xml_type'); // UF (ex: BA, SP)
            $table->integer('starting_number')->nullable()->after('state'); // Número inicial
        });

        Schema::table('conversion_jobs', function (Blueprint $table) {
            $table->string('xml_type')->default('servico')->after('status');
            $table->string('state', 2)->nullable()->after('xml_type');
            $table->integer('starting_number')->nullable()->after('state');
        });
    }

    public function down(): void
    {
        Schema::table('uploads', function (Blueprint $table) {
            $table->dropColumn(['xml_type', 'state', 'starting_number']);
        });

        Schema::table('conversion_jobs', function (Blueprint $table) {
            $table->dropColumn(['xml_type', 'state', 'starting_number']);
        });
    }
};
