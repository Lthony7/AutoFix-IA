<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('presupuesto_servicios', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('presupuesto_id')->constrained('presupuestos')->cascadeOnDelete();
            $table->foreignUuid('servicio_id')->constrained('servicios')->restrictOnDelete();
            $table->string('nombre');
            $table->decimal('precio', 10, 2);
            $table->unsignedSmallInteger('cantidad')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('presupuesto_servicios');
    }
};
