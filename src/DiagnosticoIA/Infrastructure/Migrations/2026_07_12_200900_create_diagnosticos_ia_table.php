<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diagnosticos_ia', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('orden_trabajo_id')->unique()->constrained('ordenes_trabajo')->cascadeOnDelete();
            $table->json('input_data');
            $table->text('respuesta_completa');
            $table->json('posibles_causas')->nullable();
            $table->string('servicio_recomendado')->nullable();
            $table->string('prioridad')->nullable();
            $table->text('observacion_mecanico')->nullable();
            $table->text('advertencia')->nullable();
            $table->string('estado')->default('generada');
            $table->boolean('es_simulado')->default(false);
            $table->text('observaciones_revision')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diagnosticos_ia');
    }
};
