<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('build_orders', function (Blueprint $table) {
            $table->boolean('is_push_notification')->default(false);
            $table->string('android_push_notification_url')->nullable();
            $table->string('ios_push_notification_url')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('build_orders', function (Blueprint $table) {
            $table->string('is_push_notification')->nullable();
            $table->string('android_push_notification_url')->nullable();
            $table->string('ios_push_notification_url')->nullable();
        });
    }
};
