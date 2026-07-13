<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->string('nombres')->nullable()->after('razon_social');
            $table->string('apellidos')->nullable()->after('nombres');
            $table->boolean('estado')->default(true)->after('email');
            $table->foreignUuid('user_id')->nullable()->after('estado')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn(['nombres', 'apellidos', 'estado']);
        });
    }
};
