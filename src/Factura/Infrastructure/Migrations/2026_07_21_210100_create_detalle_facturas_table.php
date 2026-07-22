<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detalle_facturas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('factura_id')->constrained('facturas')->cascadeOnDelete();
            $table->string('descripcion');
            $table->string('tipo'); // servicio|repuesto
            $table->uuid('referencia_id')->nullable();
            $table->unsignedInteger('cantidad')->default(1);
            $table->decimal('precio_unitario', 12, 2)->default(0);
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detalle_facturas');
    }
};
