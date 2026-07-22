<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->string('categoria')->nullable()->after('tipo_producto');
            $table->integer('stock_minimo')->default(0)->after('stock');
            $table->string('proveedor')->nullable()->after('categoria');
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropColumn(['categoria', 'stock_minimo', 'proveedor']);
        });
    }
};
