<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facturas', function (Blueprint $table) {
            $table->string('cliente_tipo_documento')->nullable()->after('cliente_id');
            $table->string('cliente_numero_documento')->nullable()->after('cliente_tipo_documento');
            $table->string('cliente_nombres')->nullable()->after('cliente_numero_documento');
            $table->string('cliente_apellidos')->nullable()->after('cliente_nombres');
            $table->string('cliente_razon_social')->nullable()->after('cliente_apellidos');
            $table->string('cliente_direccion')->nullable()->after('cliente_razon_social');
            $table->string('cliente_telefono')->nullable()->after('cliente_direccion');
            $table->string('cliente_email')->nullable()->after('cliente_telefono');
        });
    }

    public function down(): void
    {
        Schema::table('facturas', function (Blueprint $table) {
            $table->dropColumn([
                'cliente_tipo_documento',
                'cliente_numero_documento',
                'cliente_nombres',
                'cliente_apellidos',
                'cliente_razon_social',
                'cliente_direccion',
                'cliente_telefono',
                'cliente_email',
            ]);
        });
    }
};
