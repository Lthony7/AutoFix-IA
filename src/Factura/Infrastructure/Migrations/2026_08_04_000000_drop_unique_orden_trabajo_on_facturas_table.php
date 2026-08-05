<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Permite generar una nueva factura para una OT cuya factura anterior
        // fue anulada. La unicidad por OT pasa a validarse a nivel de app:
        // solo puede existir UNA factura NO anulada por orden de trabajo.
        Schema::table('facturas', function (Blueprint $table) {
            $table->dropUnique('facturas_orden_trabajo_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('facturas', function (Blueprint $table) {
            $table->unique('orden_trabajo_id');
        });
    }
};
