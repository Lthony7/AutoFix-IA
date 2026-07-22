<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ordenes_trabajo', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('numero')->unique();
            $table->foreignUuid('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->foreignUuid('vehiculo_id')->constrained('vehiculos')->cascadeOnDelete();
            $table->foreignUuid('mecanico_id')->nullable()->constrained('mecanicos')->nullOnDelete();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('estado')->default('pendiente');
            $table->string('tipo_falla')->nullable();
            $table->text('falla_reportada');
            $table->unsignedInteger('kilometraje_ingreso')->default(0);
            $table->text('observaciones')->nullable();
            $table->text('diagnostico_tecnico')->nullable();
            $table->string('prioridad')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ordenes_trabajo');
    }
};
