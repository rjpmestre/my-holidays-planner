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
        Schema::table('vacations', function (Blueprint $table) {
            $table->unsignedBigInteger('type_id')->default(1)->after('date'); // 1 = regular vacation
            $table->unsignedInteger('year_carried_from')->nullable()->after('type_id'); // Year this day was carried from

            $table->foreign('type_id')->references('id')->on('vacation_types')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vacations', function (Blueprint $table) {
            $table->dropForeign(['type_id']);
            $table->dropColumn(['type_id', 'year_carried_from']);
        });
    }
};
