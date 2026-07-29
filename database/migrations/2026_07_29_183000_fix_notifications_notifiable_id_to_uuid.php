<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('notifications')) {
            return;
        }

        // Users usan UUID; morphs() creó notifiable_id como bigint.
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropMorphs('notifiable');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->uuidMorphs('notifiable');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('notifications')) {
            return;
        }

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropMorphs('notifiable');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->morphs('notifiable');
        });
    }
};
