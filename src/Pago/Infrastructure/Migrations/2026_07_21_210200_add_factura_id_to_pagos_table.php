<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            $table->foreignUuid('factura_id')
                ->nullable()
                ->after('orden_trabajo_id')
                ->constrained('facturas')
                ->nullOnDelete();
            $table->unique('factura_id');
        });
    }

    public function down(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('factura_id');
        });
    }
};
