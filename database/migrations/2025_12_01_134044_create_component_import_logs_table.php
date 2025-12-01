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
        Schema::create('appza_component_import_logs', function (Blueprint $table) {
            $table->id();
            $table->string('component_name');
            $table->boolean('success')->default(false);
            $table->text('message')->nullable();
            $table->string('source')->default('manual');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appza_component_import_logs');
    }
};
