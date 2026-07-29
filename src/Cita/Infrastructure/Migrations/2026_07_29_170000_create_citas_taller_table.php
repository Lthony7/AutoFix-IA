<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('citas_taller', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->foreignUuid('vehiculo_id')->constrained('vehiculos')->cascadeOnDelete();
            $table->foreignUuid('mecanico_id')->nullable()->constrained('mecanicos')->nullOnDelete();
            $table->foreignUuid('orden_trabajo_id')->nullable()->constrained('ordenes_trabajo')->nullOnDelete();
            $table->dateTime('fecha_hora');
            $table->unsignedSmallInteger('duracion_minutos')->default(60);
            $table->string('tipo')->default('reparacion');
            $table->string('estado')->default('programada');
            $table->text('notas')->nullable();
            $table->timestamps();

            $table->index(['fecha_hora', 'estado']);
            $table->index('mecanico_id');
            $table->index('cliente_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('citas_taller');
    }
};
