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
        Schema::create('vacation_settings', function (Blueprint $table) {
            $table->id();
            $table->integer('year')->unique();
            $table->integer('max_vacation_days')->default(22);
            $table->integer('max_volunteer_days')->default(1);
            $table->integer('manual_carried_days')->default(0);
            $table->integer('min_consecutive_vacation_days')->default(10);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vacation_settings');
    }
};
