<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('citas_taller', function (Blueprint $table) {
            $table->foreignUuid('presupuesto_id')
                ->nullable()
                ->after('orden_trabajo_id')
                ->constrained('presupuestos')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('citas_taller', function (Blueprint $table) {
            $table->dropConstrainedForeignId('presupuesto_id');
        });
    }
};
