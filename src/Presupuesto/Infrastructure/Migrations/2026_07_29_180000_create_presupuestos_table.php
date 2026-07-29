<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('presupuestos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('numero')->unique();
            $table->foreignUuid('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->foreignUuid('vehiculo_id')->nullable()->constrained('vehiculos')->nullOnDelete();
            $table->string('estado')->default('guardado');
            $table->decimal('subtotal_servicios', 10, 2)->default(0);
            $table->decimal('subtotal_repuestos', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->text('notas')->nullable();
            $table->date('valido_hasta')->nullable();
            $table->timestamps();

            $table->index(['cliente_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('presupuestos');
    }
};
