<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orden_avances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('orden_trabajo_id')->constrained('ordenes_trabajo')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('mensaje');
            $table->timestamps();

            $table->index(['orden_trabajo_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orden_avances');
    }
};
