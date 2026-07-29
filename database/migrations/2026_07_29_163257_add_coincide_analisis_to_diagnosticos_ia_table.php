<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('diagnosticos_ia', function (Blueprint $table) {
            $table->boolean('coincide_analisis')->nullable()->after('observaciones_revision');
        });
    }

    public function down(): void
    {
        Schema::table('diagnosticos_ia', function (Blueprint $table) {
            $table->dropColumn('coincide_analisis');
        });
    }
};
