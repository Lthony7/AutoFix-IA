<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('presupuesto_repuestos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('presupuesto_id')->constrained('presupuestos')->cascadeOnDelete();
            $table->foreignUuid('producto_id')->constrained('productos')->restrictOnDelete();
            $table->string('codigo')->nullable();
            $table->string('nombre');
            $table->decimal('precio_unitario', 10, 2);
            $table->unsignedInteger('cantidad')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('presupuesto_repuestos');
    }
};
