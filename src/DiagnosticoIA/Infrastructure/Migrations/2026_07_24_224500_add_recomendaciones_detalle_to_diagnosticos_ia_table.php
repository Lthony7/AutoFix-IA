<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('diagnosticos_ia', function (Blueprint $table) {
            $table->text('diagnostico_detalle')->nullable()->after('respuesta_completa');
            $table->string('especialidad_recomendada')->nullable()->after('servicio_recomendado');
            $table->json('acciones_recomendadas')->nullable()->after('especialidad_recomendada');
            $table->json('mecanicos_sugeridos')->nullable()->after('acciones_recomendadas');
        });
    }

    public function down(): void
    {
        Schema::table('diagnosticos_ia', function (Blueprint $table) {
            $table->dropColumn([
                'diagnostico_detalle',
                'especialidad_recomendada',
                'acciones_recomendadas',
                'mecanicos_sugeridos',
            ]);
        });
    }
};
