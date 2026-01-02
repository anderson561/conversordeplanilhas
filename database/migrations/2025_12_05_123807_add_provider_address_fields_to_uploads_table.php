<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('uploads', function (Blueprint $table) {
            // Remove old state column
            $table->dropColumn('state');

            // Add new provider address fields
            $table->string('provider_endereco')->nullable()->after('provider_info');
            $table->string('provider_bairro')->nullable()->after('provider_endereco');
            $table->string('provider_cep', 10)->nullable()->after('provider_bairro');
            $table->string('provider_municipio')->nullable()->after('provider_cep');
            $table->string('provider_uf', 2)->nullable()->after('provider_municipio');
            $table->string('provider_fone', 20)->nullable()->after('provider_uf');
        });

        Schema::table('conversion_jobs', function (Blueprint $table) {
            // Remove old state column
            $table->dropColumn('state');
        });
    }

    public function down(): void
    {
        Schema::table('uploads', function (Blueprint $table) {
            $table->dropColumn([
                'provider_endereco',
                'provider_bairro',
                'provider_cep',
                'provider_municipio',
                'provider_uf',
                'provider_fone'
            ]);

            $table->string('state', 2)->nullable()->after('xml_type');
        });

        Schema::table('conversion_jobs', function (Blueprint $table) {
            $table->string('state', 2)->nullable()->after('xml_type');
        });
    }
};
